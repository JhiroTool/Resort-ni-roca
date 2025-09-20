<?php
/**
 * Cancel Booking Handler
 * Paradise Resort - Guest Accommodation System
 */

require_once '../config/database.php';

// Start secure session
startSecureSession();

// Require login
requireLogin();

// Set JSON response header
header('Content-Type: application/json');

// Add debugging
error_log("Cancel booking request started for customer: " . $_SESSION['cust_id']);

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get customer ID
    $customerId = $_SESSION['cust_id'];
    if (!$customerId) {
        throw new Exception('Customer not found');
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['booking_id'])) {
        throw new Exception('Booking ID is required');
    }
    
    $bookingId = (int)$input['booking_id'];
    
    error_log("Attempting to cancel booking ID: $bookingId for customer: $customerId");
    
    // Verify booking belongs to customer and can be cancelled
    $verifyQuery = "SELECT b.Booking_ID, b.Booking_Status, b.Booking_IN, b.Booking_Cost 
                   FROM booking b 
                   WHERE b.Booking_ID = ? AND b.Cust_ID = ?";
    
    $booking = executeQuery($verifyQuery, [$bookingId, $customerId], 'ii');
    
    if (empty($booking)) {
        throw new Exception('Booking not found or does not belong to you');
    }
    
    $booking = $booking[0];
    
    // Check if booking can be cancelled
    if ($booking['Booking_Status'] === 'Cancelled') {
        throw new Exception('Booking is already cancelled');
    }
    
    if ($booking['Booking_Status'] === 'Completed') {
        throw new Exception('Cannot cancel a completed booking');
    }
    
    // Check if check-in date is too close (less than 24 hours)
    $checkinDate = new DateTime($booking['Booking_IN']);
    $now = new DateTime();
    $hoursDiff = ($checkinDate->getTimestamp() - $now->getTimestamp()) / 3600;
    
    if ($hoursDiff < 24) {
        throw new Exception('Cannot cancel booking less than 24 hours before check-in');
    }
    
    // Start database transaction
    $conn = getDBConnection();
    $conn->autocommit(false);
    
    try {
        // Update booking status to cancelled
        $cancelQuery = "UPDATE booking SET Booking_Status = 'Cancelled' WHERE Booking_ID = ?";
        $cancelStmt = $conn->prepare($cancelQuery);
        
        if (!$cancelStmt) {
            throw new Exception('Failed to prepare cancel statement: ' . $conn->error);
        }
        
        $cancelStmt->bind_param('i', $bookingId);
        
        if (!$cancelStmt->execute()) {
            throw new Exception('Failed to cancel booking: ' . $cancelStmt->error);
        }
        
        // Check if there are any payments to refund
        $paymentQuery = "SELECT Payment_ID, Payment_Amount FROM payment WHERE Booking_ID = ?";
        $payments = executeQuery($paymentQuery, [$bookingId], 'i');
        
        $refundAmount = 0;
        if (!empty($payments)) {
            foreach ($payments as $payment) {
                // For this system, we'll just calculate refund amount
                // In a real system, you would process actual refunds here
                $refundAmount += $payment['Payment_Amount'];
            }
        }
        
        // Log the cancellation
        error_log("Booking $bookingId cancelled successfully for customer $customerId. Refund amount: $refundAmount");
        
        // Commit transaction
        $conn->commit();
        $cancelStmt->close();
        $conn->close();
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Booking cancelled successfully' . ($refundAmount > 0 ? '. Refund of ₱' . number_format($refundAmount, 2) . ' will be processed within 3-5 business days.' : '.'),
            'booking_id' => $bookingId,
            'refund_amount' => $refundAmount
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $conn->close();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Cancel booking error: " . $e->getMessage());
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
