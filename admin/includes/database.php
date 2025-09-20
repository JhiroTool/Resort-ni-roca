<?php
/**
 * Database Configuration - Paradise Resort Management System
 * Centralized database connection and management
 */

class DatabaseManager {
    private $host = 'localhost';
    private $dbname = 'guest_accommodation_system';
    private $username = 'root';
    private $password = '';
    private $pdo;
    
    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
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
    
    public function testConnection() {
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->query("SELECT 1");
                return true;
            } catch(PDOException $e) {
                return false;
            }
        }
        return false;
    }
}

/**
 * Common utility functions for the admin system
 */
class AdminUtils {
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function generateBookingId($pdo) {
        if (!$pdo) return rand(1000, 9999);
        
        try {
            $stmt = $pdo->query("SELECT MAX(Booking_ID) + 1 as next_id FROM booking");
            $result = $stmt->fetch();
            return $result['next_id'] ?? 1;
        } catch(PDOException $e) {
            return rand(1000, 9999);
        }
    }
    
    public static function formatCurrency($amount, $symbol = '₱') {
        return $symbol . number_format((float)$amount, 2);
    }
    
    public static function formatDate($date, $format = 'M j, Y') {
        if (empty($date)) return 'N/A';
        return date($format, strtotime($date));
    }
    
    public static function formatDateTime($datetime, $format = 'M j, Y g:i A') {
        if (empty($datetime)) return 'N/A';
        return date($format, strtotime($datetime));
    }
    
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    public static function logActivity($pdo, $adminId, $action, $description = '') {
        if (!$pdo) return false;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO admin_activity_log (Admin_ID, Action, Description, Log_Date) 
                VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$adminId, $action, $description]);
        } catch(PDOException $e) {
            error_log("Activity log error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getBookingStatusBadgeClass($status) {
        $classes = [
            'Pending' => 'pending',
            'Paid' => 'paid',
            'Confirmed' => 'confirmed',
            'Cancelled' => 'cancelled',
            'Completed' => 'completed'
        ];
        
        return $classes[$status] ?? 'pending';
    }
    
    public static function calculateDaysBetween($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        return $interval->days;
    }
    
    public static function isValidPhoneNumber($phone) {
        // Basic Philippine mobile number validation
        return preg_match('/^(09|\+639)\d{9}$/', $phone);
    }
}

/**
 * Authentication helper functions
 */
class AuthManager {
    
    public static function checkAdminLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['admin_id'])) {
            header("Location: ../login.php?type=admin&error=login_required");
            exit();
        }
        
        return $_SESSION['admin_id'];
    }
    
    public static function getAdminInfo($pdo, $adminId) {
        if (!$pdo) return ['Admin_Email' => 'admin@resort.com'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM administrator WHERE Admin_ID = ?");
            $stmt->execute([$adminId]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get admin info error: " . $e->getMessage());
            return ['Admin_Email' => 'admin@resort.com'];
        }
    }
    
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
}

/**
 * Notification system
 */
class NotificationManager {
    
    public static function setFlashMessage($type, $message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    public static function getFlashMessage() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        
        return null;
    }
}

/**
 * Data validation helper
 */
class ValidationHelper {
    
    public static function validateBookingData($data) {
        $errors = [];
        
        if (empty($data['customer_id'])) {
            $errors[] = 'Customer is required';
        }
        
        if (empty($data['room_id'])) {
            $errors[] = 'Room is required';
        }
        
        if (empty($data['checkin_date'])) {
            $errors[] = 'Check-in date is required';
        }
        
        if (empty($data['checkout_date'])) {
            $errors[] = 'Check-out date is required';
        }
        
        if (!empty($data['checkin_date']) && !empty($data['checkout_date'])) {
            if (strtotime($data['checkin_date']) >= strtotime($data['checkout_date'])) {
                $errors[] = 'Check-out date must be after check-in date';
            }
        }
        
        if (empty($data['guests']) || $data['guests'] < 1) {
            $errors[] = 'Number of guests must be at least 1';
        }
        
        return $errors;
    }
    
    public static function validateCustomerData($data) {
        $errors = [];
        
        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }
        
        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        }
        
        if (empty($data['email']) || !AdminUtils::validateEmail($data['email'])) {
            $errors[] = 'Valid email address is required';
        }
        
        if (empty($data['phone']) || !AdminUtils::isValidPhoneNumber($data['phone'])) {
            $errors[] = 'Valid phone number is required';
        }
        
        return $errors;
    }
}
?>
