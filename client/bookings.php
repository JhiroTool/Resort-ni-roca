<?php
/**
 * My Bookings Page
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

// Get customer bookings
$customerId = $_SESSION['cust_id'];
$bookingsQuery = "SELECT b.*, r.Room_Type, r.Room_Rate, r.Room_Cap,
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
                  WHERE b.Cust_ID = ?
                  GROUP BY b.Booking_ID
                  ORDER BY b.Booking_IN DESC";

$bookings = executeQuery($bookingsQuery, [$customerId], 'i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Paradise Resort</title>
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
                <a href="bookings.php" class="nav-link active">My Bookings</a>
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

    <!-- Bookings Content -->
    <div class="bookings-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <div class="header-content">
                    <div class="breadcrumb">
                        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <span><i class="fas fa-chevron-right"></i></span>
                        <span>My Bookings</span>
                    </div>
                    <h1><i class="fas fa-calendar-check"></i> My Bookings</h1>
                    <p>Manage your current and past reservations</p>
                </div>
                <div class="header-actions">
                    <a href="book_room.php" class="btn btn-light">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <div class="bookings-section">
            <div class="container">
                <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <div class="empty-content">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No bookings yet</h3>
                        <p>You haven't made any bookings yet. Start planning your perfect getaway!</p>
                        <a href="book_room.php" class="btn btn-primary">Book Your First Stay</a>
                    </div>
                </div>
                <?php else: ?>
                
                <!-- Filter Tabs -->
                <div class="booking-filters">
                    <button class="filter-btn active" data-filter="all">All Bookings</button>
                    <button class="filter-btn" data-filter="upcoming">Upcoming</button>
                    <button class="filter-btn" data-filter="pending">Pending Payment</button>
                    <button class="filter-btn" data-filter="completed">Completed</button>
                </div>

                <!-- Bookings Grid -->
                <div class="bookings-grid">
                    <?php foreach ($bookings as $booking): 
                        $checkinDate = new DateTime($booking['Booking_IN']);
                        $checkoutDate = new DateTime($booking['Booking_Out']);
                        $today = new DateTime();
                        $isUpcoming = $checkinDate > $today;
                        $isPast = $checkoutDate < $today;
                        
                        // Determine booking category for filtering
                        $category = 'completed';
                        if ($booking['Booking_Status'] === 'Pending') {
                            $category = 'pending';
                        } elseif ($isUpcoming && $booking['Booking_Status'] === 'Paid') {
                            $category = 'upcoming';
                        }
                    ?>
                    <div class="booking-item" data-category="<?php echo $category; ?>">
                        <div class="booking-card-full">
                            <div class="booking-header">
                                <div class="booking-title">
                                    <h3><?php echo htmlspecialchars($booking['Room_Type']); ?></h3>
                                    <span class="booking-id">#<?php echo str_pad($booking['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="booking-status">
                                    <span class="status-badge <?php echo strtolower($booking['Booking_Status']); ?>">
                                        <?php echo $booking['Booking_Status']; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="booking-details">
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <div>
                                            <strong>Check-in</strong>
                                            <span><?php echo $checkinDate->format('M d, Y'); ?></span>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <div>
                                            <strong>Check-out</strong>
                                            <span><?php echo $checkoutDate->format('M d, Y'); ?></span>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <div>
                                            <strong>Guests</strong>
                                            <span><?php echo $booking['Guests']; ?> guest<?php echo $booking['Guests'] > 1 ? 's' : ''; ?></span>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <div>
                                            <strong>Duration</strong>
                                            <span><?php echo $checkinDate->diff($checkoutDate)->days; ?> night<?php echo $checkinDate->diff($checkoutDate)->days > 1 ? 's' : ''; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($booking['amenities']): ?>
                                <div class="booking-extras">
                                    <div class="extra-section">
                                        <strong><i class="fas fa-star"></i> Amenities:</strong>
                                        <span><?php echo htmlspecialchars($booking['amenities']); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($booking['services']): ?>
                                <div class="booking-extras">
                                    <div class="extra-section">
                                        <strong><i class="fas fa-concierge-bell"></i> Services:</strong>
                                        <span><?php echo htmlspecialchars($booking['services']); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="booking-footer">
                                <div class="booking-total">
                                    <span class="total-label">Total Cost:</span>
                                    <span class="total-amount">₱<?php echo number_format($booking['Booking_Cost'], 2); ?></span>
                                </div>
                                
                                <div class="booking-actions">
                                    <?php if ($booking['Booking_Status'] === 'Pending'): ?>
                                        <a href="payment.php?booking_id=<?php echo $booking['Booking_ID']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </a>
                                        <button class="btn btn-danger btn-sm" onclick="cancelBooking(<?php echo $booking['Booking_ID']; ?>)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" onclick="viewBookingDetails(<?php echo $booking['Booking_ID']; ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <?php if ($isPast): ?>
                                        <a href="feedback.php?booking_id=<?php echo $booking['Booking_ID']; ?>" class="btn btn-outline btn-sm">
                                            <i class="fas fa-star"></i> Leave Review
                                        </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="modal" style="display: none;">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Booking Details</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const filterBtns = document.querySelectorAll('.filter-btn');
            const bookingItems = document.querySelectorAll('.booking-item');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Update active filter button
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const filter = this.dataset.filter;

                    // Filter booking items
                    bookingItems.forEach(item => {
                        if (filter === 'all' || item.dataset.category === filter) {
                            item.style.display = 'block';
                            item.style.animation = 'fadeIn 0.3s ease';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });

        function viewBookingDetails(bookingId) {
            // Fetch booking details via AJAX
            fetch(`get_booking_details.php?booking_id=${bookingId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalBody').innerHTML = data.html;
                        document.getElementById('bookingModal').style.display = 'flex';
                    } else {
                        alert('Failed to load booking details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading booking details');
                });
        }

        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ booking_id: bookingId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking cancelled successfully');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to cancel booking');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the booking');
                });
            }
        }

        function closeModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <style>
        /* Additional styles for bookings page */
        .bookings-container {
            margin-top: 80px;
            min-height: 100vh;
            background: #f8f9fa;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-light {
            background: white;
            color: #4a9960;
            border: 2px solid white;
        }

        .btn-light:hover {
            background: #f8f9fa;
        }

        .empty-state {
            padding: 4rem 0;
            text-align: center;
        }

        .empty-content i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-content h3 {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .empty-content p {
            color: #888;
            margin-bottom: 2rem;
        }

        .bookings-section {
            padding: 2rem 0;
        }

        .booking-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: #4a9960;
            color: white;
            border-color: #4a9960;
        }

        .bookings-grid {
            display: grid;
            gap: 1.5rem;
        }

        .booking-card-full {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .booking-card-full:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .booking-title h3 {
            color: #2c5530;
            margin-bottom: 0.25rem;
        }

        .booking-id {
            color: #666;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.paid {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .booking-details {
            padding: 1rem 1.5rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .detail-item i {
            color: #4a9960;
            width: 16px;
        }

        .detail-item div strong {
            display: block;
            font-size: 0.85rem;
            color: #666;
            font-weight: 500;
        }

        .detail-item div span {
            color: #333;
            font-weight: 600;
        }

        .booking-extras {
            margin-bottom: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .extra-section {
            margin-bottom: 0.5rem;
        }

        .extra-section strong {
            color: #4a9960;
            margin-right: 0.5rem;
        }

        .booking-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #f0f0f0;
        }

        .booking-total {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .total-label {
            font-size: 0.9rem;
            color: #666;
        }

        .total-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c5530;
        }

        .booking-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #4a9960;
            color: #4a9960;
        }

        .btn-outline:hover {
            background: #4a9960;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        .modal-overlay {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .modal-header h3 {
            margin: 0;
            color: #2c5530;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: #666;
        }

        .modal-body {
            padding: 1.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .booking-header,
            .booking-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .booking-actions {
                width: 100%;
            }

            .booking-actions .btn {
                flex: 1;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
