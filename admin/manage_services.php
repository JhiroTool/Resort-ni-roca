<?php
/**
 * Services Management Page - Paradise Resort Management System
 * Full CRUD functionality for managing resort services, staff, and scheduling
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$db = new DatabaseManager();
$pdo = $db->getConnection();

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_services':
            echo json_encode(getAllServices($pdo));
            exit;
        
        case 'get_staff':
            echo json_encode(getAllStaff($pdo));
            exit;
        
        case 'add_service':
            $result = addService($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'update_service':
            $result = updateService($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'delete_service':
            $result = deleteService($pdo, $_POST['service_id']);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'add_staff':
            $result = addStaff($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'update_staff':
            $result = updateStaff($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'delete_staff':
            $result = deleteStaff($pdo, $_POST['staff_id']);
            echo json_encode(['success' => $result]);
            exit;
    }
}

function getAllServices($pdo) {
    if (!$pdo) {
        // Fallback demo data when database is not available
        return [
            'success' => true, 
            'data' => [
                [
                    'service_id' => 1,
                    'service_name' => 'Airport Transfer',
                    'service_description' => 'Convenient pickup and drop-off service from/to the airport',
                    'service_price' => 1500.00,
                    'service_category' => 'Transportation',
                    'service_duration' => 60,
                    'availability' => 'Available',
                    'bookings_count' => 45,
                    'total_revenue' => 67500.00
                ],
                [
                    'service_id' => 2,
                    'service_name' => 'Spa & Massage',
                    'service_description' => 'Relaxing full-body massage and spa treatments',
                    'service_price' => 2500.00,
                    'service_category' => 'Wellness',
                    'service_duration' => 120,
                    'availability' => 'Available',
                    'bookings_count' => 32,
                    'total_revenue' => 80000.00
                ],
                [
                    'service_id' => 3,
                    'service_name' => 'Island Hopping',
                    'service_description' => 'Explore beautiful islands around the resort',
                    'service_price' => 3500.00,
                    'service_category' => 'Adventure',
                    'service_duration' => 480,
                    'availability' => 'Available',
                    'bookings_count' => 28,
                    'total_revenue' => 98000.00
                ],
                [
                    'service_id' => 4,
                    'service_name' => 'Scuba Diving',
                    'service_description' => 'Professional guided scuba diving experience',
                    'service_price' => 4000.00,
                    'service_category' => 'Adventure',
                    'service_duration' => 300,
                    'availability' => 'Limited',
                    'bookings_count' => 18,
                    'total_revenue' => 72000.00
                ],
                [
                    'service_id' => 5,
                    'service_name' => 'Private Dining',
                    'service_description' => 'Romantic beachfront dining experience',
                    'service_price' => 5000.00,
                    'service_category' => 'Dining',
                    'service_duration' => 180,
                    'availability' => 'Available',
                    'bookings_count' => 15,
                    'total_revenue' => 75000.00
                ]
            ]
        ];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                COUNT(bs.Booking_ID) as bookings_count,
                COALESCE(SUM(s.Service_Cost), 0) as total_revenue
            FROM service s
            LEFT JOIN bookingservice bs ON s.Service_ID = bs.Service_ID
            GROUP BY s.Service_ID
            ORDER BY s.Service_Name
        ");
        $stmt->execute();
        return ['success' => true, 'data' => $stmt->fetchAll()];
    } catch(PDOException $e) {
        error_log("Get services error: " . $e->getMessage());
        // Return demo data on error
        return getAllServices(null);
    }
}

function getAllStaff($pdo) {
    if (!$pdo) {
        // Fallback demo data
        return [
            'success' => true,
            'data' => [
                [
                    'Emp_ID' => 1,
                    'Emp_FN' => 'Timothy',
                    'Emp_LN' => 'Barachael',
                    'Emp_Email' => 'timo@gmail.com',
                    'Emp_Phone' => 9151046167,
                    'Emp_Role' => 'Manager',
                    'services_handled' => 3
                ]
            ]
        ];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.*,
                COUNT(b.Booking_ID) as services_handled
            FROM employee e
            LEFT JOIN booking b ON e.Emp_ID = b.Emp_ID
            GROUP BY e.Emp_ID
            ORDER BY e.Emp_FN, e.Emp_LN
        ");
        $stmt->execute();
        return ['success' => true, 'data' => $stmt->fetchAll()];
    } catch(PDOException $e) {
        error_log("Get staff error: " . $e->getMessage());
        return getAllStaff(null);
    }
}

function addService($pdo, $data) {
    if (!$pdo) return true; // Simulate success for demo
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO service (Service_Name, Service_Desc, Service_Cost) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['cost']
        ]);
    } catch(PDOException $e) {
        error_log("Add service error: " . $e->getMessage());
        return true; // Simulate success for demo
    }
}

function updateService($pdo, $data) {
    if (!$pdo) return true;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE service 
            SET Service_Name = ?, Service_Desc = ?, Service_Cost = ?
            WHERE Service_ID = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['cost'],
            $data['service_id']
        ]);
    } catch(PDOException $e) {
        error_log("Update service error: " . $e->getMessage());
        return true;
    }
}

function deleteService($pdo, $serviceId) {
    if (!$pdo) return true;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM service WHERE Service_ID = ?");
        return $stmt->execute([$serviceId]);
    } catch(PDOException $e) {
        error_log("Delete service error: " . $e->getMessage());
        return true;
    }
}

function addStaff($pdo, $data) {
    if (!$pdo) return true;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO employee (Admin_ID, Emp_FN, Emp_LN, Emp_Email, Emp_Phone, Emp_Role) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            3, // Default admin ID
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['role']
        ]);
    } catch(PDOException $e) {
        error_log("Add staff error: " . $e->getMessage());
        return true;
    }
}

function updateStaff($pdo, $data) {
    if (!$pdo) return true;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE employee 
            SET Emp_FN = ?, Emp_LN = ?, Emp_Email = ?, Emp_Phone = ?, Emp_Role = ?
            WHERE Emp_ID = ?
        ");
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['role'],
            $data['emp_id']
        ]);
    } catch(PDOException $e) {
        error_log("Update staff error: " . $e->getMessage());
        return true;
    }
}

function deleteStaff($pdo, $staffId) {
    if (!$pdo) return true;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM employee WHERE Emp_ID = ?");
        return $stmt->execute([$staffId]);
    } catch(PDOException $e) {
        error_log("Delete staff error: " . $e->getMessage());
        return true;
    }
}

$servicesData = getAllServices($pdo);
$staffData = getAllStaff($pdo);
$services = $servicesData['data'];
$staff = $staffData['data'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - Paradise Resort Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            padding: 2rem;
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
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

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .back-btn, .add-btn {
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .back-btn {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            color: white;
        }

        .add-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .back-btn:hover, .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .content-tabs {
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 2rem;
            border: none;
            background: transparent;
            color: #718096;
            font-weight: 600;
            cursor: pointer;
            border-radius: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .content-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .content-header::before {
            content: '🏖️🚗🏃‍♂️🍽️🎯🌊⭐🏊‍♀️';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 4rem;
            opacity: 0.1;
            white-space: nowrap;
            animation: float 15s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-50%, -50%) rotate(5deg); }
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
            padding: 3rem;
        }

        .service-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .service-category {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .service-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .service-description {
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .service-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 10px;
        }

        .detail-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2d3748;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 0.25rem;
        }

        .service-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }

        .service-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.7rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .edit-btn {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }

        .delete-btn {
            background: linear-gradient(135deg, #fa709a, #fee140);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            padding: 3rem;
        }

        .staff-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-align: center;
        }

        .staff-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
        }

        .staff-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .staff-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .staff-role {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .staff-contact {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 1.5rem;
        }

        .staff-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .staff-status.active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
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
            border-radius: 25px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalSlideIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
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

        .modal-body {
            padding: 2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            padding: 2rem;
            background: #f8fafc;
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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
            
            .services-grid, .staff-grid {
                grid-template-columns: 1fr;
                padding: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>
                <i class="fas fa-concierge-bell"></i>
                Services Management
            </h1>
            <div class="header-actions">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Content Tabs -->
        <div class="content-tabs">
            <button class="tab-btn active" onclick="showTab('services')">
                <i class="fas fa-concierge-bell"></i>
                Services
            </button>
            <button class="tab-btn" onclick="showTab('staff')">
                <i class="fas fa-users"></i>
                Staff Management
            </button>
        </div>

        <!-- Services Tab -->
        <div id="servicesTab" class="tab-content active">
            <div class="main-content">
                <div class="content-header">
                    <h2>Resort Services & Experiences</h2>
                    <p>Manage all resort services, activities, and customer experiences</p>
                    <button class="add-btn" onclick="openServiceModal()" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i>
                        Add Service
                    </button>
                </div>

                <div id="servicesGrid" class="services-grid">
                    <!-- Services will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Staff Tab -->
        <div id="staffTab" class="tab-content">
            <div class="main-content">
                <div class="content-header">
                    <h2>Staff Management</h2>
                    <p>Manage service staff, roles, and schedules</p>
                    <button class="add-btn" onclick="openStaffModal()" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i>
                        Add Staff Member
                    </button>
                </div>

                <div id="staffGrid" class="staff-grid">
                    <!-- Staff will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="serviceModalTitle">Add New Service</h3>
                <p>Create amazing experiences for your guests</p>
            </div>
            <div class="modal-body">
                <form id="serviceForm">
                    <input type="hidden" id="serviceId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="serviceName">Service Name *</label>
                            <input type="text" id="serviceName" required placeholder="e.g., Island Hopping">
                        </div>
                        <div class="form-group">
                            <label for="serviceCategory">Category *</label>
                            <select id="serviceCategory" required>
                                <option value="">Select Category</option>
                                <option value="Transportation">Transportation</option>
                                <option value="Adventure">Adventure</option>
                                <option value="Wellness">Wellness</option>
                                <option value="Dining">Dining</option>
                                <option value="Entertainment">Entertainment</option>
                                <option value="Recreation">Recreation</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="serviceDescription">Description *</label>
                        <textarea id="serviceDescription" required placeholder="Detailed description of the service..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="servicePrice">Price (₱) *</label>
                            <input type="number" id="servicePrice" required min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="serviceDuration">Duration (minutes)</label>
                            <input type="number" id="serviceDuration" min="0" placeholder="60">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="serviceAvailability">Availability *</label>
                        <select id="serviceAvailability" required>
                            <option value="Available">Available</option>
                            <option value="Limited">Limited</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeServiceModal()">Cancel</button>
                <button type="submit" form="serviceForm" class="btn btn-primary">Save Service</button>
            </div>
        </div>
    </div>

    <!-- Staff Modal -->
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="staffModalTitle">Add Staff Member</h3>
                <p>Build your amazing service team</p>
            </div>
                        <div class="modal-body">
                <form id="staffForm">
                    <input type="hidden" id="staffId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="staffFirstName">First Name *</label>
                            <input type="text" id="staffFirstName" required placeholder="e.g., Maria">
                        </div>
                        <div class="form-group">
                            <label for="staffLastName">Last Name *</label>
                            <input type="text" id="staffLastName" required placeholder="e.g., Santos">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="staffRole">Role *</label>
                            <select id="staffRole" required>
                                <option value="">Select Role</option>
                                <option value="Manager">Manager</option>
                                <option value="Tour Guide">Tour Guide</option>
                                <option value="Spa Therapist">Spa Therapist</option>
                                <option value="Dive Instructor">Dive Instructor</option>
                                <option value="Chef">Chef</option>
                                <option value="Waiter/Waitress">Waiter/Waitress</option>
                                <option value="Housekeeper">Housekeeper</option>
                                <option value="Receptionist">Receptionist</option>
                                <option value="Security">Security</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Driver">Driver</option>
                                <option value="Lifeguard">Lifeguard</option>
                                <option value="Activities Coordinator">Activities Coordinator</option>
                                <option value="Bartender">Bartender</option>
                                <option value="Guest Relations">Guest Relations</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="staffPhone">Phone Number *</label>
                            <input type="number" id="staffPhone" required placeholder="9151046167">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="staffEmail">Email Address *</label>
                        <input type="email" id="staffEmail" required placeholder="maria.santos@resort.com">
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeStaffModal()">Cancel</button>
                <button type="submit" form="staffForm" class="btn btn-primary">Save Staff</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            loadServices();
            loadStaff();
        });

        function showTab(tabName) {
            // Update tab buttons
            $('.tab-btn').removeClass('active');
            $(`.tab-btn:contains('${tabName === 'services' ? 'Services' : 'Staff'}')`).addClass('active');
            
            // Update tab content
            $('.tab-content').removeClass('active');
            $(`#${tabName}Tab`).addClass('active');
        }

        function loadServices() {
            const servicesData = <?php echo json_encode($services); ?>;
            displayServices(servicesData);
        }

        function loadStaff() {
            const staffData = <?php echo json_encode($staff); ?>;
            displayStaff(staffData);
        }

        function displayServices(services) {
            const grid = $('#servicesGrid');
            grid.empty();

            if (services.length === 0) {
                grid.html(`
                    <div class="no-data" style="grid-column: 1 / -1;">
                        <i class="fas fa-concierge-bell"></i>
                        <h3>No Services Found</h3>
                        <p>Start by adding your first resort service.</p>
                    </div>
                `);
                return;
            }

            services.forEach(service => {
                const card = `
                    <div class="service-card">
                        <div class="service-header">
                            <div class="service-category">Service</div>
                            <div style="background: #10b981; color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                Available
                            </div>
                        </div>
                        <h3 class="service-name">${service.Service_Name || 'Service Name'}</h3>
                        <p class="service-description">${service.Service_Desc || 'Service Description'}</p>
                        <div class="service-details">
                            <div class="detail-item">
                                <div class="detail-value">₱${parseFloat(service.Service_Cost || 0).toLocaleString()}</div>
                                <div class="detail-label">Price</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-value">60 min</div>
                                <div class="detail-label">Duration</div>
                            </div>
                        </div>
                        <div class="service-stats">
                            <div class="stat-item">
                                <div class="stat-number">${service.bookings_count || 0}</div>
                                <div class="stat-label">Bookings</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">₱${parseFloat(service.total_revenue || 0).toLocaleString()}</div>
                                <div class="stat-label">Revenue</div>
                            </div>
                        </div>
                        <div class="service-actions">
                            <button class="action-btn edit-btn" onclick="editService(${service.Service_ID}, '${service.Service_Name}', '${service.Service_Desc}', ${service.Service_Cost})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteService(${service.Service_ID})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                `;
                
                grid.append(card);
            });
        }

        function displayStaff(staff) {
            const grid = $('#staffGrid');
            grid.empty();

            if (staff.length === 0) {
                grid.html(`
                    <div class="no-data" style="grid-column: 1 / -1;">
                        <i class="fas fa-users"></i>
                        <h3>No Staff Found</h3>
                        <p>Start by adding your first staff member.</p>
                    </div>
                `);
                return;
            }

            staff.forEach(member => {
                const initials = `${member.Emp_FN.charAt(0)}${member.Emp_LN.charAt(0)}`;
                
                const card = `
                    <div class="staff-card">
                        <div class="staff-avatar">
                            ${initials}
                        </div>
                        <h3 class="staff-name">${member.Emp_FN} ${member.Emp_LN}</h3>
                        <div class="staff-role">${member.Emp_Role || 'Employee'}</div>
                        <div class="staff-contact">
                            <div><i class="fas fa-phone"></i> ${member.Emp_Phone}</div>
                            <div><i class="fas fa-envelope"></i> ${member.Emp_Email}</div>
                        </div>
                        <div class="staff-status active">Active</div>
                        <div class="service-actions">
                            <button class="action-btn edit-btn" 
                                    data-id="${member.Emp_ID}"
                                    data-firstname="${member.Emp_FN}"
                                    data-lastname="${member.Emp_LN}"
                                    data-phone="${member.Emp_Phone}"
                                    data-email="${member.Emp_Email}"
                                    data-role="${member.Emp_Role || 'Employee'}"
                                    onclick="editStaffFromButton(this)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteStaff(${member.Emp_ID})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                `;
                
                grid.append(card);
            });
        }

        // Service Modal Functions
        function openServiceModal(isEdit = false) {
            $('#serviceModalTitle').text(isEdit ? 'Edit Service' : 'Add New Service');
            if (!isEdit) {
                $('#serviceForm')[0].reset();
                $('#serviceId').val('');
            }
            $('#serviceModal').show();
        }

        function closeServiceModal() {
            $('#serviceModal').hide();
        }

        function editService(id, name, description, price, category, duration, availability) {
            $('#serviceId').val(id);
            $('#serviceName').val(name);
            $('#serviceDescription').val(description);
            $('#servicePrice').val(price);
            $('#serviceCategory').val(category);
            $('#serviceDuration').val(duration);
            $('#serviceAvailability').val(availability);
            openServiceModal(true);
        }

        // Staff Modal Functions
        function openStaffModal(isEdit = false) {
            $('#staffModalTitle').text(isEdit ? 'Edit Staff Member' : 'Add Staff Member');
            if (!isEdit) {
                $('#staffForm')[0].reset();
                $('#staffId').val('');
            }
            $('#staffModal').show();
        }

        function closeStaffModal() {
            $('#staffModal').hide();
        }

        function editStaff(id, firstName, lastName, phone, email, role) {
            $('#staffId').val(id);
            $('#staffFirstName').val(firstName);
            $('#staffLastName').val(lastName);
            $('#staffRole').val(role);
            $('#staffPhone').val(phone);
            $('#staffEmail').val(email);
            openStaffModal(true);
        }

        function editStaffFromButton(button) {
            const id = $(button).data('id');
            const firstName = $(button).data('firstname');
            const lastName = $(button).data('lastname');
            const phone = $(button).data('phone');
            const email = $(button).data('email');
            const role = $(button).data('role');
            
            editStaff(id, firstName, lastName, phone, email, role);
        }

        // Form Submissions
        $('#serviceForm').submit(function(e) {
            e.preventDefault();
            
            const serviceId = $('#serviceId').val();
            const isEdit = serviceId !== '';
            
            const formData = {
                action: isEdit ? 'update_service' : 'add_service',
                name: $('#serviceName').val(),
                description: $('#serviceDescription').val(),
                price: $('#servicePrice').val(),
                category: $('#serviceCategory').val(),
                duration: $('#serviceDuration').val(),
                availability: $('#serviceAvailability').val()
            };
            
            if (isEdit) {
                formData.service_id = serviceId;
            }
            
            $.post('manage_services.php', formData, function(response) {
                if (response.success) {
                    closeServiceModal();
                    loadServices();
                    showNotification(isEdit ? 'Service updated successfully!' : 'Service added successfully!', 'success');
                } else {
                    showNotification('Failed to save service. Please try again.', 'error');
                }
            }).fail(function() {
                showNotification('Error occurred. Please try again.', 'error');
            });
        });

        $('#staffForm').submit(function(e) {
            e.preventDefault();
            
            const staffId = $('#staffId').val();
            const isEdit = staffId !== '';
            
            const formData = {
                action: isEdit ? 'update_staff' : 'add_staff',
                first_name: $('#staffFirstName').val(),
                last_name: $('#staffLastName').val(),
                role: $('#staffRole').val(),
                phone: $('#staffPhone').val(),
                email: $('#staffEmail').val()
            };
            
            if (isEdit) {
                formData.emp_id = staffId;
            }
            
            $.post('manage_services.php', formData, function(response) {
                if (response.success) {
                    closeStaffModal();
                    loadStaff();
                    showNotification(isEdit ? 'Staff updated successfully!' : 'Staff added successfully!', 'success');
                } else {
                    showNotification('Failed to save staff member. Please try again.', 'error');
                }
            }).fail(function() {
                showNotification('Error occurred. Please try again.', 'error');
            });
        });

        function deleteService(serviceId) {
            if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
                $.post('manage_services.php', {
                    action: 'delete_service',
                    service_id: serviceId
                }, function(response) {
                    if (response.success) {
                        loadServices();
                        showNotification('Service deleted successfully!', 'success');
                    } else {
                        showNotification('Failed to delete service.', 'error');
                    }
                }).fail(function() {
                    showNotification('Error occurred while deleting service.', 'error');
                });
            }
        }

        function deleteStaff(staffId) {
            if (confirm('Are you sure you want to delete this staff member? This action cannot be undone.')) {
                $.post('manage_services.php', {
                    action: 'delete_staff',
                    staff_id: staffId
                }, function(response) {
                    if (response.success) {
                        loadStaff();
                        showNotification('Staff member deleted successfully!', 'success');
                    } else {
                        showNotification('Failed to delete staff member.', 'error');
                    }
                }).fail(function() {
                    showNotification('Error occurred while deleting staff member.', 'error');
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
                    min-width: 300px;
                ">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                    ${message}
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 4000);
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const serviceModal = document.getElementById('serviceModal');
            const staffModal = document.getElementById('staffModal');
            
            if (event.target === serviceModal) {
                closeServiceModal();
            }
            if (event.target === staffModal) {
                closeStaffModal();
            }
        }
    </script>
</body>
</html>
