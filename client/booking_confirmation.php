<?php
/**
 * Booking Confirmation Page
 * Paradise Resort - Guest Accommodation System
 */

require_once '../config/database.php';

// Start secure session
startSecureSession();

// Require login
requireLogin();

// Get customer information
$customer = DatabaseUtilities::getCustomerById($_SESSION['cust_id']);

if (!$customer) {
    logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}

// Get booking ID from URL
$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$bookingId) {
    header('Location: bookings.php');
    exit();
}

// Get booking details
$bookingQuery = "SELECT b.*, r.Room_Type, r.Room_Rate, r.Room_Cap,
                        GROUP_CONCAT(DISTINCT a.Amenity_Name SEPARATOR ', ') as amenities,
                        GROUP_CONCAT(DISTINCT s.Service_Name SEPARATOR ', ') as services,
                        p.Payment_Amount, p.Payment_Method, p.Payment_Date
                 FROM booking b
                 JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
                 JOIN room r ON br.Room_ID = r.Room_ID
                 LEFT JOIN bookingamenity ba ON b.Booking_ID = ba.Booking_ID
                 LEFT JOIN amenity a ON ba.Amenity_ID = a.Amenity_ID
                 LEFT JOIN bookingservice bs ON b.Booking_ID = bs.Booking_ID
                 LEFT JOIN service s ON bs.Service_ID = s.Service_ID
                 LEFT JOIN payment p ON b.Booking_ID = p.Booking_ID
                 WHERE b.Booking_ID = ? AND b.Cust_ID = ?
                 GROUP BY b.Booking_ID";

$bookingResult = executeQuery($bookingQuery, [$bookingId, $_SESSION['cust_id']], 'ii');

if (!$bookingResult) {
    header('Location: bookings.php');
    exit();
}

$booking = $bookingResult[0];
$checkinDate = new DateTime($booking['Booking_IN']);
$checkoutDate = new DateTime($booking['Booking_Out']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Paradise Resort</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/booking.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar dashboard-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-palm-tree"></i> Paradise Resort</h2>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="bookings.php" class="nav-link">My Bookings</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="../index.php" class="nav-link">Home</a>
                <div class="user-dropdown">
                    <button class="user-dropdown-btn">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($customer['Cust_FN']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Confirmation Content -->
    <div class="confirmation-container">
        <div class="container">
            <!-- Success Message -->
            <div class="confirmation-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Booking Confirmed!</h1>
                <p>Thank you for choosing Paradise Resort. Your booking has been successfully created.</p>
                <div class="booking-reference">
                    <span>Booking Reference: <strong>#<?php echo str_pad($booking['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></strong></span>
                </div>
            </div>

            <!-- Booking Details Card -->
            <div class="confirmation-details">
                <div class="details-card">
                    <div class="card-header">
                        <h2><i class="fas fa-receipt"></i> Booking Details</h2>
                        <span class="status-badge <?php echo strtolower($booking['Booking_Status']); ?>">
                            <?php echo $booking['Booking_Status']; ?>
                        </span>
                    </div>

                    <div class="card-content">
                        <div class="detail-sections">
                            <!-- Guest Information -->
                            <div class="detail-section">
                                <h3><i class="fas fa-user"></i> Guest Information</h3>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="label">Name:</span>
                                        <span class="value"><?php echo htmlspecialchars($customer['Cust_FN'] . ' ' . $customer['Cust_LN']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Email:</span>
                                        <span class="value"><?php echo htmlspecialchars($customer['Cust_Email']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Phone:</span>
                                        <span class="value"><?php echo htmlspecialchars($customer['Cust_Phone']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Number of Guests:</span>
                                        <span class="value"><?php echo $booking['Guests']; ?> guest<?php echo $booking['Guests'] > 1 ? 's' : ''; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stay Information -->
                            <div class="detail-section">
                                <h3><i class="fas fa-bed"></i> Stay Information</h3>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="label">Room Type:</span>
                                        <span class="value"><?php echo htmlspecialchars($booking['Room_Type']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Check-in:</span>
                                        <span class="value"><?php echo $checkinDate->format('F j, Y - g:i A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Check-out:</span>
                                        <span class="value"><?php echo $checkoutDate->format('F j, Y - g:i A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Duration:</span>
                                        <span class="value"><?php echo $checkinDate->diff($checkoutDate)->days; ?> night<?php echo $checkinDate->diff($checkoutDate)->days > 1 ? 's' : ''; ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($booking['amenities'] || $booking['services']): ?>
                            <!-- Additional Services -->
                            <div class="detail-section">
                                <h3><i class="fas fa-star"></i> Additional Services</h3>
                                <div class="services-list">
                                    <?php if ($booking['amenities']): ?>
                                    <div class="service-group">
                                        <strong><i class="fas fa-star"></i> Amenities:</strong>
                                        <span><?php echo htmlspecialchars($booking['amenities']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($booking['services']): ?>
                                    <div class="service-group">
                                        <strong><i class="fas fa-concierge-bell"></i> Services:</strong>
                                        <span><?php echo htmlspecialchars($booking['services']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Payment Information -->
                            <div class="detail-section">
                                <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                                <div class="payment-summary">
                                    <div class="payment-item">
                                        <span>Total Amount:</span>
                                        <span class="amount">₱<?php echo number_format($booking['Booking_Cost'], 2); ?></span>
                                    </div>
                                    <?php if ($booking['Booking_Status'] === 'Pending'): ?>
                                    <div class="payment-status pending">
                                        <i class="fas fa-clock"></i>
                                        <span>Payment pending - Please complete payment to confirm your booking</span>
                                    </div>
                                    <?php else: ?>
                                    <div class="payment-status paid">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Payment completed on <?php echo date('F j, Y', strtotime($booking['Payment_Date'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="confirmation-actions">
                <?php if ($booking['Booking_Status'] === 'Pending'): ?>
                <a href="payment.php?booking_id=<?php echo $booking['Booking_ID']; ?>" class="btn btn-primary">
                    <i class="fas fa-credit-card"></i> Complete Payment
                </a>
                <?php endif; ?>
                <a href="bookings.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> View All Bookings
                </a>
                <button onclick="window.print()" class="btn btn-outline">
                    <i class="fas fa-print"></i> Print Confirmation
                </button>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Back to Dashboard
                </a>
            </div>

            <!-- Important Information -->
            <div class="important-info">
                <h3><i class="fas fa-info-circle"></i> Important Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <h4>Check-in Policy</h4>
                        <p>Check-in time is 4:00 PM. Early check-in may be available upon request and subject to availability.</p>
                    </div>
                    <div class="info-item">
                        <h4>Check-out Policy</h4>
                        <p>Check-out time is 12:00 PM. Late check-out may be arranged with additional charges.</p>
                    </div>
                    <div class="info-item">
                        <h4>Cancellation Policy</h4>
                        <p>Free cancellation up to 48 hours before check-in. Cancellations within 48 hours are subject to charges.</p>
                    </div>
                    <div class="info-item">
                        <h4>Contact Information</h4>
                        <p>For any questions or special requests, please contact us at (123) 456-7890 or email info@paradiseresort.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <style>
        .confirmation-container {
            margin-top: 80px;
            min-height: 100vh;
            background: #f8f9fa;
            padding: 2rem 0;
        }

        .confirmation-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .success-icon {
            font-size: 4rem;
            color: #4a9960;
            margin-bottom: 1rem;
        }

        .confirmation-header h1 {
            color: #2c5530;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .confirmation-header p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1.5rem;
        }

        .booking-reference {
            background: #e8f5e8;
            padding: 1rem 2rem;
            border-radius: 25px;
            display: inline-block;
            color: #2c5530;
            font-weight: 600;
        }

        .confirmation-details {
            margin-bottom: 3rem;
        }

        .details-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #4a9960, #2c5530);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-content {
            padding: 2rem;
        }

        .detail-sections {
            display: grid;
            gap: 2rem;
        }

        .detail-section h3 {
            color: #2c5530;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .label {
            font-weight: 500;
            color: #666;
            font-size: 0.9rem;
        }

        .value {
            font-weight: 600;
            color: #333;
        }

        .services-list {
            display: grid;
            gap: 1rem;
        }

        .service-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .service-group strong {
            color: #4a9960;
            min-width: 120px;
        }

        .payment-summary {
            display: grid;
            gap: 1rem;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .amount {
            color: #2c5530;
            font-size: 1.3rem;
        }

        .payment-status {
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .payment-status.pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .payment-status.paid {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .confirmation-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .important-info {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .important-info h3 {
            color: #2c5530;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item h4 {
            color: #4a9960;
            margin-bottom: 0.5rem;
        }

        .info-item p {
            color: #666;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .confirmation-header h1 {
                font-size: 2rem;
            }

            .card-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .confirmation-actions {
                flex-direction: column;
                align-items: center;
            }

            .confirmation-actions .btn {
                width: 100%;
                max-width: 300px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .navbar,
            .confirmation-actions {
                display: none !important;
            }
            
            .confirmation-container {
                margin-top: 0;
            }
        }
    </style>
</body>
</html>
