<?php
/**
 * Centralized Logout Handler
 * Handles logout for both clients and administrators
 */

session_start();

// Include database configuration for security logging
require_once 'config/database.php';

// Function to log logout activity
function logLogoutActivity($user_id, $user_type) {
    try {
        $db = new DatabaseConnection();
        $conn = $db->connect();
        
        $query = "INSERT INTO activity_log (user_id, user_type, activity, ip_address, user_agent, timestamp) 
                  VALUES (?, ?, 'logout', ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->bind_param("isss", $user_id, $user_type, $ip_address, $user_agent);
        $stmt->execute();
        
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Silently fail - logout should proceed even if logging fails
        error_log("Logout activity logging failed: " . $e->getMessage());
    }
}

// Determine user type and ID before destroying session
$user_type = 'guest';
$user_id = null;
$redirect_page = 'index.php';

if (isset($_SESSION['customer_id'])) {
    $user_type = 'customer';
    $user_id = $_SESSION['customer_id'];
    $redirect_page = 'index.php?logout=success';
} elseif (isset($_SESSION['admin_id'])) {
    $user_type = 'administrator';
    $user_id = $_SESSION['admin_id'];
    $redirect_page = 'index.php?logout=admin_success';
}

// Log the logout activity if user was logged in
if ($user_id) {
    logLogoutActivity($user_id, $user_type);
}

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any authentication cookies
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check for AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Successfully logged out',
        'redirect' => $redirect_page
    ]);
    exit();
}

// Handle redirect parameter
if (isset($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
    
    // Validate redirect URL to prevent open redirect attacks
    $allowed_redirects = [
        'index.php',
        'login.php',
        'register.php'
    ];
    
    if (in_array($redirect, $allowed_redirects)) {
        $redirect_page = $redirect;
    }
}

// Add success message parameter
if (strpos($redirect_page, '?') !== false) {
    $redirect_page .= '&';
} else {
    $redirect_page .= '?';
}

if ($user_type === 'administrator') {
    $redirect_page .= 'logout=admin_success';
} else {
    $redirect_page .= 'logout=success';
}

// Redirect to the appropriate page
header("Location: " . $redirect_page);
exit();
?>
