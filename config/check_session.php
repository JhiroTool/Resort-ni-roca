<?php
/**
 * Session Check API
 * Returns JSON response about current user login status
 */

session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$response = [
    'logged_in' => false,
    'user_type' => null,
    'user_id' => null,
    'name' => null,
    'email' => null
];

// Check if customer is logged in (check both possible session variable names)
if ((isset($_SESSION['customer_id']) || isset($_SESSION['cust_id'])) && isset($_SESSION['customer_name'])) {
    $customer_id = $_SESSION['customer_id'] ?? $_SESSION['cust_id'];
    $response = [
        'logged_in' => true,
        'user_type' => 'customer',
        'user_id' => $customer_id,
        'customer_name' => $_SESSION['customer_name'],
        'customer_email' => $_SESSION['customer_email'] ?? null
    ];
}
// Check if admin is logged in
elseif (isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
    $response = [
        'logged_in' => true,
        'user_type' => 'admin',
        'user_id' => $_SESSION['admin_id'],
        'admin_username' => $_SESSION['admin_username'],
        'admin_role' => $_SESSION['admin_role'] ?? 'admin'
    ];
}

echo json_encode($response);
?>
