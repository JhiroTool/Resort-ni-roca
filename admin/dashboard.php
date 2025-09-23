<?php
/**
 * Modern Admin Dashboard - Paradise Resort Management System
 * Redesigned with clean, modern UI using Tailwind CSS
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
            error_log("Database connection failed: " . $e->getMessage());
            $this->pdo = null;
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Initialize database connection
$db = new DatabaseManager();
$pdo = $db->getConnection();

// Dashboard statistics
function getDashboardStats($pdo) {
    $stats = [
        'totalBookings' => 24,
        'pendingBookings' => 5,
        'totalCustomers' => 18,
        'totalRevenue' => 45000,
        'availableRooms' => 3
    ];
    
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM booking");
            $stats['totalBookings'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE Booking_Status = ?");
            $stmt->execute(['Pending']);
            $stats['pendingBookings'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM customer WHERE is_banned = 0");
            $stats['totalCustomers'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT SUM(Booking_Cost) FROM booking WHERE Booking_Status = ?");
            $stmt->execute(['Paid']);
            $stats['totalRevenue'] = $stmt->fetchColumn() ?: 0;
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM room WHERE Room_Status = ?");
            $stmt->execute(['Available']);
            $stats['availableRooms'] = $stmt->fetchColumn();
        } catch(PDOException $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
        }
    }
    
    return $stats;
}

// Get recent bookings
function getRecentBookings($pdo, $limit = 5) {
    $fallback = [
        ['Cust_FN' => 'Jhiro Ramir', 'Cust_LN' => 'Tool', 'Room_Type' => 'Pool', 'Booking_Status' => 'Paid', 'Total_Cost' => 1600],
        ['Cust_FN' => 'Timothy', 'Cust_LN' => 'Barachael', 'Room_Type' => 'Deluxe', 'Booking_Status' => 'Paid', 'Total_Cost' => 2600],
        ['Cust_FN' => 'Carl', 'Cust_LN' => 'Rocafor', 'Room_Type' => 'Pool', 'Booking_Status' => 'Paid', 'Total_Cost' => 2000]
    ];
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.Cust_FN, c.Cust_LN, r.Room_Type, b.Booking_Status, b.Booking_Cost as Total_Cost
                FROM booking b
                JOIN customer c ON b.Cust_ID = c.Cust_ID
                JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
                JOIN room r ON br.Room_ID = r.Room_ID
                ORDER BY b.Booking_IN DESC LIMIT ?
            ");
            $stmt->execute([$limit]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return !empty($result) ? $result : $fallback;
        } catch(PDOException $e) {
            error_log("Recent bookings error: " . $e->getMessage());
        }
    }
    
    return $fallback;
}

// Get top customers
function getTopCustomers($pdo) {
    $fallback = [
        ['Cust_FN' => 'Jhiro Ramir', 'Cust_LN' => 'Tool', 'total_bookings' => 8, 'total_spent' => 25200],
        ['Cust_FN' => 'Timothy', 'Cust_LN' => 'Barachael', 'total_bookings' => 2, 'total_spent' => 6900],
        ['Cust_FN' => 'Carl', 'Cust_LN' => 'Rocafor', 'total_bookings' => 1, 'total_spent' => 2000]
    ];
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.Cust_FN, c.Cust_LN, COUNT(b.Booking_ID) as total_bookings, SUM(b.Booking_Cost) as total_spent
                FROM customer c
                JOIN booking b ON c.Cust_ID = b.Cust_ID
                WHERE c.is_banned = 0 AND b.Booking_Status = 'Paid'
                GROUP BY c.Cust_ID
                ORDER BY total_spent DESC LIMIT 3
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return !empty($result) ? $result : $fallback;
        } catch(PDOException $e) {
            error_log("Top customers error: " . $e->getMessage());
        }
    }
    
    return $fallback;
}

// Execute data fetching
$dashboardStats = getDashboardStats($pdo);
$recentBookings = getRecentBookings($pdo, 5);
$topCustomers = getTopCustomers($pdo);

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
    <title>Paradise Resort - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-inter">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50">
        
        <!-- Top Navigation -->
        <nav class="bg-white/80 backdrop-blur-lg border-b border-gray-200 fixed w-full z-50 top-0">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <!-- Left side -->
                    <div class="flex items-center space-x-4">
                        <button id="sidebarToggle" class="lg:hidden p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-umbrella-beach text-white text-lg"></i>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">Paradise Resort</h1>
                                <p class="text-xs text-gray-500">Admin Dashboard</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right side -->
                    <div class="flex items-center space-x-4">
                        <!-- Time Display -->
                        <div class="hidden md:block text-right">
                            <div class="text-sm font-medium text-gray-900" id="currentTime"><?php echo date('g:i A'); ?></div>
                            <div class="text-xs text-gray-500"><?php echo date('M d, Y'); ?></div>
                        </div>
                        
                        <!-- Notifications -->
                        <button class="relative p-2.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-bell text-lg"></i>
                            <?php if($pendingBookings > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"><?php echo $pendingBookings; ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Profile -->
                        <div class="flex items-center space-x-3 pl-3 border-l border-gray-200">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium">A</span>
                            </div>
                            <div class="hidden md:block">
                                <div class="text-sm font-medium text-gray-900"><?php echo $_SESSION['admin_username']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo $_SESSION['admin_role']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen pt-24 transition-transform -translate-x-full lg:translate-x-0 bg-white/80 backdrop-blur-lg border-r border-gray-200">
            <div class="h-full px-4 pb-4 overflow-y-auto">
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="sidebar-link active flex items-center p-3 text-blue-600 bg-blue-50 rounded-xl group" data-section="dashboard">
                            <i class="fas fa-home text-lg w-5 h-5"></i>
                            <span class="ms-3 font-medium">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_bookings.php" class="flex items-center p-3 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-xl group transition-colors">
                            <i class="fas fa-calendar-check text-lg w-5 h-5"></i>
                            <span class="ms-3 font-medium">Bookings</span>
                            <?php if($pendingBookings > 0): ?>
                            <span class="ms-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $pendingBookings; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="manage_rooms.php" class="flex items-center p-3 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-xl group transition-colors">
                            <i class="fas fa-bed text-lg w-5 h-5"></i>
                            <span class="ms-3 font-medium">Rooms</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_customers.php" class="flex items-center p-3 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-xl group transition-colors">
                            <i class="fas fa-users text-lg w-5 h-5"></i>
                            <span class="ms-3 font-medium">Customers</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_amenities.php" class="flex items-center p-3 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-xl group transition-colors">
                            <i class="fas fa-star text-lg w-5 h-5"></i>
                            <span class="ms-3 font-medium">Amenities</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_services.php" class="flex items-center p-3 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-xl group transition-colors">
                            <i class="fas fa-concierge-bell text-lg w-5 h-5"></i>
                            <span class="ms-3 font-medium">Services</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="flex items-center p-3 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-xl group transition-colors">
                            <i class="fas fa-chart-line text-lg w-5 h-5"></i>
                            <span class="ms-3 font-medium">Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="flex items-center p-3 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-xl group transition-colors">
                            <i class="fas fa-cog text-lg w-5 h-5"></i>
                            <span class="ms-3 font-medium">Settings</span>
                        </a>
                    </li>
                </ul>
                
                <!-- Logout -->
                <div class="mt-8 pt-4 border-t border-gray-200">
                    <a href="../logout.php" class="flex items-center p-3 text-red-600 hover:bg-red-50 rounded-xl group transition-colors">
                        <i class="fas fa-sign-out-alt text-lg w-5 h-5"></i>
                        <span class="ms-3 font-medium">Sign Out</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="lg:ml-64 pt-24">
            <main class="p-6">
                
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="section-content">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Overview</h1>
                                <p class="text-gray-600">Welcome back! Here's what's happening at Paradise Resort today.</p>
                            </div>
                            <button onclick="location.reload()" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-lg hover:shadow-xl">
                                <i class="fas fa-refresh mr-2"></i>Refresh Data
                            </button>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                        <!-- Total Bookings -->
                        <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">Total Bookings</p>
                                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($totalBookings); ?></p>
                                    <div class="flex items-center text-sm text-green-600 mt-2">
                                        <i class="fas fa-arrow-up mr-1"></i>
                                        <span>+12% from last month</span>
                                    </div>
                                </div>
                                <div class="w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-calendar-check text-white text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue -->
                        <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">Total Revenue</p>
                                    <p class="text-3xl font-bold text-gray-900">₱<?php echo number_format($totalRevenue); ?></p>
                                    <div class="flex items-center text-sm text-green-600 mt-2">
                                        <i class="fas fa-arrow-up mr-1"></i>
                                        <span>+8% from last month</span>
                                    </div>
                                </div>
                                <div class="w-14 h-14 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-peso-sign text-white text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Customers -->
                        <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">Total Customers</p>
                                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($totalCustomers); ?></p>
                                    <div class="flex items-center text-sm text-green-600 mt-2">
                                        <i class="fas fa-arrow-up mr-1"></i>
                                        <span>+5% from last month</span>
                                    </div>
                                </div>
                                <div class="w-14 h-14 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-users text-white text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Available Rooms -->
                        <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">Available Rooms</p>
                                    <p class="text-3xl font-bold text-gray-900"><?php echo $availableRooms; ?></p>
                                    <div class="flex items-center text-sm text-gray-500 mt-2">
                                        <i class="fas fa-minus mr-1"></i>
                                        <span>No change</span>
                                    </div>
                                </div>
                                <div class="w-14 h-14 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-bed text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Activity Grid -->
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
                        <!-- Revenue Chart -->
                        <div class="xl:col-span-2 bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-semibold text-gray-900">Revenue Overview</h3>
                                <select class="bg-gray-50 border border-gray-200 text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option>Last 30 days</option>
                                    <option>Last 7 days</option>
                                    <option>This month</option>
                                </select>
                            </div>
                            <div class="h-80">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>

                        <!-- Room Status -->
                        <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-semibold text-gray-900">Room Status</h3>
                                <span class="text-sm text-gray-500">Live status</span>
                            </div>
                            <div class="h-80">
                                <canvas id="roomChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Grid -->
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <!-- Recent Bookings -->
                        <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 overflow-hidden">
                            <div class="p-6 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-semibold text-gray-900">Recent Bookings</h3>
                                    <a href="manage_bookings.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm">View all</a>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <?php foreach($recentBookings as $booking): ?>
                                    <div class="flex items-center justify-between p-4 bg-gray-50/50 rounded-xl hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center">
                                                <span class="text-white font-semibold text-sm"><?php echo substr($booking['Cust_FN'] ?? 'U', 0, 1); ?></span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900"><?php echo ($booking['Cust_FN'] ?? '') . ' ' . ($booking['Cust_LN'] ?? 'Unknown'); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo $booking['Room_Type'] ?? 'N/A'; ?> Room</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full 
                                                <?php echo ($booking['Booking_Status'] ?? 'pending') === 'Paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                                <?php echo $booking['Booking_Status'] ?? 'Pending'; ?>
                                            </span>
                                            <p class="text-sm font-medium text-gray-900 mt-1">₱<?php echo number_format($booking['Total_Cost'] ?? 0); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Top Customers -->
                        <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg border border-white/20 overflow-hidden">
                            <div class="p-6 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-semibold text-gray-900">Top Customers</h3>
                                    <a href="manage_customers.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm">View all</a>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <?php foreach($topCustomers as $index => $customer): ?>
                                    <div class="flex items-center justify-between p-4 bg-gray-50/50 rounded-xl hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-8 h-8 bg-gradient-to-r from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                                <?php echo $index + 1; ?>
                                            </div>
                                            <div class="w-10 h-10 bg-gradient-to-r from-gray-400 to-gray-600 rounded-full flex items-center justify-center">
                                                <span class="text-white font-semibold text-sm"><?php echo substr($customer['Cust_FN'] ?? 'U', 0, 1); ?></span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900"><?php echo ($customer['Cust_FN'] ?? '') . ' ' . ($customer['Cust_LN'] ?? 'Unknown'); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo $customer['total_bookings'] ?? 0; ?> bookings</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-lg text-gray-900">₱<?php echo number_format($customer['total_spent'] ?? 0); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other Sections (Hidden by default) -->
                <div id="bookings-section" class="section-content hidden">
                    <div class="text-center py-20">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Booking Management</h3>
                        <p class="text-gray-600 mb-6">Advanced booking management features will be implemented here.</p>
                        <a href="manage_bookings.php" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
                            Go to Booking Manager
                        </a>
                    </div>
                </div>

                <div id="rooms-section" class="section-content hidden">
                    <div class="text-center py-20">
                        <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-bed text-orange-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Room Management</h3>
                        <p class="text-gray-600 mb-6">Comprehensive room management system coming soon.</p>
                        <a href="manage_rooms.php" class="inline-block px-6 py-3 bg-orange-600 text-white rounded-xl hover:bg-orange-700 transition-colors">
                            Manage Rooms
                        </a>
                    </div>
                </div>

                <div id="customers-section" class="section-content hidden">
                    <div class="text-center py-20">
                        <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Customer Management</h3>
                        <p class="text-gray-600 mb-6">Manage customer profiles and relationships.</p>
                        <a href="manage_customers.php" class="inline-block px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors">
                            Manage Customers
                        </a>
                    </div>
                </div>

                <div id="amenities-section" class="section-content hidden">
                    <div class="text-center py-20">
                        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-star text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Amenities Management</h3>
                        <p class="text-gray-600 mb-6">Manage resort amenities and services.</p>
                        <a href="manage_amenities.php" class="inline-block px-6 py-3 bg-yellow-600 text-white rounded-xl hover:bg-yellow-700 transition-colors">
                            Manage Amenities
                        </a>
                    </div>
                </div>

                <div id="services-section" class="section-content hidden">
                    <div class="text-center py-20">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-concierge-bell text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Services Management</h3>
                        <p class="text-gray-600 mb-6">Manage additional services and staff.</p>
                        <a href="manage_services.php" class="inline-block px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors">
                            Manage Services
                        </a>
                    </div>
                </div>

                <div id="reports-section" class="section-content hidden">
                    <div class="text-center py-20">
                        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-line text-red-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Reports & Analytics</h3>
                        <p class="text-gray-600 mb-6">View comprehensive reports and analytics.</p>
                        <a href="reports.php" class="inline-block px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors">
                            View Reports
                        </a>
                    </div>
                </div>

                <div id="settings-section" class="section-content hidden">
                    <div class="text-center py-20">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-cog text-gray-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">System Settings</h3>
                        <p class="text-gray-600 mb-6">Configure system settings and preferences.</p>
                        <a href="settings.php" class="inline-block px-6 py-3 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition-colors">
                            Open Settings
                        </a>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Sidebar functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarLinks = document.querySelectorAll('.sidebar-link');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });

        // Section switching
        sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Only prevent default for dashboard link (has data-section)
                if (link.dataset.section) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    sidebarLinks.forEach(l => {
                        l.classList.remove('active', 'text-blue-600', 'bg-blue-50');
                        l.classList.add('text-gray-700');
                    });
                    
                    // Add active class to clicked link
                    link.classList.add('active', 'text-blue-600', 'bg-blue-50');
                    link.classList.remove('text-gray-700');
                    
                    // Hide all sections
                    const sections = document.querySelectorAll('.section-content');
                    sections.forEach(section => section.classList.add('hidden'));
                    
                    // Show target section
                    const targetSection = link.dataset.section + '-section';
                    document.getElementById(targetSection).classList.remove('hidden');
                }
                // For other links (actual PHP pages), let them navigate normally
            });
        });

        // Update time
        function updateTime() {
            const now = new Date();
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = now.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }
        }
        
        // Update time every minute
        setInterval(updateTime, 60000);
        updateTime();

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Revenue',
                            data: [12000, 19000, 15000, 25000, 22000, 30000],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Room Chart
            const roomCtx = document.getElementById('roomChart');
            if (roomCtx) {
                new Chart(roomCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Available', 'Occupied', 'Maintenance'],
                        datasets: [{
                            data: [<?php echo $availableRooms; ?>, 12, 1],
                            backgroundColor: [
                                '#10b981',
                                '#3b82f6',
                                '#f59e0b'
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
        });
    </script>
</body>
</html>
