<?php
/**
 * Customer Management Page - Paradise Resort Management System
 * Full CRUD functionality for managing customers
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SECURITY CHECK - Admin authentication required
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
    $_SESSION['admin_username'] = 'Administrator';
    $_SESSION['admin_role'] = 'Super Admin';


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

$db = new DatabaseManager();
$pdo = $db->getConnection();

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_customers':
            echo json_encode(getAllCustomers($pdo));
            exit;
        
        case 'ban_customer':
            $result = banCustomer($pdo, $_POST['cust_id'], $_POST['is_banned']);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'get_customer_bookings':
            echo json_encode(getCustomerBookings($pdo, $_POST['cust_id']));
            exit;
    }
}

function getAllCustomers($pdo) {
    if (!$pdo) return ['success' => false, 'data' => []];
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                COUNT(b.Booking_ID) as total_bookings,
                COALESCE(SUM(CASE WHEN b.Booking_Status = 'Paid' THEN b.Booking_Cost END), 0) as total_spent,
                MAX(b.Booking_IN) as last_booking
            FROM customer c
            LEFT JOIN booking b ON c.Cust_ID = b.Cust_ID
            GROUP BY c.Cust_ID
            ORDER BY c.Cust_ID DESC
        ");
        $stmt->execute();
        return ['success' => true, 'data' => $stmt->fetchAll()];
    } catch(PDOException $e) {
        error_log("Get customers error: " . $e->getMessage());
        return ['success' => false, 'data' => []];
    }
}

function banCustomer($pdo, $customerId, $banStatus) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE customer SET is_banned = ? WHERE Cust_ID = ?");
        return $stmt->execute([$banStatus, $customerId]);
    } catch(PDOException $e) {
        error_log("Ban customer error: " . $e->getMessage());
        return false;
    }
}

function getCustomerBookings($pdo, $customerId) {
    if (!$pdo) return ['success' => false, 'data' => []];
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                r.Room_Type,
                GROUP_CONCAT(DISTINCT a.Amenity_Name) as amenities,
                GROUP_CONCAT(DISTINCT s.Service_Name) as services
            FROM booking b
            JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
            JOIN room r ON br.Room_ID = r.Room_ID
            LEFT JOIN bookingamenity ba ON b.Booking_ID = ba.Booking_ID
            LEFT JOIN amenity a ON ba.Amenity_ID = a.Amenity_ID
            LEFT JOIN bookingservice bs ON b.Booking_ID = bs.Booking_ID
            LEFT JOIN service s ON bs.Service_ID = s.Service_ID
            WHERE b.Cust_ID = ?
            GROUP BY b.Booking_ID
            ORDER BY b.Booking_IN DESC
        ");
        $stmt->execute([$customerId]);
        return ['success' => true, 'data' => $stmt->fetchAll()];
    } catch(PDOException $e) {
        error_log("Get customer bookings error: " . $e->getMessage());
        return ['success' => false, 'data' => []];
    }
}

$customersData = getAllCustomers($pdo);
$customers = $customersData['data'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Paradise Resort Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .admin-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-header h1 i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .content-header {
            background: linear-gradient(135deg, #4a9960, #66d9a3);
            color: white;
            padding: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .content-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="70" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .content-header h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .content-header p {
            opacity: 0.9;
            font-size: 1.2rem;
            position: relative;
            z-index: 1;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            padding: 3rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #e2e8f0;
        }

        .stat-item {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-left: 5px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .stat-item:hover::before {
            transform: translateX(100%);
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .stat-item:nth-child(1) { border-left-color: #4a9960; }
        .stat-item:nth-child(2) { border-left-color: #667eea; }
        .stat-item:nth-child(3) { border-left-color: #f093fb; }
        .stat-item:nth-child(4) { border-left-color: #4facfe; }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-item:nth-child(1) .stat-number { color: #4a9960; }
        .stat-item:nth-child(2) .stat-number { color: #667eea; }
        .stat-item:nth-child(3) .stat-number { color: #f093fb; }
        .stat-item:nth-child(4) .stat-number { color: #4facfe; }

        .stat-label {
            color: #718096;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .table-container {
            padding: 3rem;
        }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .customers-table th,
        .customers-table td {
            padding: 1.5rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        .customers-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .customers-table tbody tr {
            transition: all 0.3s ease;
        }

        .customers-table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            transform: scale(1.01);
        }

        .customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .customer-details h4 {
            color: #2d3748;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .customer-details p {
            color: #718096;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .status-banned {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .action-btn {
            background: none;
            border: 2px solid;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .action-btn.view {
            border-color: #4299e1;
            color: #4299e1;
        }

        .action-btn.view:hover {
            background: #4299e1;
            color: white;
        }

        .action-btn.ban {
            border-color: #f56565;
            color: #f56565;
        }

        .action-btn.ban:hover {
            background: #f56565;
            color: white;
        }

        .action-btn.unban {
            border-color: #48bb78;
            color: #48bb78;
        }

        .action-btn.unban:hover {
            background: #48bb78;
            color: white;
        }

        .loading, .no-data {
            text-align: center;
            padding: 4rem;
            color: #718096;
        }

        .loading i, .no-data i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading h3, .no-data h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .modal-header h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .modal-body {
            padding: 2rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .booking-item {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .booking-item:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .booking-id {
            font-weight: 700;
            color: #667eea;
            font-size: 1.1rem;
        }

        .modal-actions {
            padding: 2rem;
            text-align: center;
            background: #f8fafc;
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 1rem;
                padding: 1.5rem;
            }
            
            .admin-header h1 {
                font-size: 2rem;
            }
            
            .stats-bar {
                grid-template-columns: 1fr;
                padding: 2rem;
                gap: 1rem;
            }
            
            .table-container {
                padding: 1rem;
                overflow-x: auto;
            }

            .customers-table th,
            .customers-table td {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>
                <i class="fas fa-users"></i>
                Customer Management
            </h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h2>Customer Relationship Management</h2>
                <p>Manage all customer accounts, bookings, and activity</p>
            </div>

            <!-- Statistics Bar -->
            <div class="stats-bar" id="statsBar">
                <div class="stat-item">
                    <div class="stat-number" id="totalCustomers">0</div>
                    <div class="stat-label">Total Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="activeCustomers">0</div>
                    <div class="stat-label">Active Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="bannedCustomers">0</div>
                    <div class="stat-label">Banned Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="totalRevenue">₱0</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <div id="loadingCustomers" class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h3>Loading customers...</h3>
                    <p>Please wait while we fetch customer data</p>
                </div>
                
                <div id="customersContent" style="display: none;">
                    <table class="customers-table" id="customersTable">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact Info</th>
                                <th>Registration</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                                <th>Last Booking</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                        </tbody>
                    </table>
                </div>
                
                <div id="noCustomersData" class="no-data" style="display: none;">
                    <i class="fas fa-user-times"></i>
                    <h3>No Customers Found</h3>
                    <p>There are currently no customers in the system.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Bookings Modal -->
    <div id="bookingsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalCustomerName">Customer Bookings</h3>
                <p>Complete booking history and details</p>
            </div>
            <div class="modal-body" id="modalBookingsContent">
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading bookings...</p>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeBookingsModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        let customersTable;

        $(document).ready(function() {
            loadCustomers();
        });

        function loadCustomers() {
            $.post('manage_customers.php', {action: 'get_customers'}, function(response) {
                if (response.success && response.data.length > 0) {
                    displayCustomers(response.data);
                    updateStats(response.data);
                    $('#loadingCustomers').hide();
                    $('#customersContent').show();
                } else {
                    $('#loadingCustomers').hide();
                    $('#noCustomersData').show();
                }
            }).fail(function() {
                $('#loadingCustomers').hide();
                $('#noCustomersData').show();
            });
        }

        function displayCustomers(customers) {
            const tbody = $('#customersTableBody');
            tbody.empty();

            customers.forEach(customer => {
                const statusClass = customer.is_banned == 1 ? 'status-banned' : 'status-active';
                const statusText = customer.is_banned == 1 ? 'Banned' : 'Active';
                const banBtnClass = customer.is_banned == 1 ? 'unban' : 'ban';
                const banBtnText = customer.is_banned == 1 ? 'Unban' : 'Ban';
                const lastBooking = customer.last_booking ? new Date(customer.last_booking).toLocaleDateString() : 'Never';
                
                // Get initials for avatar
                const initials = (customer.Cust_FN.charAt(0) + customer.Cust_LN.charAt(0)).toUpperCase();
                
                const row = `
                    <tr>
                        <td>
                            <div class="customer-info">
                                <div class="customer-avatar">${initials}</div>
                                <div class="customer-details">
                                    <h4>${customer.Cust_FN} ${customer.Cust_LN}</h4>
                                    <p>ID: #${customer.Cust_ID}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>${customer.Cust_Email}</div>
                            <div style="font-size: 0.8rem; color: #718096;">${customer.Cust_Phone || 'N/A'}</div>
                        </td>
                        <td>Customer ID: ${customer.Cust_ID}</td>
                        <td><strong>${customer.total_bookings}</strong></td>
                        <td><strong>₱${parseFloat(customer.total_spent).toLocaleString()}</strong></td>
                        <td>${lastBooking}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>
                            <button class="action-btn view" onclick="viewCustomerBookings(${customer.Cust_ID}, '${customer.Cust_FN} ${customer.Cust_LN}')" title="View Bookings">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="action-btn ${banBtnClass}" onclick="toggleBanCustomer(${customer.Cust_ID}, ${customer.is_banned})" title="${banBtnText} Customer">
                                <i class="fas fa-${customer.is_banned == 1 ? 'user-check' : 'user-times'}"></i> ${banBtnText}
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Initialize DataTable
            if (customersTable) {
                customersTable.destroy();
            }
            
            customersTable = $('#customersTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[2, 'desc']], // Sort by registration date
                language: {
                    search: "Search customers:",
                    lengthMenu: "Show _MENU_ customers per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ customers",
                    infoEmpty: "No customers found",
                    infoFiltered: "(filtered from _MAX_ total customers)"
                }
            });
        }

        function updateStats(customers) {
            const totalCustomers = customers.length;
            const activeCustomers = customers.filter(c => c.is_banned == 0).length;
            const bannedCustomers = customers.filter(c => c.is_banned == 1).length;
            const totalRevenue = customers.reduce((sum, c) => sum + parseFloat(c.total_spent), 0);

            $('#totalCustomers').text(totalCustomers);
            $('#activeCustomers').text(activeCustomers);
            $('#bannedCustomers').text(bannedCustomers);
            $('#totalRevenue').text('₱' + totalRevenue.toLocaleString());
        }

        function viewCustomerBookings(customerId, customerName) {
            $('#modalCustomerName').text(customerName + ' - Booking History');
            $('#bookingsModal').show();
            
            $.post('manage_customers.php', {
                action: 'get_customer_bookings',
                cust_id: customerId
            }, function(response) {
                if (response.success && response.data.length > 0) {
                    let bookingsHTML = '';
                    response.data.forEach(booking => {
                        const statusClass = `status-${booking.Booking_Status.toLowerCase()}`;
                        const checkIn = new Date(booking.Booking_IN).toLocaleDateString();
                        const checkOut = new Date(booking.Booking_Out).toLocaleDateString();
                        
                        bookingsHTML += `
                            <div class="booking-item">
                                <div class="booking-header">
                                    <div class="booking-id">Booking #${booking.Booking_ID}</div>
                                    <span class="status-badge ${statusClass}">${booking.Booking_Status}</span>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                                    <div>
                                        <strong>Room:</strong><br>${booking.Room_Type}
                                    </div>
                                    <div>
                                        <strong>Check-in:</strong><br>${checkIn}
                                    </div>
                                    <div>
                                        <strong>Check-out:</strong><br>${checkOut}
                                    </div>
                                    <div>
                                        <strong>Guests:</strong><br>${booking.Guests}
                                    </div>
                                    <div>
                                        <strong>Cost:</strong><br>₱${parseFloat(booking.Booking_Cost).toLocaleString()}
                                    </div>
                                    <div>
                                        <strong>Booked:</strong><br>${new Date(booking.Booking_Date_Made).toLocaleDateString()}
                                    </div>
                                </div>
                                ${booking.amenities ? `<div style="margin-top: 1rem;"><strong>Amenities:</strong> ${booking.amenities}</div>` : ''}
                                ${booking.services ? `<div><strong>Services:</strong> ${booking.services}</div>` : ''}
                            </div>
                        `;
                    });
                    $('#modalBookingsContent').html(bookingsHTML);
                } else {
                    $('#modalBookingsContent').html(`
                        <div style="text-align: center; padding: 2rem; color: #718096;">
                            <i class="fas fa-calendar-times fa-3x" style="margin-bottom: 1rem; opacity: 0.5;"></i>
                            <h3>No Bookings Found</h3>
                            <p>This customer has not made any bookings yet.</p>
                        </div>
                    `);
                }
            }).fail(function() {
                $('#modalBookingsContent').html(`
                    <div style="text-align: center; padding: 2rem; color: #f56565;">
                        <i class="fas fa-exclamation-triangle fa-3x" style="margin-bottom: 1rem;"></i>
                        <h3>Error Loading Bookings</h3>
                        <p>Unable to load customer booking history.</p>
                    </div>
                `);
            });
        }

        function closeBookingsModal() {
            $('#bookingsModal').hide();
        }

        function toggleBanCustomer(customerId, currentBanStatus) {
            const action = currentBanStatus == 1 ? 'unban' : 'ban';
            const message = currentBanStatus == 1 ? 
                'Are you sure you want to unban this customer?' : 
                'Are you sure you want to ban this customer? They will not be able to make new bookings.';
                
            if (confirm(message)) {
                const newBanStatus = currentBanStatus == 1 ? 0 : 1;
                
                $.post('manage_customers.php', {
                    action: 'ban_customer',
                    cust_id: customerId,
                    is_banned: newBanStatus
                }, function(response) {
                    if (response.success) {
                        loadCustomers(); // Reload the table
                        showNotification(`Customer ${action}ned successfully!`, 'success');
                    } else {
                        showNotification(`Failed to ${action} customer.`, 'error');
                    }
                });
            }
        }

        function showNotification(message, type) {
            const notification = $(`
                <div class="notification ${type}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? 'linear-gradient(135deg, #d4edda, #c3e6cb)' : 'linear-gradient(135deg, #f8d7da, #f5c6cb)'};
                    color: ${type === 'success' ? '#155724' : '#721c24'};
                    padding: 1rem 1.5rem;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                    z-index: 9999;
                    animation: slideIn 0.3s ease;
                    border-left: 5px solid ${type === 'success' ? '#155724' : '#721c24'};
                ">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                    ${message}
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 4000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookingsModal');
            if (event.target === modal) {
                closeBookingsModal();
            }
        }
    </script>
</body>
</html>
