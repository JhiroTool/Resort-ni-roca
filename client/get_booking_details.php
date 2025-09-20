<?php
/**
 * Get Booking Details
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
    
    // Get detailed booking information
    $bookingQuery = "SELECT b.*, r.Room_Type, r.Room_Rate, r.Room_Cap, r.Room_Desc,
                            c.Cust_FN, c.Cust_LN, c.Cust_Email, c.Cust_Phone,
                            GROUP_CONCAT(DISTINCT CONCAT(a.Amenity_Name, ' (₱', a.Amenity_Cost, ')') SEPARATOR ', ') as amenities,
                            GROUP_CONCAT(DISTINCT CONCAT(s.Service_Name, ' (₱', s.Service_Cost, ')') SEPARATOR ', ') as services,
                            p.Payment_Amount, p.Payment_Method, p.Payment_Date
                     FROM booking b
                     JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
                     JOIN room r ON br.Room_ID = r.Room_ID
                     JOIN customer c ON b.Cust_ID = c.Cust_ID
                     LEFT JOIN bookingamenity ba ON b.Booking_ID = ba.Booking_ID
                     LEFT JOIN amenity a ON ba.Amenity_ID = a.Amenity_ID
                     LEFT JOIN bookingservice bs ON b.Booking_ID = bs.Booking_ID
                     LEFT JOIN service s ON bs.Service_ID = s.Service_ID
                     LEFT JOIN payment p ON b.Booking_ID = p.Booking_ID
                     WHERE b.Booking_ID = ? AND b.Cust_ID = ?
                     GROUP BY b.Booking_ID";
    
    $booking = executeQuery($bookingQuery, [$bookingId, $customerId], 'ii');
    
    if (empty($booking)) {
        throw new Exception('Booking not found or does not belong to you');
    }
    
    $booking = $booking[0];
    
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
        </div>';
        
    if ($booking['amenities']) {
        $html .= '
        <div class="detail-section">
            <h5><i class="fas fa-star"></i> Amenities</h5>
            <div class="amenities-list">
                ' . htmlspecialchars($booking['amenities']) . '
            </div>
        </div>';
    }
    
    if ($booking['services']) {
        $html .= '
        <div class="detail-section">
            <h5><i class="fas fa-concierge-bell"></i> Services</h5>
            <div class="services-list">
                ' . htmlspecialchars($booking['services']) . '
            </div>
        </div>';
    }
    
    $html .= '
        <div class="detail-section">
            <h5><i class="fas fa-user"></i> Guest Information</h5>
            <div class="detail-grid">
                <div class="detail-row">
                    <strong>Name:</strong>
                    <span>' . htmlspecialchars($booking['Cust_FN'] . ' ' . $booking['Cust_LN']) . '</span>
                </div>
                <div class="detail-row">
                    <strong>Email:</strong>
                    <span>' . htmlspecialchars($booking['Cust_Email']) . '</span>
                </div>
                <div class="detail-row">
                    <strong>Phone:</strong>
                    <span>' . htmlspecialchars($booking['Cust_Phone']) . '</span>
                </div>
            </div>
        </div>';
        
    if ($booking['Payment_Amount']) {
        $paymentDate = $booking['Payment_Date'] ? new DateTime($booking['Payment_Date']) : null;
        $html .= '
        <div class="detail-section">
            <h5><i class="fas fa-credit-card"></i> Payment Information</h5>
            <div class="detail-grid">
                <div class="detail-row">
                    <strong>Amount:</strong>
                    <span>₱' . number_format($booking['Payment_Amount'], 2) . '</span>
                </div>
                <div class="detail-row">
                    <strong>Method:</strong>
                    <span>' . htmlspecialchars($booking['Payment_Method']) . '</span>
                </div>';
        
        if ($paymentDate) {
            $html .= '
                <div class="detail-row">
                    <strong>Date:</strong>
                    <span>' . $paymentDate->format('F j, Y g:i A') . '</span>
                </div>';
        }
        
        $html .= '
            </div>
        </div>';
    }
    
    $html .= '
        <div class="detail-section cost-summary">
            <h5><i class="fas fa-calculator"></i> Cost Summary</h5>
            <div class="cost-breakdown">
                <div class="cost-row">
                    <span>Room (' . $nights . ' night' . ($nights > 1 ? 's' : '') . '):</span>
                    <span>₱' . number_format($booking['Room_Rate'] * $nights, 2) . '</span>
                </div>';
    
    // Add amenity and service costs if available
    if ($booking['amenities']) {
        $html .= '
                <div class="cost-row">
                    <span>Amenities:</span>
                    <span>Included</span>
                </div>';
    }
    
    if ($booking['services']) {
        $html .= '
                <div class="cost-row">
                    <span>Services:</span>
                    <span>Included</span>
                </div>';
    }
    
    $html .= '
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
        
        .amenities-list,
        .services-list {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #4a9960;
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
        
        .payment-status.completed {
            color: #28a745;
            font-weight: 600;
        }
        
        .payment-status.pending {
            color: #ffc107;
            font-weight: 600;
        }
        
        .payment-status.refunded {
            color: #6c757d;
            font-weight: 600;
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
