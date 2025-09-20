<?php
/**
 * Process Booking
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
error_log("Process booking started for customer: " . $_SESSION['cust_id']);
error_log("POST data: " . print_r($_POST, true));

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
    $requiredFields = ['room_id', 'checkin', 'checkout', 'guests'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            error_log("Missing field: $field");
            throw new Exception("Missing required field: $field");
        }
    }

    // Get form data
    $roomId = (int)$_POST['room_id'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $guests = (int)$_POST['guests'];
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
    $services = isset($_POST['services']) ? $_POST['services'] : [];

    // Validate dates
    $checkinDate = new DateTime($checkin);
    $checkoutDate = new DateTime($checkout);
    $today = new DateTime();
    
    if ($checkinDate < $today) {
        throw new Exception('Check-in date cannot be in the past');
    }
    
    if ($checkoutDate <= $checkinDate) {
        throw new Exception('Check-out date must be after check-in date');
    }

    // Calculate nights
    $nights = $checkinDate->diff($checkoutDate)->days;
    
    // Get room details
    $roomQuery = "SELECT * FROM room WHERE Room_ID = ? AND Room_Status = 'Available'";
    $roomResult = executeQuery($roomQuery, [$roomId], 'i');
    
    error_log("Room query executed, result count: " . (is_array($roomResult) ? count($roomResult) : 'null'));
    
    if (!$roomResult || empty($roomResult)) {
        throw new Exception('Selected room is not available');
    }
    
    $room = $roomResult[0];
    error_log("Room details: " . print_r($room, true));
    
    // Check room capacity
    if ($guests > $room['Room_Cap']) {
        throw new Exception('Number of guests exceeds room capacity');
    }

    // Check room availability for selected dates
    $availabilityQuery = "SELECT COUNT(*) as conflict_count FROM booking b 
                         JOIN bookingroom br ON b.Booking_ID = br.Booking_ID 
                         WHERE br.Room_ID = ? 
                         AND b.Booking_Status IN ('Paid', 'Pending')
                         AND (
                             (? BETWEEN DATE(b.Booking_IN) AND DATE(b.Booking_Out)) OR
                             (? BETWEEN DATE(b.Booking_IN) AND DATE(b.Booking_Out)) OR
                             (DATE(b.Booking_IN) BETWEEN ? AND ?) OR
                             (DATE(b.Booking_Out) BETWEEN ? AND ?)
                         )";
    $availabilityResult = executeQuery($availabilityQuery, [
        $roomId, $checkin, $checkout, $checkin, $checkout, $checkin, $checkout
    ], 'issssss');
    
    if ($availabilityResult[0]['conflict_count'] > 0) {
        throw new Exception('Room is not available for selected dates');
    }

    // Calculate costs
    $roomCost = $room['Room_Rate'] * $nights;
    $amenityCost = 0;
    $serviceCost = 0;

    // Calculate amenity costs
    if (!empty($amenities)) {
        $amenityIds = implode(',', array_map('intval', $amenities));
        $amenityQuery = "SELECT SUM(Amenity_Cost) as total FROM amenity WHERE Amenity_ID IN ($amenityIds)";
        $amenityResult = executeQuery($amenityQuery);
        $amenityCost = $amenityResult[0]['total'] ?? 0;
    }

    // Calculate service costs
    if (!empty($services)) {
        $serviceIds = implode(',', array_map('intval', $services));
        $serviceQuery = "SELECT SUM(Service_Cost) as total FROM service WHERE Service_ID IN ($serviceIds)";
        $serviceResult = executeQuery($serviceQuery);
        $serviceCost = $serviceResult[0]['total'] ?? 0;
    }

    $totalCost = $roomCost + $amenityCost + $serviceCost;

    // Start transaction
    $conn = getDBConnection();
    $conn->autocommit(false);

    try {
        // Insert booking
        $bookingQuery = "INSERT INTO booking (Cust_ID, Booking_IN, Booking_Out, Booking_Cost, Booking_Status, Guests) 
                        VALUES (?, ?, ?, ?, 'Pending', ?)";
        $bookingStmt = $conn->prepare($bookingQuery);
        // Prepare datetime strings
        $checkinDateTime = $checkin . ' 16:00:00';
        $checkoutDateTime = $checkout . ' 16:00:00';
        
        error_log("About to bind parameters: customerId=$customerId, checkin=$checkinDateTime, checkout=$checkoutDateTime, totalCost=$totalCost, guests=$guests");
        $bookingStmt->bind_param('issdi', $customerId, $checkinDateTime, $checkoutDateTime, $totalCost, $guests);
        error_log("Parameters bound successfully");
        
        if (!$bookingStmt->execute()) {
            error_log("Booking statement execution failed: " . $bookingStmt->error);
            throw new Exception('Failed to create booking: ' . $bookingStmt->error);
        } else {
            error_log("Booking statement executed successfully");
        }
        
        $bookingId = $conn->insert_id;

        // Insert room booking
        $roomBookingQuery = "INSERT INTO bookingroom (Booking_ID, Room_ID) VALUES (?, ?)";
        $roomBookingStmt = $conn->prepare($roomBookingQuery);
        $roomBookingStmt->bind_param('ii', $bookingId, $roomId);
        
        if (!$roomBookingStmt->execute()) {
            throw new Exception('Failed to link room to booking');
        }

        // Insert amenity bookings
        if (!empty($amenities)) {
            $amenityBookingQuery = "INSERT INTO bookingamenity (Booking_ID, Amenity_ID, RA_Quantity) VALUES (?, ?, 0)";
            $amenityStmt = $conn->prepare($amenityBookingQuery);
            
            foreach ($amenities as $amenityId) {
                $amenityStmt->bind_param('ii', $bookingId, $amenityId);
                if (!$amenityStmt->execute()) {
                    throw new Exception('Failed to add amenities');
                }
            }
        }

        // Insert service bookings
        if (!empty($services)) {
            $serviceBookingQuery = "INSERT INTO bookingservice (Booking_ID, Service_ID) VALUES (?, ?)";
            $serviceStmt = $conn->prepare($serviceBookingQuery);
            
            foreach ($services as $serviceId) {
                $serviceStmt->bind_param('ii', $bookingId, $serviceId);
                if (!$serviceStmt->execute()) {
                    throw new Exception('Failed to add services');
                }
            }
        }

        // Commit transaction
        $conn->commit();
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking_id' => $bookingId,
            'total_cost' => $totalCost
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    } finally {
        $conn->close();
    }

} catch (Exception $e) {
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
