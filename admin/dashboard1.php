<?php
/**
 * Admin Dashboard - Paradise Resort Management System
 * Comprehensive resort management and analytics dashboard
 */

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in - SECURITY CHECK
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username']) || !isset($_SESSION['admin_role'])) {
    // Redirect to admin login page
    header("Location: ../login.php?admin=1&error=access_denied");
    exit();
}

// Verify session is still valid (additional security)
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    // Session corrupted, destroy and redirect
    session_destroy();
    header("Location: ../login.php?admin=1&error=session_expired");
    exit();
}

// Database configuration
class DatabaseManager {
    private $host = 'localhost';
    private $dbname = 'guest_accommodation_system';
    private $username = 'root';
    private $password = '';
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // Log the error and fallback to demo data
            error_log("Database connection failed: " . $e->getMessage());
            $this->pdo = null;
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function isConnected() {
        return $this->pdo !== null;
    }
}

// Initialize database connection
$db = new DatabaseManager();
$pdo = $db->getConnection();

// Dashboard statistics with database integration
function getDashboardStats($pdo) {
    $stats = [];
    
    if ($pdo) {
        try {
            // Total bookings
            $stmt = $pdo->query("SELECT COUNT(*) FROM booking");
            $stats['totalBookings'] = $stmt->fetchColumn();
            
            // Pending bookings
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE Booking_Status = ?");
            $stmt->execute(['Pending']);
            $stats['pendingBookings'] = $stmt->fetchColumn();
            
            // Total customers
            $stmt = $pdo->query("SELECT COUNT(*) FROM customer WHERE is_banned = 0");
            $stats['totalCustomers'] = $stmt->fetchColumn();
            
            // Total revenue from paid bookings
            $stmt = $pdo->prepare("SELECT SUM(Booking_Cost) FROM booking WHERE Booking_Status = ?");
            $stmt->execute(['Paid']);
            $stats['totalRevenue'] = $stmt->fetchColumn() ?: 0;
            
            // Available rooms
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM room WHERE Room_Status = ?");
            $stmt->execute(['Available']);
            $stats['availableRooms'] = $stmt->fetchColumn();
            
        } catch(PDOException $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return getFallbackStats();
        }
    } else {
        return getFallbackStats();
    }
    
    return $stats;
}

function getFallbackStats() {
    return [
        'totalBookings' => 24,
        'pendingBookings' => 5,
        'totalCustomers' => 18,
        'totalRevenue' => 45000,
        'availableRooms' => 3
    ];
}

// Get recent bookings with customer and room information
function getRecentBookings($pdo, $limit = 10) {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    b.Booking_ID,
                    c.Cust_FN,
                    c.Cust_LN,
                    r.Room_Type,
                    DATE(b.Booking_IN) as Booking_Date,
                    b.Booking_Status,
                    b.Booking_Cost as Total_Cost,
                    b.Guests,
                    b.Booking_IN,
                    b.Booking_Out,
                    'REAL_DATA' as data_source
                FROM booking b
                JOIN customer c ON b.Cust_ID = c.Cust_ID
                JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
                JOIN room r ON br.Room_ID = r.Room_ID
                ORDER BY b.Booking_IN DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If we got results, return them
            if (!empty($result)) {
                return $result;
            }
        } catch(PDOException $e) {
            error_log("Recent bookings error: " . $e->getMessage());
        }
    }
    
    // Return fallback data with source indicator
    $fallbackData = getFallbackBookings();
    foreach ($fallbackData as &$booking) {
        $booking['data_source'] = 'FALLBACK_DATA';
    }
    return $fallbackData;
}

function getFallbackBookings() {
    // Fallback data when database is unavailable - shows real-looking demo data
    return [
        [
            'Cust_FN' => 'Jhiro Ramir',
            'Cust_LN' => 'Tool',
            'Room_Type' => 'Pool',
            'Booking_Date' => '2025-06-26',
            'Booking_Status' => 'Paid',
            'Total_Cost' => 1600,
            'Guests' => 5
        ],
        [
            'Cust_FN' => 'Timothy',
            'Cust_LN' => 'Barachael',
            'Room_Type' => 'Deluxe',
            'Booking_Date' => '2025-06-25',
            'Booking_Status' => 'Paid',
            'Total_Cost' => 2600,
            'Guests' => 10
        ],
        [
            'Cust_FN' => 'Carl',
            'Cust_LN' => 'Rocafor',
            'Room_Type' => 'Pool',
            'Booking_Date' => '2025-06-23',
            'Booking_Status' => 'Paid',
            'Total_Cost' => 2000,
            'Guests' => 5
        ]
    ];
}

// Get revenue analytics data for charts
function getRevenueAnalytics($pdo) {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(Payment_Date) as date,
                    SUM(Payment_Amount) as revenue
                FROM payment p
                JOIN booking b ON p.Booking_ID = b.Booking_ID
                WHERE Payment_Date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(Payment_Date)
                ORDER BY date DESC
                LIMIT 30
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Revenue analytics error: " . $e->getMessage());
            return getFallbackRevenueData();
        }
    } else {
        return getFallbackRevenueData();
    }
}

function getFallbackRevenueData() {
    $data = [];
    for ($i = 29; $i >= 0; $i--) {
        $data[] = [
            'date' => date('Y-m-d', strtotime("-$i days")),
            'revenue' => rand(500, 5000)
        ];
    }
    return $data;
}

// Room availability analytics
function getRoomAnalytics($pdo) {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    Room_Type,
                    Room_Status,
                    COUNT(*) as count,
                    Room_Rate
                FROM room
                GROUP BY Room_Type, Room_Status, Room_Rate
                ORDER BY Room_Type
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Room analytics error: " . $e->getMessage());
            return getFallbackRoomData();
        }
    } else {
        return getFallbackRoomData();
    }
}

function getFallbackRoomData() {
    return [
        ['Room_Type' => 'Deluxe', 'Room_Status' => 'Unavailable', 'count' => 1, 'Room_Rate' => 2500],
        ['Room_Type' => 'Pool', 'Room_Status' => 'Available', 'count' => 1, 'Room_Rate' => 1500],
        ['Room_Type' => 'Family', 'Room_Status' => 'Available', 'count' => 1, 'Room_Rate' => 1500]
    ];
}

// Top customers analytics
function getTopCustomers($pdo, $limit = 5) {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.Cust_FN,
                    c.Cust_LN,
                    c.Cust_Email,
                    COUNT(b.Booking_ID) as total_bookings,
                    SUM(b.Booking_Cost) as total_spent
                FROM customer c
                JOIN booking b ON c.Cust_ID = b.Cust_ID
                WHERE c.is_banned = 0 AND b.Booking_Status = 'Paid'
                GROUP BY c.Cust_ID
                ORDER BY total_spent DESC, total_bookings DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Top customers error: " . $e->getMessage());
            return getFallbackCustomerData();
        }
    } else {
        return getFallbackCustomerData();
    }
}

function getFallbackCustomerData() {
    return [
        ['Cust_FN' => 'Jhiro Ramir', 'Cust_LN' => 'Tool', 'Cust_Email' => 'jhiroramir@gmail.com', 'total_bookings' => 8, 'total_spent' => 25200],
        ['Cust_FN' => 'Timothy', 'Cust_LN' => 'Barachael', 'Cust_Email' => 'timo@gmail.com', 'total_bookings' => 2, 'total_spent' => 6900],
        ['Cust_FN' => 'Carl', 'Cust_LN' => 'Rocafor', 'Cust_Email' => 'carl@gmail.com', 'total_bookings' => 1, 'total_spent' => 2000]
    ];
}

// Get amenities and services data
function getAmenitiesAndServices($pdo) {
    $data = ['amenities' => [], 'services' => []];
    
    if ($pdo) {
        try {
            // Get amenities
            $stmt = $pdo->query("SELECT * FROM amenity ORDER BY Amenity_Name");
            $data['amenities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get services
            $stmt = $pdo->query("SELECT * FROM service ORDER BY Service_Name");
            $data['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log("Amenities/Services error: " . $e->getMessage());
            return getFallbackAmenitiesServices();
        }
    } else {
        return getFallbackAmenitiesServices();
    }
    
    return $data;
}

function getFallbackAmenitiesServices() {
    return [
        'amenities' => [
            ['Amenity_ID' => 1, 'Amenity_Name' => 'Wi-Fi', 'Amenity_Desc' => 'High-speed internet', 'Amenity_Cost' => 500],
            ['Amenity_ID' => 2, 'Amenity_Name' => 'Breakfast', 'Amenity_Desc' => 'Continental breakfast', 'Amenity_Cost' => 1000],
            ['Amenity_ID' => 3, 'Amenity_Name' => 'Spa', 'Amenity_Desc' => 'Relaxing spa services', 'Amenity_Cost' => 800],
            ['Amenity_ID' => 4, 'Amenity_Name' => 'ATV', 'Amenity_Desc' => 'Adventure ATV rides', 'Amenity_Cost' => 1500]
        ],
        'services' => [
            ['Service_ID' => 1, 'Service_Name' => 'Pick up from home', 'Service_Desc' => 'Transportation service', 'Service_Cost' => 1000]
        ]
    ];
}

// Execute data fetching
$dashboardStats = getDashboardStats($pdo);
$recentBookings = getRecentBookings($pdo, 8);
$revenueData = getRevenueAnalytics($pdo);
$roomData = getRoomAnalytics($pdo);
$topCustomers = getTopCustomers($pdo);
$amenitiesServices = getAmenitiesAndServices($pdo);

// Extract individual stats for template use
$totalBookings = $dashboardStats['totalBookings'];
$pendingBookings = $dashboardStats['pendingBookings'];
$totalCustomers = $dashboardStats['totalCustomers'];
$totalRevenue = $dashboardStats['totalRevenue'];
$availableRooms = $dashboardStats['availableRooms'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Paradise Resort Management</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-dashboard">
        <!-- Sidebar Navigation -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <h2>Resort Admin</h2>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <a href="#dashboard" class="menu-item active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#bookings" class="menu-item" data-section="bookings">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                    <span class="badge" id="pendingBadge"><?php echo $pendingBookings > 0 ? $pendingBookings : ''; ?></span>
                </a>
                <a href="#rooms" class="menu-item" data-section="rooms">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="#customers" class="menu-item" data-section="customers">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="#amenities" class="menu-item" data-section="amenities">
                    <i class="fas fa-star"></i>
                    <span>Amenities</span>
                </a>
                <a href="#services" class="menu-item" data-section="services">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Services</span>
                </a>
                <a href="#reports" class="menu-item" data-section="reports">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports</span>
                </a>
                <a href="#settings" class="menu-item" data-section="settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
            
            <div class="sidebar-footer">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-details">
                        <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? $_SESSION['admin_email'] ?? 'Administrator'); ?></span>
                        <span class="admin-role"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Super Admin'); ?></span>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="content-header">
                <div class="header-left">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back! Here's what's happening at Paradise Resort today.</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <div class="date" id="currentDate"></div>
                        <div class="time" id="currentTime"></div>
                    </div>
                    <button class="refresh-btn" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Dashboard Section -->
            <div class="section-content active" id="dashboard-section">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card bookings">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totalBookings); ?></h3>
                            <p>Total Bookings</p>
                            <span class="stat-trend up">
                                <i class="fas fa-arrow-up"></i> +12% this month
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card revenue">
                        <div class="stat-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₱<?php echo number_format($totalRevenue, 2); ?></h3>
                            <p>Total Revenue</p>
                            <span class="stat-trend up">
                                <i class="fas fa-arrow-up"></i> +8% this month
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card customers">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totalCustomers); ?></h3>
                            <p>Total Customers</p>
                            <span class="stat-trend up">
                                <i class="fas fa-arrow-up"></i> +15% this month
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card rooms">
                        <div class="stat-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $availableRooms; ?></h3>
                            <p>Available Rooms</p>
                            <span class="stat-trend neutral">
                                <i class="fas fa-check"></i> Ready for booking
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Charts and Tables Grid -->
                <div class="content-grid">
                    <!-- Recent Bookings -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock"></i> Recent Bookings</h3>
                            <a href="#bookings" class="view-all" data-section="bookings">View All</a>
                        </div>
                        <div class="card-content">
                            <div class="bookings-list">
                                <?php if (!empty($recentBookings) && is_array($recentBookings)): ?>
                                    <?php foreach ($recentBookings as $booking): ?>
                                    <div class="booking-item">
                                        <div class="booking-info">
                                            <div class="customer-name">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars(($booking['Cust_FN'] ?? '') . ' ' . ($booking['Cust_LN'] ?? 'Unknown Customer')); ?>
                                            </div>
                                            <div class="booking-details">
                                                <span class="room-type"><?php echo htmlspecialchars($booking['Room_Type'] ?? 'N/A'); ?></span>
                                                <span class="booking-date"><?php echo $booking['Booking_Date'] ? date('M j, Y', strtotime($booking['Booking_Date'])) : 'N/A'; ?></span>
                                                <span class="guest-count"><?php echo ($booking['Guests'] ?? 1) . ' guest' . (($booking['Guests'] ?? 1) > 1 ? 's' : ''); ?></span>
                                            </div>
                                        </div>
                                        <div class="booking-status">
                                            <span class="status-badge <?php echo strtolower($booking['Booking_Status'] ?? 'pending'); ?>">
                                                <?php echo htmlspecialchars($booking['Booking_Status'] ?? 'Pending'); ?>
                                            </span>
                                            <div class="booking-amount">₱<?php echo number_format($booking['Total_Cost'] ?? 0, 2); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-calendar-times"></i>
                                        <p>No recent bookings found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Chart -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-area"></i> Revenue Overview</h3>
                            <div class="chart-controls">
                                <select id="revenueperiod">
                                    <option value="7">Last 7 days</option>
                                    <option value="30">Last 30 days</option>
                                    <option value="90">Last 90 days</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-content">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Analytics Row -->
                <div class="content-grid" style="margin-top: 2rem;">
                    <!-- Room Availability Chart -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-bed"></i> Room Availability</h3>
                            <a href="#rooms" class="view-all" data-section="rooms">Manage Rooms</a>
                        </div>
                        <div class="card-content">
                            <canvas id="roomChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Booking Status Distribution -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-pie"></i> Booking Status</h3>
                            <span class="status-summary">
                                <?php echo $pendingBookings; ?> pending of <?php echo $totalBookings; ?> total
                            </span>
                        </div>
                        <div class="card-content">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Top Customers & Amenities Row -->
                <div class="content-grid" style="margin-top: 2rem;">
                    <!-- Top Customers -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-star"></i> Top Customers</h3>
                            <a href="#customers" class="view-all" data-section="customers">View All</a>
                        </div>
                        <div class="card-content">
                            <div class="customers-list">
                                <?php if (!empty($topCustomers) && is_array($topCustomers)): ?>
                                    <?php foreach ($topCustomers as $customer): ?>
                                    <div class="customer-item">
                                        <div class="customer-info">
                                            <div class="customer-name">
                                                <i class="fas fa-user-circle"></i>
                                                <?php echo htmlspecialchars(($customer['Cust_FN'] ?? '') . ' ' . ($customer['Cust_LN'] ?? '')); ?>
                                            </div>
                                            <div class="customer-email">
                                                <?php echo htmlspecialchars($customer['Cust_Email'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <div class="customer-stats">
                                            <div class="bookings-count">
                                                <?php echo $customer['total_bookings'] ?? 0; ?> booking<?php echo ($customer['total_bookings'] ?? 0) != 1 ? 's' : ''; ?>
                                            </div>
                                            <div class="customer-spent">
                                                ₱<?php echo number_format($customer['total_spent'] ?? 0, 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-users"></i>
                                        <p>No customer data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Amenities & Services -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-concierge-bell"></i> Amenities & Services</h3>
                            <a href="#amenities" class="view-all" data-section="amenities">Manage All</a>
                        </div>
                        <div class="card-content">
                            <div class="amenities-services">
                                <div class="service-section">
                                    <h4><i class="fas fa-wifi"></i> Amenities (<?php echo count($amenitiesServices['amenities']); ?>)</h4>
                                    <?php if (!empty($amenitiesServices['amenities'])): ?>
                                        <?php foreach (array_slice($amenitiesServices['amenities'], 0, 3) as $amenity): ?>
                                        <div class="service-item">
                                            <span class="service-name"><?php echo htmlspecialchars($amenity['Amenity_Name'] ?? 'N/A'); ?></span>
                                            <span class="service-price">₱<?php echo number_format($amenity['Amenity_Cost'] ?? 0, 2); ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="service-section">
                                    <h4><i class="fas fa-car"></i> Services (<?php echo count($amenitiesServices['services']); ?>)</h4>
                                    <?php if (!empty($amenitiesServices['services'])): ?>
                                        <?php foreach (array_slice($amenitiesServices['services'], 0, 3) as $service): ?>
                                        <div class="service-item">
                                            <span class="service-name"><?php echo htmlspecialchars($service['Service_Name'] ?? 'N/A'); ?></span>
                                            <span class="service-price">₱<?php echo number_format($service['Service_Cost'] ?? 0, 2); ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="actions-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="actions-grid">
                        <button class="action-btn" onclick="window.location.href='manage_rooms.php'">
                            <i class="fas fa-plus"></i>
                            <span>Add New Room</span>
                        </button>
                        <button class="action-btn" onclick="window.location.href='manage_bookings.php'">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Create Booking</span>
                        </button>
                        <button class="action-btn" onclick="window.location.href='manage_customers.php'">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Customer</span>
                        </button>
                        <button class="action-btn" onclick="window.location.href='reports.php'">
                            <i class="fas fa-file-export"></i>
                            <span>Generate Report</span>
                        </button>
                        <button class="action-btn" onclick="window.location.href='manage_amenities.php'">
                            <i class="fas fa-star"></i>
                            <span>Manage Amenities</span>
                        </button>
                        <button class="action-btn" onclick="window.location.href='settings.php'">
                            <i class="fas fa-cog"></i>
                            <span>System Settings</span>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Other Sections (Hidden by default) -->
            <div class="section-content" id="bookings-section">
                <div class="section-header">
                    <h2><i class="fas fa-calendar-check"></i> Booking Management</h2>
                    <button class="btn btn-primary" onclick="window.location.href='manage_bookings.php'">
                        <i class="fas fa-plus"></i> New Booking
                    </button>
                </div>
                <div class="management-placeholder">
                    <i class="fas fa-tools"></i>
                    <h3>Booking Management</h3>
                    <p>Advanced booking management features will be implemented here.</p>
                    <div class="feature-list">
                        <div class="feature-item"><i class="fas fa-check"></i> View all bookings</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Update booking status</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Cancel bookings</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Export booking reports</div>
                    </div>
                </div>
            </div>

            <div class="section-content" id="rooms-section">
                <div class="section-header">
                    <h2><i class="fas fa-bed"></i> Room Management</h2>
                    <button class="btn btn-primary" onclick="window.location.href='manage_rooms.php'">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </div>
                <div class="management-placeholder">
                    <i class="fas fa-bed"></i>
                    <h3>Room Management</h3>
                    <p>Comprehensive room management system coming soon.</p>
                    <div class="feature-list">
                        <div class="feature-item"><i class="fas fa-check"></i> Add/Edit/Delete rooms</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Room availability calendar</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Maintenance scheduling</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Rate management</div>
                    </div>
                </div>
            </div>

            <div class="section-content" id="customers-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Customer Management</h2>
                    <button class="btn btn-primary" onclick="window.location.href='manage_customers.php'">
                        <i class="fas fa-plus"></i> Add Customer
                    </button>
                </div>
                <div class="management-placeholder">
                    <i class="fas fa-users"></i>
                    <h3>Customer Management</h3>
                    <p>Advanced customer relationship management tools.</p>
                    <div class="feature-list">
                        <div class="feature-item"><i class="fas fa-check"></i> Customer profiles</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Booking history</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Communication logs</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Loyalty programs</div>
                    </div>
                </div>
            </div>

            <div class="section-content" id="amenities-section">
                <div class="section-header">
                    <h2><i class="fas fa-star"></i> Amenities Management</h2>
                    <button class="btn btn-primary" onclick="window.location.href='manage_amenities.php'">
                        <i class="fas fa-plus"></i> Add Amenity
                    </button>
                </div>
                <div class="management-placeholder">
                    <i class="fas fa-star"></i>
                    <h3>Amenities Management</h3>
                    <p>Manage resort amenities and pricing.</p>
                    <div class="feature-list">
                        <div class="feature-item"><i class="fas fa-check"></i> Add/Edit amenities</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Pricing management</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Availability tracking</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Usage analytics</div>
                    </div>
                </div>
            </div>

            <div class="section-content" id="services-section">
                <div class="section-header">
                    <h2><i class="fas fa-concierge-bell"></i> Services Management</h2>
                    <button class="btn btn-primary" onclick="window.location.href='manage_services.php'">
                        <i class="fas fa-plus"></i> Add Service
                    </button>
                </div>
                <div class="management-placeholder">
                    <i class="fas fa-concierge-bell"></i>
                    <h3>Services Management</h3>
                    <p>Manage additional services and staff scheduling.</p>
                    <div class="feature-list">
                        <div class="feature-item"><i class="fas fa-check"></i> Service catalog</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Staff scheduling</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Service requests</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Performance metrics</div>
                    </div>
                </div>
            </div>

            <div class="section-content" id="reports-section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Reports & Analytics</h2>
                    <button class="btn btn-primary" onclick="window.location.href='reports.php'">
                        <i class="fas fa-download"></i> Generate Report
                    </button>
                </div>
                <div class="management-placeholder">
                    <i class="fas fa-chart-line"></i>
                    <h3>Reports & Analytics</h3>
                    <p>Comprehensive reporting and business intelligence.</p>
                    <div class="feature-list">
                        <div class="feature-item"><i class="fas fa-check"></i> Revenue reports</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Occupancy analytics</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Customer insights</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Performance dashboards</div>
                    </div>
                </div>
            </div>

            <div class="section-content" id="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-cog"></i> System Settings</h2>
                    <button class="btn btn-secondary" onclick="window.location.href='settings.php'">
                        <i class="fas fa-database"></i> System Settings
                    </button>
                </div>
                <div class="management-placeholder">
                    <i class="fas fa-cog"></i>
                    <h3>System Settings</h3>
                    <p>Configure resort management system settings.</p>
                    <div class="feature-list">
                        <div class="feature-item"><i class="fas fa-check"></i> User management</div>
                        <div class="feature-item"><i class="fas fa-check"></i> System configuration</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Security settings</div>
                        <div class="feature-item"><i class="fas fa-check"></i> Data backup/restore</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            updateDateTime();
            initializeCharts();
            
            // Update time every second
            setInterval(updateDateTime, 1000);
            
            // Auto-refresh dashboard data every 30 seconds
            setInterval(refreshDashboardData, 30000);
            
            // Add refresh indicator
            addRefreshIndicator();
        });

        function initializeDashboard() {
            // Sidebar toggle functionality
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.admin-sidebar');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });

            // Menu item click handlers
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.getAttribute('data-section');
                    
                    // Redirect to specific pages for management sections
                    if (section === 'bookings') {
                        window.location.href = 'manage_bookings.php';
                    } else if (section === 'rooms') {
                        window.location.href = 'manage_rooms.php';
                    } else if (section === 'customers') {
                        window.location.href = 'manage_customers.php';
                    } else if (section === 'amenities') {
                        window.location.href = 'manage_amenities.php';
                    } else if (section === 'services') {
                        window.location.href = 'manage_services.php';
                    } else if (section === 'reports') {
                        window.location.href = 'reports.php';
                    } else if (section === 'settings') {
                        window.location.href = 'settings.php';
                    } else {
                        // For dashboard, stay on current page and show dashboard section
                        switchSection(section);
                        // Update active menu item
                        menuItems.forEach(mi => mi.classList.remove('active'));
                        this.classList.add('active');
                    }
                });
            });

            // View all links
            const viewAllLinks = document.querySelectorAll('.view-all');
            viewAllLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.getAttribute('data-section');
                    switchSection(section);
                });
            });
        }

        function switchSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.section-content');
            sections.forEach(section => section.classList.remove('active'));
            
            // Show target section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Update header
            updateContentHeader(sectionName);
        }

        function updateContentHeader(sectionName) {
            const headerLeft = document.querySelector('.header-left');
            const headers = {
                'dashboard': { title: 'Dashboard Overview', subtitle: "Welcome back! Here's what's happening at Paradise Resort today." },
                'bookings': { title: 'Booking Management', subtitle: 'Manage all resort bookings and reservations.' },
                'rooms': { title: 'Room Management', subtitle: 'Manage room inventory, availability, and rates.' },
                'customers': { title: 'Customer Management', subtitle: 'View and manage customer profiles and relationships.' },
                'amenities': { title: 'Amenities Management', subtitle: 'Manage resort amenities and services.' },
                'services': { title: 'Services Management', subtitle: 'Manage additional services and staff.' },
                'reports': { title: 'Reports & Analytics', subtitle: 'View comprehensive reports and analytics.' },
                'settings': { title: 'System Settings', subtitle: 'Configure system settings and preferences.' }
            };
            
            const header = headers[sectionName] || headers['dashboard'];
            headerLeft.innerHTML = `
                <h1>${header.title}</h1>
                <p>${header.subtitle}</p>
            `;
        }

        function updateDateTime() {
            const now = new Date();
            const dateElement = document.getElementById('currentDate');
            const timeElement = document.getElementById('currentTime');
            
            if (dateElement && timeElement) {
                dateElement.textContent = now.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                timeElement.textContent = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }
        }

        function initializeCharts() {
            // Revenue Chart with real data
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                // Get revenue data from PHP
                const revenueData = <?php echo json_encode($revenueData); ?>;
                
                // Prepare chart data
                const chartLabels = [];
                const chartData = [];
                const maxDays = 30; // Show last 30 days
                
                // Create array for last 30 days
                for (let i = maxDays - 1; i >= 0; i--) {
                    const date = new Date();
                    date.setDate(date.getDate() - i);
                    const dateString = date.toISOString().split('T')[0];
                    
                    // Find revenue for this date
                    const dayRevenue = revenueData.find(item => item.date === dateString);
                    
                    chartLabels.push(date.toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric' 
                    }));
                    chartData.push(dayRevenue ? parseFloat(dayRevenue.revenue) : 0);
                }
                
                new Chart(revenueCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Revenue (₱)',
                            data: chartData,
                            borderColor: '#4a9960',
                            backgroundColor: 'rgba(74, 153, 96, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#4a9960',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: '#4a9960',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#718096',
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    color: '#718096',
                                    font: {
                                        size: 11
                                    },
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Additional charts can be added here (Room occupancy, Customer analytics, etc.)
            initializeRoomChart();
            initializeBookingStatusChart();
        }
        
        function initializeRoomChart() {
            // Room availability chart
            const roomData = <?php echo json_encode($roomData); ?>;
            const roomChartCtx = document.getElementById('roomChart');
            
            if (roomChartCtx && roomData.length > 0) {
                const roomTypes = [...new Set(roomData.map(room => room.Room_Type))];
                const availableRooms = roomTypes.map(type => {
                    const available = roomData.filter(room => room.Room_Type === type && room.Room_Status === 'Available');
                    return available.reduce((sum, room) => sum + parseInt(room.count), 0);
                });
                const unavailableRooms = roomTypes.map(type => {
                    const unavailable = roomData.filter(room => room.Room_Type === type && room.Room_Status === 'Unavailable');
                    return unavailable.reduce((sum, room) => sum + parseInt(room.count), 0);
                });
                
                new Chart(roomChartCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: roomTypes,
                        datasets: [{
                            label: 'Available',
                            data: availableRooms,
                            backgroundColor: [
                                'rgba(74, 153, 96, 0.8)',
                                'rgba(52, 152, 219, 0.8)',
                                'rgba(155, 89, 182, 0.8)',
                                'rgba(241, 196, 15, 0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            }
        }
        
        function initializeBookingStatusChart() {
            // Booking status distribution
            const bookingStats = {
                paid: <?php echo json_encode($dashboardStats['totalBookings'] - $dashboardStats['pendingBookings']); ?>,
                pending: <?php echo json_encode($dashboardStats['pendingBookings']); ?>
            };
            
            const statusChartCtx = document.getElementById('statusChart');
            
            if (statusChartCtx) {
                new Chart(statusChartCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: ['Paid', 'Pending'],
                        datasets: [{
                            data: [bookingStats.paid, bookingStats.pending],
                            backgroundColor: [
                                'rgba(72, 187, 120, 0.8)',
                                'rgba(255, 193, 7, 0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            }
        }

        function openModal(modalId) {
            showNotification('Feature coming soon! ' + modalId.replace('Modal', '') + ' functionality will be implemented.', 'info');
        }

        function generateReport() {
            showNotification('Generating comprehensive resort report...', 'info');
            setTimeout(() => {
                showNotification('Report generation feature coming soon!', 'success');
            }, 2000);
        }

        function backupSystem() {
            showNotification('System backup initiated...', 'info');
            setTimeout(() => {
                showNotification('Backup feature will be implemented in the next update!', 'success');
            }, 2000);
        }

        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="close-notification">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Auto-refresh functionality
        function refreshDashboardData() {
            showRefreshIndicator();
            
            fetch('dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateDashboardStats(data.stats);
                        updateRecentBookings(data.recentBookings);
                        updateConnectionStatus(data.connected);
                        
                        // Show success notification
                        showNotification('Dashboard updated automatically', 'success');
                    }
                })
                .catch(error => {
                    console.error('Auto-refresh error:', error);
                    showNotification('Auto-refresh failed', 'error');
                })
                .finally(() => {
                    hideRefreshIndicator();
                });
        }

        function updateDashboardStats(stats) {
            // Update stat cards
            document.querySelector('.stat-card.bookings h3').textContent = new Intl.NumberFormat().format(stats.totalBookings);
            document.querySelector('.stat-card.revenue h3').textContent = '₱' + new Intl.NumberFormat().format(stats.totalRevenue);
            document.querySelector('.stat-card.customers h3').textContent = new Intl.NumberFormat().format(stats.totalCustomers);
            document.querySelector('.stat-card.rooms h3').textContent = stats.availableRooms;
            
            // Update badge
            const badge = document.getElementById('pendingBadge');
            if (badge) {
                badge.textContent = stats.pendingBookings > 0 ? stats.pendingBookings : '';
            }
        }

        function updateRecentBookings(bookings) {
            const bookingsList = document.querySelector('.bookings-list');
            
            if (!bookings || bookings.length === 0) {
                bookingsList.innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <p>No recent bookings found</p>
                    </div>
                `;
                return;
            }

            let bookingsHTML = '';
            bookings.forEach(booking => {
                const isRealData = booking.data_source === 'REAL_DATA';
                const customerName = `${booking.Cust_FN || ''} ${booking.Cust_LN || 'Unknown Customer'}`.trim();
                const bookingDate = booking.Booking_Date ? new Date(booking.Booking_Date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                const statusClass = booking.Booking_Status ? booking.Booking_Status.toLowerCase() : 'pending';
                
                bookingsHTML += `
                    <div class="booking-item ${isRealData ? 'real-data' : 'fallback-data'}">
                        <div class="booking-info">
                            <div class="customer-name">
                                <i class="fas fa-user"></i>
                                ${customerName}
                                ${isRealData ? '<span class="data-badge real">🟢</span>' : '<span class="data-badge fallback">🟡</span>'}
                            </div>
                            <div class="booking-details">
                                <span class="room-type">${booking.Room_Type || 'N/A'}</span>
                                <span class="booking-date">${bookingDate}</span>
                                <span class="guest-count">${booking.Guests || 1} guest${(booking.Guests || 1) > 1 ? 's' : ''}</span>
                            </div>
                        </div>
                        <div class="booking-status">
                            <span class="status-badge ${statusClass}">
                                ${booking.Booking_Status || 'Pending'}
                            </span>
                            <div class="booking-amount">₱${new Intl.NumberFormat().format(booking.Total_Cost || 0)}</div>
                        </div>
                    </div>
                `;
            });
            
            bookingsList.innerHTML = bookingsHTML;
        }

        function updateConnectionStatus(connected) {
            const headerRight = document.querySelector('.header-right');
            let statusIndicator = document.querySelector('.connection-status');
            
            if (!statusIndicator) {
                statusIndicator = document.createElement('div');
                statusIndicator.className = 'connection-status';
                headerRight.insertBefore(statusIndicator, headerRight.firstChild);
            }
            
            statusIndicator.innerHTML = `
                <div class="status-indicator ${connected ? 'connected' : 'disconnected'}">
                    <i class="fas fa-${connected ? 'database' : 'exclamation-triangle'}"></i>
                    <span>${connected ? 'Database Connected' : 'Using Demo Data'}</span>
                </div>
            `;
        }

        function addRefreshIndicator() {
            const headerRight = document.querySelector('.header-right');
            const refreshIndicator = document.createElement('div');
            refreshIndicator.className = 'refresh-indicator';
            refreshIndicator.id = 'refreshIndicator';
            refreshIndicator.style.display = 'none';
            refreshIndicator.innerHTML = `
                <i class="fas fa-spinner fa-spin"></i>
                <span>Updating...</span>
            `;
            headerRight.appendChild(refreshIndicator);
        }

        function showRefreshIndicator() {
            const indicator = document.getElementById('refreshIndicator');
            if (indicator) {
                indicator.style.display = 'flex';
            }
        }

        function hideRefreshIndicator() {
            const indicator = document.getElementById('refreshIndicator');
            if (indicator) {
                indicator.style.display = 'none';
            }
        }
    </script>
    <style>
        /* Modern Admin Dashboard Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            overflow-x: hidden;
        }

        .admin-dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .admin-sidebar.collapsed {
            transform: translateX(-240px);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            font-size: 2rem;
            color: #4a9960;
        }

        .logo h2 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #4a9960, #66d9a3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            border-left-color: #4a9960;
        }

        .menu-item.active {
            background: rgba(74, 153, 96, 0.15);
            color: white;
            border-left-color: #4a9960;
            box-shadow: inset 0 0 20px rgba(74, 153, 96, 0.2);
        }

        .menu-item i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .badge {
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: auto;
            min-width: 18px;
            text-align: center;
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4a9960, #66d9a3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .admin-details {
            display: flex;
            flex-direction: column;
        }

        .admin-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .admin-role {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .logout-btn {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #e74c3c;
            color: white;
            transform: scale(1.1);
        }

        /* Main Content Styles */
        .admin-content {
            flex: 1;
            margin-left: 280px;
            background: #f8fafc;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .admin-sidebar.collapsed + .admin-content {
            margin-left: 40px;
        }

        .content-header {
            background: white;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .header-left h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .header-left p {
            color: #718096;
            font-size: 1rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .date-time {
            text-align: right;
        }

        .date {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .time {
            color: #718096;
            font-size: 0.8rem;
            font-family: 'Monaco', monospace;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #4a9960, #66d9a3);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 153, 96, 0.3);
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 153, 96, 0.4);
        }

        /* Section Content */
        .section-content {
            display: none;
            padding: 2rem;
            animation: fadeIn 0.5s ease;
        }

        .section-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #4a9960, #66d9a3);
        }

        .stat-card.revenue::before {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .stat-card.customers::before {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .stat-card.rooms::before {
            background: linear-gradient(135deg, #fa709a, #fee140);
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.bookings .stat-icon {
            background: linear-gradient(135deg, #4a9960, #66d9a3);
        }

        .stat-card.revenue .stat-icon {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .stat-card.customers .stat-icon {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .stat-card.rooms .stat-icon {
            background: linear-gradient(135deg, #fa709a, #fee140);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .stat-trend.up {
            background: rgba(72, 187, 120, 0.1);
            color: #48bb78;
        }

        .stat-trend.neutral {
            background: rgba(45, 55, 72, 0.1);
            color: #4a5568;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-all {
            color: #4a9960;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .view-all:hover {
            color: #2c5530;
        }

        .card-content {
            padding: 1.5rem;
            height: 300px;
            overflow-y: auto;
        }

        /* Booking List */
        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f7fafc;
        }

        .booking-item:last-child {
            border-bottom: none;
        }

        .booking-info {
            flex: 1;
        }

        .customer-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .booking-details {
            font-size: 0.8rem;
            color: #718096;
            display: flex;
            gap: 1rem;
        }

        .booking-status {
            text-align: right;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.pending {
            background: rgba(255, 193, 7, 0.1);
            color: #d69e2e;
        }

        .status-badge.confirmed {
            background: rgba(72, 187, 120, 0.1);
            color: #48bb78;
        }

        .booking-amount {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .no-data {
            text-align: center;
            color: #718096;
            padding: 2rem 0;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Charts */
        #revenueChart {
            height: 250px !important;
        }

        .chart-controls select {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: #2d3748;
            font-size: 0.9rem;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .actions-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: white;
            border: 2px solid #e2e8f0;
            padding: 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
        }

        .action-btn:hover {
            border-color: #4a9960;
            background: rgba(74, 153, 96, 0.02);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 153, 96, 0.15);
        }

        .action-btn i {
            font-size: 1.5rem;
            color: #4a9960;
            margin-bottom: 0.5rem;
        }

        .action-btn span {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        /* Management Placeholder Styles */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a9960, #66d9a3);
            color: white;
            box-shadow: 0 4px 15px rgba(74, 153, 96, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 153, 96, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .management-placeholder {
            background: white;
            border-radius: 16px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .management-placeholder i {
            font-size: 4rem;
            color: #4a9960;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .management-placeholder h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .management-placeholder p {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .feature-item {
            background: rgba(74, 153, 96, 0.05);
            padding: 1rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            color: #2d3748;
        }

        .feature-item i {
            color: #4a9960;
            font-size: 1rem;
        }

        /* Notification System */
        .notification-container {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 2000;
        }

        .notification {
            background: white;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            border-left: 4px solid #48bb78;
        }

        .notification.info {
            border-left: 4px solid #4299e1;
        }

        .notification.error {
            border-left: 4px solid #e53e3e;
        }

        .notification i:first-child {
            font-size: 1.2rem;
        }

        .notification.success i:first-child {
            color: #48bb78;
        }

        .notification.info i:first-child {
            color: #4299e1;
        }

        .notification.error i:first-child {
            color: #e53e3e;
        }

        .close-notification {
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            margin-left: auto;
            padding: 0.25rem;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Customer and Service Item Styles */
        .customer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f7fafc;
        }

        .customer-item:last-child {
            border-bottom: none;
        }

        .customer-info {
            flex: 1;
        }

        .customer-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .customer-email {
            font-size: 0.8rem;
            color: #718096;
        }

        .customer-stats {
            text-align: right;
        }

        .bookings-count {
            font-size: 0.8rem;
            color: #718096;
            margin-bottom: 0.25rem;
        }

        .customer-spent {
            font-weight: 600;
            color: #4a9960;
        }

        .service-section {
            margin-bottom: 1.5rem;
        }

        .service-section:last-child {
            margin-bottom: 0;
        }

        .service-section h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f7fafc;
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .service-name {
            font-size: 0.85rem;
            color: #2d3748;
        }

        .service-price {
            font-weight: 600;
            color: #4a9960;
            font-size: 0.85rem;
        }

        .status-summary {
            font-size: 0.8rem;
            color: #718096;
            background: rgba(74, 153, 96, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
        }

        .guest-count {
            font-size: 0.75rem;
            color: #4a9960 !important;
            background: rgba(74, 153, 96, 0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-weight: 500;
        }

        /* Chart Container Improvements */
        .card-content canvas {
            width: 100% !important;
            height: 100% !important;
        }

        /* Additional Analytics Cards */
        .amenities-services {
            height: 240px;
            overflow-y: auto;
        }

        .customers-list {
            height: 280px;
            overflow-y: auto;
        }

        /* Status Badge Improvements */
        .status-badge.paid {
            background: rgba(72, 187, 120, 0.1);
            color: #48bb78;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }

            .admin-content {
                margin-left: 0;
                width: 100%;
            }

            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Auto-refresh and connection status styles */
        .connection-status {
            margin-right: 1rem;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-indicator.connected {
            background: rgba(74, 153, 96, 0.1);
            color: #4a9960;
        }

        .status-indicator.disconnected {
            background: rgba(245, 101, 101, 0.1);
            color: #f56565;
        }

        .refresh-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(74, 153, 96, 0.1);
            color: #4a9960;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 1rem;
        }

        .refresh-indicator i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .data-badge {
            font-size: 0.7rem;
            margin-left: 0.5rem;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }

        .data-badge.real {
            background: rgba(74, 153, 96, 0.1);
            color: #4a9960;
        }

        .data-badge.fallback {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .booking-item.real-data {
            border-left: 3px solid #4a9960;
        }

        .booking-item.fallback-data {
            border-left: 3px solid #ffc107;
        }

        /* Notification improvements for auto-refresh */
        .notification {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            border-left: 4px solid;
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            border-left-color: #4a9960;
            background: linear-gradient(135deg, rgba(74, 153, 96, 0.05), rgba(102, 217, 163, 0.05));
        }

        .notification.error {
            border-left-color: #f56565;
            background: linear-gradient(135deg, rgba(245, 101, 101, 0.05), rgba(245, 101, 101, 0.05));
        }

        .notification.info {
            border-left-color: #4299e1;
            background: linear-gradient(135deg, rgba(66, 153, 225, 0.05), rgba(66, 153, 225, 0.05));
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .close-notification {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            opacity: 0.7;
            transition: all 0.2s ease;
        }

        .close-notification:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.1);
        }
    </style>
</body>
</html>
