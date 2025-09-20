<?php
/**
 * Unified Registration Handler
 * Handles client registration (admins should not self-register)
 */

require_once '../config/database.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

session_start();

// Rate limiting for registration attempts
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 0;
    $_SESSION['last_register_attempt'] = 0;
}

$max_attempts = 3;
$lockout_time = 300; // 5 minutes
$current_time = time();

// Check if user is locked out from registration
if ($_SESSION['register_attempts'] >= $max_attempts && 
    ($current_time - $_SESSION['last_register_attempt']) < $lockout_time) {
    
    $remaining_time = $lockout_time - ($current_time - $_SESSION['last_register_attempt']);
    redirectWithError('register_locked', ['time' => ceil($remaining_time / 60)]);
}

// Reset attempts after lockout period
if ($_SESSION['register_attempts'] >= $max_attempts && 
    ($current_time - $_SESSION['last_register_attempt']) >= $lockout_time) {
    $_SESSION['register_attempts'] = 0;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('invalid_request');
}

// CSRF protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    redirectWithError('invalid_token');
}

// Get and validate input
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$terms_accepted = isset($_POST['terms_accepted']);
$newsletter_subscription = isset($_POST['newsletter_subscription']);

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email) || 
    empty($phone) || empty($password) || empty($confirm_password)) {
    incrementRegisterAttempts();
    redirectWithError('missing_fields');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    incrementRegisterAttempts();
    redirectWithError('invalid_email');
}

// Validate phone number
if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
    incrementRegisterAttempts();
    redirectWithError('invalid_phone');
}

// Validate passwords match
if ($password !== $confirm_password) {
    incrementRegisterAttempts();
    redirectWithError('password_mismatch');
}

// Validate password strength
if (!isPasswordStrong($password)) {
    incrementRegisterAttempts();
    redirectWithError('weak_password');
}

// Check terms acceptance
if (!$terms_accepted) {
    incrementRegisterAttempts();
    redirectWithError('terms_required');
}

// Sanitize names
$first_name = htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8');
$last_name = htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8');

try {
    $db = new DatabaseConnection();
    $conn = $db->connect();
    
    // Check if email already exists
    if (emailExists($conn, $email)) {
        incrementRegisterAttempts();
        redirectWithError('email_exists');
    }
    
    // Check if phone already exists
    if (phoneExists($conn, $phone)) {
        incrementRegisterAttempts();
        redirectWithError('phone_exists');
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new customer using your database schema
    $query = "INSERT INTO customer (Cust_FN, Cust_LN, Cust_Email, Cust_Phone, 
              Cust_Password, is_banned) 
              VALUES (?, ?, ?, ?, ?, 0)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $password_hash);
    
    if ($stmt->execute()) {
        // Reset registration attempts on success
        $_SESSION['register_attempts'] = 0;
        
        // Get the inserted customer's auto-increment ID
        $db_customer_id = $conn->insert_id;
        
        // Log registration activity
        logRegistrationActivity($conn, $db_customer_id, 'success');
        
        // Auto-login the user
        $_SESSION['cust_id'] = $db_customer_id;  // Match your dashboard expectations
        $_SESSION['customer_id'] = $db_customer_id; // Keep both for compatibility
        $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
        $_SESSION['customer_email'] = $email;
        $_SESSION['user_type'] = 'client';
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Send welcome email (optional)
        if ($newsletter_subscription) {
            sendWelcomeEmail($email, $first_name);
        }
        
        $stmt->close();
        $conn->close();
        
        // Redirect to dashboard with success message
        header("Location: ../client/dashboard.php?registration=success");
        exit();
        
    } else {
        throw new Exception("Database insertion failed");
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    incrementRegisterAttempts();
    
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        if (strpos($e->getMessage(), 'email') !== false) {
            redirectWithError('email_exists');
        } elseif (strpos($e->getMessage(), 'phone') !== false) {
            redirectWithError('phone_exists');
        }
    }
    
    redirectWithError('database_error');
}

/**
 * Check if email already exists
 */
function emailExists($conn, $email) {
    $query = "SELECT COUNT(*) as count FROM customer WHERE Cust_Email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

/**
 * Check if phone already exists
 */
function phoneExists($conn, $phone) {
    $query = "SELECT COUNT(*) as count FROM customer WHERE Cust_Phone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

/**
 * Generate unique customer ID
 */
function generateCustomerID($conn) {
    do {
        $customer_id = 'CUST' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $query = "SELECT COUNT(*) as count FROM customer WHERE customer_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
    } while ($row['count'] > 0);
    
    return $customer_id;
}

/**
 * Validate password strength
 */
function isPasswordStrong($password) {
    // Minimum 8 characters, at least one uppercase, one lowercase, one number
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

/**
 * Log registration activity
 */
function logRegistrationActivity($conn, $customer_id, $status) {
    try {
        $query = "INSERT INTO activity_log (user_id, user_type, activity, status, 
                  ip_address, user_agent, timestamp) 
                  VALUES (?, 'customer', 'registration', ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->bind_param("isss", $customer_id, $status, $ip_address, $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Registration activity logging failed: " . $e->getMessage());
    }
}

/**
 * Send welcome email
 */
function sendWelcomeEmail($email, $first_name) {
    // Basic email implementation
    // In production, use a proper email service like PHPMailer
    $subject = "Welcome to Paradise Resort!";
    $message = "Dear $first_name,\n\n";
    $message .= "Welcome to Paradise Resort! Your account has been successfully created.\n";
    $message .= "You can now book rooms, access exclusive deals, and manage your reservations.\n\n";
    $message .= "Thank you for choosing Paradise Resort!\n\n";
    $message .= "Best regards,\nThe Paradise Resort Team";
    
    $headers = "From: noreply@paradiseresort.com\r\n";
    $headers .= "Reply-To: support@paradiseresort.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Uncomment to actually send emails
    // mail($email, $subject, $message, $headers);
}

/**
 * Increment registration attempts
 */
function incrementRegisterAttempts() {
    $_SESSION['register_attempts']++;
    $_SESSION['last_register_attempt'] = time();
}

/**
 * Redirect with error message
 */
function redirectWithError($error, $params = []) {
    $redirect_url = '../register.php?error=' . urlencode($error);
    
    foreach ($params as $key => $value) {
        $redirect_url .= '&' . urlencode($key) . '=' . urlencode($value);
    }
    
    header("Location: " . $redirect_url);
    exit();
}
?>
