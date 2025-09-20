<?php
/**
 * Get Booking Details (Simple Version)
 * Paradise Resort - Guest Accommodation System
 */

require_once '../config/database.php';

// Start secure session
startSecureSession();

// Require login
requireLogin();

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // Get customer ID
    $customerId = $_SESSION['cust_id'];
    if (!$customerId) {
        throw new Exception('Customer not found');
    }

    // Get booking ID from query parameter
    if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
        throw new Exception('Booking ID is required');
    }
    
    $bookingId = (int)$_GET['booking_id'];
    
    // Simple booking query without complex joins
    $bookingQuery = "SELECT b.*, r.Room_Type, r.Room_Rate, r.Room_Cap
                     FROM booking b
                     JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
                     JOIN room r ON br.Room_ID = r.Room_ID
                     WHERE b.Booking_ID = ? AND b.Cust_ID = ?";
    
    $booking = executeQuery($bookingQuery, [$bookingId, $customerId], 'ii');
    
    if (empty($booking)) {
        throw new Exception('Booking not found or does not belong to you');
    }
    
    $booking = $booking[0];
    
    // Get customer details
    $customerQuery = "SELECT Cust_FN, Cust_LN, Cust_Email, Cust_Phone FROM customer WHERE Cust_ID = ?";
    $customer = executeQuery($customerQuery, [$customerId], 'i');
    $customer = $customer[0];
    
    // Format dates
    $checkinDate = new DateTime($booking['Booking_IN']);
    $checkoutDate = new DateTime($booking['Booking_Out']);
    $nights = $checkinDate->diff($checkoutDate)->days;
    
    // Generate HTML for modal
    $html = '
    <div class="booking-detail-modal">
        <div class="detail-header">
            <h4>Booking #' . str_pad($booking['Booking_ID'], 4, '0', STR_PAD_LEFT) . '</h4>
            <div class="status-badge ' . strtolower($booking['Booking_Status']) . '">' . $booking['Booking_Status'] . '</div>
        </div>
        
        <div class="detail-section">
            <h5><i class="fas fa-bed"></i> Room Information</h5>
            <div class="detail-grid">
                <div class="detail-row">
                    <strong>Room Type:</strong>
                    <span>' . htmlspecialchars($booking['Room_Type']) . '</span>
                </div>
                <div class="detail-row">
                    <strong>Room Rate:</strong>
                    <span>₱' . number_format($booking['Room_Rate'], 2) . ' per night</span>
                </div>
                <div class="detail-row">
                    <strong>Capacity:</strong>
                    <span>' . $booking['Room_Cap'] . ' guests</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h5><i class="fas fa-calendar"></i> Stay Details</h5>
            <div class="detail-grid">
                <div class="detail-row">
                    <strong>Check-in:</strong>
                    <span>' . $checkinDate->format('l, F j, Y') . ' at 4:00 PM</span>
                </div>
                <div class="detail-row">
                    <strong>Check-out:</strong>
                    <span>' . $checkoutDate->format('l, F j, Y') . ' at 4:00 PM</span>
                </div>
                <div class="detail-row">
                    <strong>Duration:</strong>
                    <span>' . $nights . ' night' . ($nights > 1 ? 's' : '') . '</span>
                </div>
                <div class="detail-row">
                    <strong>Guests:</strong>
                    <span>' . $booking['Guests'] . ' guest' . ($booking['Guests'] > 1 ? 's' : '') . '</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h5><i class="fas fa-user"></i> Guest Information</h5>
            <div class="detail-grid">
                <div class="detail-row">
                    <strong>Name:</strong>
                    <span>' . htmlspecialchars($customer['Cust_FN'] . ' ' . $customer['Cust_LN']) . '</span>
                </div>
                <div class="detail-row">
                    <strong>Email:</strong>
                    <span>' . htmlspecialchars($customer['Cust_Email']) . '</span>
                </div>
                <div class="detail-row">
                    <strong>Phone:</strong>
                    <span>' . htmlspecialchars($customer['Cust_Phone']) . '</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section cost-summary">
            <h5><i class="fas fa-calculator"></i> Cost Summary</h5>
            <div class="cost-breakdown">
                <div class="cost-row">
                    <span>Room (' . $nights . ' night' . ($nights > 1 ? 's' : '') . '):</span>
                    <span>₱' . number_format($booking['Room_Rate'] * $nights, 2) . '</span>
                </div>
                <div class="cost-row total">
                    <strong>Total:</strong>
                    <strong>₱' . number_format($booking['Booking_Cost'], 2) . '</strong>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .booking-detail-modal {
            max-width: 100%;
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .detail-header h4 {
            margin: 0;
            color: #2c5530;
        }
        
        .detail-section {
            margin-bottom: 1.5rem;
        }
        
        .detail-section h5 {
            color: #4a9960;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-grid {
            display: grid;
            gap: 0.75rem;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row strong {
            color: #555;
        }
        
        .cost-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
        }
        
        .cost-breakdown {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .cost-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }
        
        .cost-row.total {
            border-top: 2px solid #ddd;
            padding-top: 1rem;
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }
    </style>';
    
    // Send success response
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
