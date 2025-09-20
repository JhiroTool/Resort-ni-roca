<?php
/**
 * AJAX endpoint for dashboard auto-refresh
 * Returns JSON data for real-time updates
 */

session_start();

// Check if admin is logged in - temporarily disabled for testing
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_username'] = 'Administrator';
    $_SESSION['admin_role'] = 'Super Admin';
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
    
    public function isConnected() {
        return $this->pdo !== null;
    }
}

// Include the same functions from dashboard
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

function getRecentBookings($pdo, $limit = 8) {
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
            
            if (!empty($result)) {
                return $result;
            }
        } catch(PDOException $e) {
            error_log("Recent bookings error: " . $e->getMessage());
        }
    }
    
    // Return fallback data
    return [
        [
            'Cust_FN' => 'Jhiro Ramir',
            'Cust_LN' => 'Tool',
            'Room_Type' => 'Pool',
            'Booking_Date' => '2025-06-26',
            'Booking_Status' => 'Paid',
            'Total_Cost' => 1600,
            'Guests' => 5,
            'data_source' => 'FALLBACK_DATA'
        ],
        [
            'Cust_FN' => 'Timothy',
            'Cust_LN' => 'Barachael',
            'Room_Type' => 'Deluxe',
            'Booking_Date' => '2025-06-25',
            'Booking_Status' => 'Paid',
            'Total_Cost' => 2600,
            'Guests' => 10,
            'data_source' => 'FALLBACK_DATA'
        ],
        [
            'Cust_FN' => 'Carl',
            'Cust_LN' => 'Rocafor',
            'Room_Type' => 'Pool',
            'Booking_Date' => '2025-06-23',
            'Booking_Status' => 'Paid',
            'Total_Cost' => 2000,
            'Guests' => 5,
            'data_source' => 'FALLBACK_DATA'
        ]
    ];
}

// Initialize database and get data
$db = new DatabaseManager();
$pdo = $db->getConnection();

$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'stats' => getDashboardStats($pdo),
    'recentBookings' => getRecentBookings($pdo, 8),
    'connected' => $db->isConnected()
];

header('Content-Type: application/json');
echo json_encode($response);
?>
