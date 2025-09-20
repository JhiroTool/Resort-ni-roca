<?php
/**
 * Customer Dashboard
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
    header('Location: login.php?error=session_expired');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Paradise Resort</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
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

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="dashboard-header">
            <div class="container">
                <div class="welcome-section">
                    <h1>Welcome back, <?php echo htmlspecialchars($customer['Cust_FN']); ?>!</h1>
                    <p>Ready to plan your next amazing getaway?</p>
                </div>
                <div class="quick-actions">
                    <a href="book_room.php" class="quick-action-btn primary">
                        <i class="fas fa-plus"></i>
                        New Booking
                    </a>
                    <a href="bookings.php" class="quick-action-btn secondary">
                        <i class="fas fa-calendar"></i>
                        View Bookings
                    </a>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalBookings">Loading...</h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="upcomingBookings">Loading...</h3>
                            <p>Upcoming Stays</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="loyaltyPoints">0</h3>
                            <p>Loyalty Points</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalSpent">Loading...</h3>
                            <p>Total Spent</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="dashboard-section">
            <div class="container">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="bookings.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="bookings-list" id="recentBookings">
                    <!-- Bookings will be loaded here -->
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Loading your bookings...
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Book Section -->
        <div class="dashboard-section">
            <div class="container">
                <div class="quick-book-section">
                    <div class="quick-book-content">
                        <h2>Ready for Another Adventure?</h2>
                        <p>Book your next stay with us and enjoy exclusive member benefits!</p>
                        <div class="room-types-quick">
                            <div class="room-type-card">
                                <img src="../assets/images/deluxe-room.jpg" alt="Deluxe Room">
                                <div class="room-info">
                                    <h4>Deluxe Room</h4>
                                    <p>From ₱2,500/night</p>
                                </div>
                                <a href="book_room.php?type=deluxe" class="book-btn">Book Now</a>
                            </div>
                            <div class="room-type-card">
                                <img src="../assets/images/pool-room.jpg" alt="Pool Room">
                                <div class="room-info">
                                    <h4>Pool Room</h4>
                                    <p>From ₱1,500/night</p>
                                </div>
                                <a href="book_room.php?type=pool" class="book-btn">Book Now</a>
                            </div>
                            <div class="room-type-card">
                                <img src="../assets/images/family-room.jpg" alt="Family Room">
                                <div class="room-info">
                                    <h4>Family Room</h4>
                                    <p>From ₱1,500/night</p>
                                </div>
                                <a href="book_room.php?type=family" class="book-btn">Book Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer dashboard-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-palm-tree"></i> Paradise Resort</h3>
                    <p>Your perfect getaway destination</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="bookings.php">My Bookings</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="../index.php">Home</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="feedback.php">Feedback</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Load customer data
        const customerId = <?php echo $_SESSION['cust_id']; ?>;
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData(customerId);
        });
        
        function loadDashboardData(customerId) {
            // This would typically fetch data from an API
            // For now, we'll use placeholder data
            
            setTimeout(() => {
                // Update stats
                document.getElementById('totalBookings').textContent = '5';
                document.getElementById('upcomingBookings').textContent = '1';
                document.getElementById('loyaltyPoints').textContent = '2,450';
                document.getElementById('totalSpent').textContent = '₱15,300';
                
                // Load recent bookings
                loadRecentBookings();
            }, 1000);
        }
        
        function loadRecentBookings() {
            const bookingsContainer = document.getElementById('recentBookings');
            
            // Placeholder booking data
            const bookings = [
                {
                    id: 'BK001',
                    roomType: 'Deluxe Room',
                    checkIn: '2025-06-25',
                    checkOut: '2025-06-27',
                    status: 'Confirmed',
                    amount: '₱5,000'
                }
            ];
            
            if (bookings.length === 0) {
                bookingsContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No bookings yet</h3>
                        <p>Start planning your perfect getaway!</p>
                        <a href="book_room.php" class="btn btn-primary">Book Your First Stay</a>
                    </div>
                `;
            } else {
                bookingsContainer.innerHTML = bookings.map(booking => `
                    <div class="booking-card">
                        <div class="booking-info">
                            <div class="booking-header">
                                <h4>${booking.roomType}</h4>
                                <span class="booking-id">#${booking.id}</span>
                            </div>
                            <div class="booking-details">
                                <p><i class="fas fa-calendar"></i> ${booking.checkIn} - ${booking.checkOut}</p>
                                <p><i class="fas fa-dollar-sign"></i> ${booking.amount}</p>
                            </div>
                        </div>
                        <div class="booking-status">
                            <span class="status-badge ${booking.status.toLowerCase()}">${booking.status}</span>
                        </div>
                    </div>
                `).join('');
            }
        }
    </script>
</body>
</html>
