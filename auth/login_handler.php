<?php
/**
 * Unified Login Handler
 * Handles authentication for both clients and administrators
 */

require_once '../config/database.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Start session with secure settings (adjusted for development)
ini_set('session.cookie_httponly', 1);
// Only enable secure cookies in production (HTTPS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.use_strict_mode', 1);

session_start();

// Rate limiting
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}

$max_attempts = 10; // Increased from 5 to 10
$lockout_time = 300; // Reduced from 15 minutes to 5 minutes
$current_time = time();

// Check if user is locked out
if ($_SESSION['login_attempts'] >= $max_attempts && 
    ($current_time - $_SESSION['last_attempt']) < $lockout_time) {
    
    $remaining_time = $lockout_time - ($current_time - $_SESSION['last_attempt']);
    redirectWithError('account_locked', ['time' => ceil($remaining_time / 60)]);
}

// Reset attempts after lockout period
if ($_SESSION['login_attempts'] >= $max_attempts && 
    ($current_time - $_SESSION['last_attempt']) >= $lockout_time) {
    $_SESSION['login_attempts'] = 0;
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
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$user_type = $_POST['user_type'] ?? 'client';
$remember_me = isset($_POST['remember_me']);

// Validate required fields
if (empty($email) || empty($password)) {
    incrementLoginAttempts();
    redirectWithError('missing_fields');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    incrementLoginAttempts();
    redirectWithError('invalid_email');
}

// Validate user type
if (!in_array($user_type, ['client', 'admin'])) {
    incrementLoginAttempts();
    redirectWithError('invalid_user_type');
}

try {
    $db = new DatabaseConnection();
    $conn = $db->connect();
    
    if ($user_type === 'admin') {
        $result = authenticateAdmin($conn, $email, $password);
    } else {
        $result = authenticateClient($conn, $email, $password);
    }
    
    if ($result['success']) {
        // Reset login attempts on successful login
        $_SESSION['login_attempts'] = 0;
        
        // Log successful login
        logLoginActivity($conn, $result['user_id'], $user_type, 'success');
        
        // Set session variables
        if ($user_type === 'admin') {
            $_SESSION['admin_id'] = $result['user_id'];
            $_SESSION['admin_username'] = $result['username'];
            $_SESSION['admin_role'] = $result['role'];
            $_SESSION['user_type'] = 'admin';
            
            $redirect_url = '../admin/dashboard.php';
        } else {
            $_SESSION['cust_id'] = $result['user_id'];  // Match your dashboard expectations
            $_SESSION['customer_id'] = $result['user_id']; // Keep both for compatibility
            $_SESSION['customer_name'] = $result['name'];
            $_SESSION['customer_email'] = $result['email'];
            $_SESSION['user_type'] = 'client';
            
            $redirect_url = '../client/dashboard.php';
        }
        
        // Handle remember me functionality
        if ($remember_me) {
            setRememberMeCookie($result['user_id'], $user_type);
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $conn->close();
        
        // Check for redirect parameter
        if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
            $allowed_redirects = [
                '../client/dashboard.php',
                '../admin/dashboard.php',
                '../index.php'
            ];
            
            $redirect = $_GET['redirect'];
            if (in_array($redirect, $allowed_redirects)) {
                $redirect_url = $redirect;
            }
        }
        
        header("Location: " . $redirect_url . "?login=success");
        exit();
        
    } else {
        // Log failed login attempt
        logLoginActivity($conn, null, $user_type, 'failed', $email);
        
        incrementLoginAttempts();
        redirectWithError($result['error']);
    }
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    incrementLoginAttempts();
    redirectWithError('system_error');
}

/**
 * Authenticate administrator
 */
function authenticateAdmin($conn, $email, $password) {
    $query = "SELECT Admin_ID, Admin_Email, Admin_Password 
              FROM administrator 
              WHERE Admin_Email = ? 
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'invalid_credentials'];
    }
    
    $admin = $result->fetch_assoc();
    
    if (!password_verify($password, $admin['Admin_Password'])) {
        return ['success' => false, 'error' => 'invalid_credentials'];
    }
    
    return [
        'success' => true,
        'user_id' => $admin['Admin_ID'],
        'username' => 'Administrator',
        'role' => 'admin'
    ];
}

/**
 * Authenticate client/customer
 */
function authenticateClient($conn, $email, $password) {
    $query = "SELECT Cust_ID, Cust_FN, Cust_LN, Cust_Email, Cust_Password, is_banned 
              FROM customer 
              WHERE Cust_Email = ? AND is_banned = 0 
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'invalid_credentials'];
    }
    
    $customer = $result->fetch_assoc();
    
    if (!password_verify($password, $customer['Cust_Password'])) {
        return ['success' => false, 'error' => 'invalid_credentials'];
    }
    
    return [
        'success' => true,
        'user_id' => $customer['Cust_ID'],
        'name' => $customer['Cust_FN'] . ' ' . $customer['Cust_LN'],
        'email' => $customer['Cust_Email']
    ];
}

/**
 * Log login activity
 */
function logLoginActivity($conn, $user_id, $user_type, $status, $email = null) {
    try {
        $query = "INSERT INTO activity_log (user_id, user_type, activity, status, email, 
                  ip_address, user_agent, timestamp) 
                  VALUES (?, ?, 'login', ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->bind_param("isssss", $user_id, $user_type, $status, $email, $ip_address, $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Login activity logging failed: " . $e->getMessage());
    }
}

/**
 * Set remember me cookie
 */
function setRememberMeCookie($user_id, $user_type) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60); // 30 days
    
    setcookie('remember_user', json_encode([
        'token' => $token,
        'user_id' => $user_id,
        'user_type' => $user_type
    ]), $expires, '/', '', true, true);
    
    // Store token hash in database for verification
    try {
        $db = new DatabaseConnection();
        $conn = $db->connect();
        
        $token_hash = password_hash($token, PASSWORD_DEFAULT);
        $query = "INSERT INTO remember_tokens (user_id, user_type, token_hash, expires_at) 
                  VALUES (?, ?, ?, FROM_UNIXTIME(?))
                  ON DUPLICATE KEY UPDATE 
                  token_hash = VALUES(token_hash), 
                  expires_at = VALUES(expires_at)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issi", $user_id, $user_type, $token_hash, $expires);
        $stmt->execute();
        
        $conn->close();
    } catch (Exception $e) {
        error_log("Remember me token storage failed: " . $e->getMessage());
    }
}

/**
 * Increment login attempts
 */
function incrementLoginAttempts() {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt'] = time();
}

/**
 * Redirect with error message
 */
function redirectWithError($error, $params = []) {
    $redirect_url = 'login.php?error=' . urlencode($error);
    
    foreach ($params as $key => $value) {
        $redirect_url .= '&' . urlencode($key) . '=' . urlencode($value);
    }
    
    header("Location: " . $redirect_url);
    exit();
}
?>
