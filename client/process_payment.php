<?php
/**
 * Process Payment Handler
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
error_log("Process payment started for customer: " . $_SESSION['cust_id']);

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

    // Validate required fields
    $requiredFields = ['booking_id', 'amount', 'payment_method'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            error_log("Missing field: $field");
            throw new Exception("Missing required field: $field");
        }
    }

    $bookingId = (int)$_POST['booking_id'];
    $amount = (float)$_POST['amount'];
    $paymentMethod = $_POST['payment_method'];

    error_log("Processing payment: Booking ID: $bookingId, Amount: $amount, Method: $paymentMethod");

    // Verify booking belongs to customer and is payable
    $verifyQuery = "SELECT b.Booking_ID, b.Booking_Status, b.Booking_Cost, 
                           COALESCE(SUM(p.Payment_Amount), 0) as paid_amount
                   FROM booking b 
                   LEFT JOIN payment p ON b.Booking_ID = p.Booking_ID
                   WHERE b.Booking_ID = ? AND b.Cust_ID = ?
                   GROUP BY b.Booking_ID";
    
    $booking = executeQuery($verifyQuery, [$bookingId, $customerId], 'ii');
    
    if (empty($booking)) {
        throw new Exception('Booking not found or does not belong to you');
    }
    
    $booking = $booking[0];
    
    // Check if booking is payable
    if ($booking['Booking_Status'] !== 'Pending') {
        throw new Exception('This booking is not available for payment');
    }

    $remainingAmount = $booking['Booking_Cost'] - $booking['paid_amount'];
    
    // Verify payment amount
    if ($amount > $remainingAmount) {
        throw new Exception('Payment amount exceeds remaining balance');
    }

    // Handle file upload for receipt
    $receiptPath = '';
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/receipts/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $fileName = 'receipt_' . $bookingId . '_' . time() . '.' . $fileExtension;
        $receiptPath = $uploadDir . $fileName;
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($fileExtension), $allowedTypes)) {
            throw new Exception('Invalid file type. Please upload an image file (JPG, PNG, GIF).');
        }
        
        // Validate file size (max 5MB)
        if ($_FILES['receipt']['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $receiptPath)) {
            throw new Exception('Failed to upload receipt image');
        }
        
        // Store relative path for database
        $receiptPath = 'uploads/receipts/' . $fileName;
    } else {
        throw new Exception('Receipt image is required');
    }

    // Start database transaction
    $conn = getDBConnection();
    $conn->autocommit(false);

    try {
        // Insert payment record
        $paymentQuery = "INSERT INTO payment (Booking_ID, Payment_Date, Payment_Amount, Payment_Method, Receipt_Image) 
                        VALUES (?, NOW(), ?, ?, ?)";
        $paymentStmt = $conn->prepare($paymentQuery);
        
        if (!$paymentStmt) {
            throw new Exception('Failed to prepare payment statement: ' . $conn->error);
        }
        
        $paymentStmt->bind_param('idss', $bookingId, $amount, $paymentMethod, $receiptPath);
        
        if (!$paymentStmt->execute()) {
            throw new Exception('Failed to record payment: ' . $paymentStmt->error);
        }
        
        $paymentId = $conn->insert_id;
        
        // Check if this payment completes the booking
        $totalPaid = $booking['paid_amount'] + $amount;
        
        if ($totalPaid >= $booking['Booking_Cost']) {
            // Update booking status to Paid
            $updateBookingQuery = "UPDATE booking SET Booking_Status = 'Paid' WHERE Booking_ID = ?";
            $updateStmt = $conn->prepare($updateBookingQuery);
            
            if (!$updateStmt) {
                throw new Exception('Failed to prepare booking update statement: ' . $conn->error);
            }
            
            $updateStmt->bind_param('i', $bookingId);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update booking status: ' . $updateStmt->error);
            }
            
            $updateStmt->close();
            error_log("Booking $bookingId marked as Paid");
        } else {
            error_log("Partial payment recorded. Remaining: " . ($booking['Booking_Cost'] - $totalPaid));
        }

        // Commit transaction
        $conn->commit();
        $paymentStmt->close();
        $conn->close();
        
        error_log("Payment processed successfully: Payment ID $paymentId for Booking $bookingId");
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment submitted successfully! Your booking will be confirmed once payment is verified.',
            'payment_id' => $paymentId,
            'booking_id' => $bookingId,
            'amount' => $amount,
            'remaining' => $booking['Booking_Cost'] - $totalPaid,
            'fully_paid' => $totalPaid >= $booking['Booking_Cost']
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $conn->close();
        
        // Delete uploaded file if transaction failed
        if ($receiptPath && file_exists('../' . $receiptPath)) {
            unlink('../' . $receiptPath);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    error_log("Process payment error: " . $e->getMessage());
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
