<?php
/**
 * Database Configuration for Paradise Resort
 * Guest Accommodation System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'guest_accommodation_system');

// Create connection class
class DatabaseConnection {
    private $connection;
    private $host;
    private $username;
    private $password;
    private $database;
    
    public function __construct($host = DB_HOST, $username = DB_USERNAME, $password = DB_PASSWORD, $database = DB_NAME) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }
    
    /**
     * Create database connection
     */
    public function connect() {
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            // Check connection
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset to UTF-8
            $this->connection->set_charset("utf8");
            
            return $this->connection;
            
        } catch (Exception $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get connection instance
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Close database connection
     */
    public function disconnect() {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
    
    /**
     * Prepare and execute a statement
     */
    public function prepare($sql) {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->connection->prepare($sql);
    }
}

// Global database connection function
function getDBConnection() {
    $db = new DatabaseConnection();
    return $db->connect();
}

// Execute query with parameters and return results
function executeQuery($query, $params = [], $types = '') {
    $conn = getDBConnection();
    if (!$conn) {
        return false;
    }
    
    try {
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            
            if ($stmt->error) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $data = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            
            $stmt->close();
            $conn->close();
            return $data;
            
        } else {
            $result = $conn->query($query);
            if (!$result) {
                throw new Exception("Query failed: " . $conn->error);
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $conn->close();
            return $data;
        }
        
    } catch (Exception $e) {
        error_log("Database Query Error: " . $e->getMessage());
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
        return false;
    }
}

// Utility functions for common database operations
class DatabaseUtilities {
    
    /**
     * Check if email exists in customer table
     */
    public static function emailExists($email) {
        $query = "SELECT Cust_ID FROM customer WHERE Cust_Email = ? LIMIT 1";
        $result = executeQuery($query, [$email], 's');
        return !empty($result);
    }
    
    /**
     * Check if phone exists in customer table
     */
    public static function phoneExists($phone) {
        $query = "SELECT Cust_ID FROM customer WHERE Cust_Phone = ? LIMIT 1";
        $result = executeQuery($query, [$phone], 's');
        return !empty($result);
    }
    
    /**
     * Get customer by email
     */
    public static function getCustomerByEmail($email) {
        $query = "SELECT * FROM customer WHERE Cust_Email = ? LIMIT 1";
        $result = executeQuery($query, [$email], 's');
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Get customer by ID
     */
    public static function getCustomerById($id) {
        $query = "SELECT * FROM customer WHERE Cust_ID = ? LIMIT 1";
        $result = executeQuery($query, [$id], 'i');
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Insert new customer
     */
    public static function insertCustomer($firstName, $lastName, $email, $phone, $hashedPassword) {
        $query = "INSERT INTO customer (Cust_FN, Cust_LN, Cust_Email, Cust_Phone, Cust_Password, is_banned) VALUES (?, ?, ?, ?, ?, 0)";
        return executeQuery($query, [$firstName, $lastName, $email, $phone, $hashedPassword], 'sssss');
    }
    
    /**
     * Update customer last login
     */
    public static function updateCustomerLogin($customerId) {
        $query = "UPDATE customer SET last_login = CURRENT_TIMESTAMP WHERE Cust_ID = ?";
        return executeQuery($query, [$customerId], 'i');
    }
    
    /**
     * Get admin by email
     */
    public static function getAdminByEmail($email) {
        $query = "SELECT * FROM admin WHERE Admin_Email = ? LIMIT 1";
        $result = executeQuery($query, [$email], 's');
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Update admin last login
     */
    public static function updateAdminLogin($adminId) {
        $query = "UPDATE admin SET last_login = CURRENT_TIMESTAMP WHERE Admin_ID = ?";
        return executeQuery($query, [$adminId], 'i');
    }
    
    /**
     * Get customer bookings
     */
    public static function getCustomerBookings($customerId) {
        $query = "SELECT b.*, r.Room_Type, r.Room_Price, r.Room_Desc 
                 FROM booking b 
                 JOIN room r ON b.Room_ID = r.Room_ID 
                 WHERE b.Cust_ID = ? 
                 ORDER BY b.Check_In DESC";
        return executeQuery($query, [$customerId], 'i');
    }
    
    /**
     * Get customer stats
     */
    public static function getCustomerStats($customerId) {
        // Get total bookings
        $totalBookingsQuery = "SELECT COUNT(*) as total FROM booking WHERE Cust_ID = ?";
        $totalBookings = executeQuery($totalBookingsQuery, [$customerId], 'i');
        
        // Get upcoming bookings
        $upcomingQuery = "SELECT COUNT(*) as upcoming FROM booking WHERE Cust_ID = ? AND Check_In > NOW()";
        $upcoming = executeQuery($upcomingQuery, [$customerId], 'i');
        
        // Get total spent
        $spentQuery = "SELECT SUM(b.Total_Amount) as total_spent 
                      FROM booking b 
                      WHERE b.Cust_ID = ? AND b.Status = 'confirmed'";
        $spent = executeQuery($spentQuery, [$customerId], 'i');
        
        return [
            'total_bookings' => $totalBookings[0]['total'] ?? 0,
            'upcoming_bookings' => $upcoming[0]['upcoming'] ?? 0,
            'total_spent' => $spent[0]['total_spent'] ?? 0
        ];
    }
}

// Authentication helper functions
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // Regenerate session ID for security
        if (!isset($_SESSION['session_regenerated'])) {
            session_regenerate_id(true);
            $_SESSION['session_regenerated'] = true;
        }
        
        // Set secure session parameters
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
    }
}

function isLoggedIn() {
    return isset($_SESSION['cust_id']) || isset($_SESSION['admin_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin($redirectUrl = '/login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit();
    }
}

function requireAdmin($redirectUrl = '/login.php') {
    if (!isAdmin()) {
        header("Location: $redirectUrl");
        exit();
    }
}

function logout() {
    session_start();
    session_unset();
    session_destroy();
    
    // Clear session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

// Rate limiting for login attempts
function checkLoginAttempts($identifier, $maxAttempts = 10, $timeWindow = 300) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("SELECT attempt_count, last_attempt FROM login_attempts WHERE identifier = ? AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->bind_param('si', $identifier, $timeWindow);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['attempt_count'] >= $maxAttempts) {
            $stmt->close();
            $conn->close();
            return false; // Too many attempts
        }
    }
    
    $stmt->close();
    $conn->close();
    return true; // Attempts allowed
}

function recordLoginAttempt($identifier, $success = false) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    if ($success) {
        // Clear attempts on successful login
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ?");
        $stmt->bind_param('s', $identifier);
    } else {
        // Record failed attempt
        $stmt = $conn->prepare("INSERT INTO login_attempts (identifier, attempt_count, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1, last_attempt = NOW()");
        $stmt->bind_param('s', $identifier);
    }
    
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}

?>
