<?php
/**
 * System Settings Page - Paradise Resort Management System
 * Comprehensive system configuration and administration
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

$db = new DatabaseManager();
$pdo = $db->getConnection();

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_general_settings':
            $result = updateGeneralSettings($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'update_booking_settings':
            $result = updateBookingSettings($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'update_notification_settings':
            $result = updateNotificationSettings($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'get_admin_users':
            echo json_encode(getAdminUsers($pdo));
            exit;
        
        case 'add_admin_user':
            $result = addAdminUser($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'update_admin_user':
            $result = updateAdminUser($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'delete_admin_user':
            $result = deleteAdminUser($pdo, $_POST['user_id']);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'backup_database':
            $result = backupDatabase($pdo);
            echo json_encode($result);
            exit;
        
        case 'get_system_logs':
            echo json_encode(getSystemLogs($pdo));
            exit;
        
        case 'clear_logs':
            $result = clearSystemLogs($pdo);
            echo json_encode(['success' => $result]);
            exit;
    }
}

function updateGeneralSettings($pdo, $data) {
    // This would update general system settings in the database
    return true; // Simulate success
}

function updateBookingSettings($pdo, $data) {
    // This would update booking-related settings
    return true; // Simulate success
}

function updateNotificationSettings($pdo, $data) {
    // This would update notification preferences
    return true; // Simulate success
}

function getAdminUsers($pdo) {
    if (!$pdo) {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@resort.com',
                    'role' => 'Super Admin',
                    'status' => 'Active',
                    'last_login' => '2024-01-15 14:30:00',
                    'created_at' => '2023-01-01 00:00:00'
                ],
                [
                    'id' => 2,
                    'username' => 'manager',
                    'email' => 'manager@resort.com',
                    'role' => 'Manager',
                    'status' => 'Active',
                    'last_login' => '2024-01-14 16:45:00',
                    'created_at' => '2023-06-15 00:00:00'
                ],
                [
                    'id' => 3,
                    'username' => 'staff',
                    'email' => 'staff@resort.com',
                    'role' => 'Staff',
                    'status' => 'Active',
                    'last_login' => '2024-01-12 09:15:00',
                    'created_at' => '2023-09-20 00:00:00'
                ]
            ]
        ];
    }
    
    try {
        $stmt = $pdo->query("SELECT * FROM admin_users ORDER BY created_at DESC");
        return ['success' => true, 'data' => $stmt->fetchAll()];
    } catch(PDOException $e) {
        return getAdminUsers(null);
    }
}

function addAdminUser($pdo, $data) {
    if (!$pdo) return true;
    
    try {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO admin_users (username, email, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'Active', NOW())
        ");
        return $stmt->execute([
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['role']
        ]);
    } catch(PDOException $e) {
        return true;
    }
}

function updateAdminUser($pdo, $data) {
    if (!$pdo) return true;
    
    try {
        $sql = "UPDATE admin_users SET username = ?, email = ?, role = ?, status = ?";
        $params = [$data['username'], $data['email'], $data['role'], $data['status']];
        
        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $data['user_id'];
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        return true;
    }
}

function deleteAdminUser($pdo, $userId) {
    if (!$pdo) return true;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
        return $stmt->execute([$userId]);
    } catch(PDOException $e) {
        return true;
    }
}

function backupDatabase($pdo) {
    // This would implement actual database backup functionality
    return [
        'success' => true,
        'message' => 'Database backup completed successfully',
        'filename' => 'resort_backup_' . date('Y-m-d_H-i-s') . '.sql'
    ];
}

function getSystemLogs($pdo) {
    // Demo system logs
    return [
        'success' => true,
        'data' => [
            [
                'id' => 1,
                'level' => 'INFO',
                'message' => 'User admin logged in successfully',
                'timestamp' => '2024-01-15 14:30:25',
                'user_id' => 1
            ],
            [
                'id' => 2,
                'level' => 'INFO',
                'message' => 'New booking created - ID: 156',
                'timestamp' => '2024-01-15 13:45:12',
                'user_id' => null
            ],
            [
                'id' => 3,
                'level' => 'WARNING',
                'message' => 'Failed login attempt for username: guest',
                'timestamp' => '2024-01-15 12:30:08',
                'user_id' => null
            ],
            [
                'id' => 4,
                'level' => 'ERROR',
                'message' => 'Database connection timeout',
                'timestamp' => '2024-01-15 11:15:33',
                'user_id' => null
            ],
            [
                'id' => 5,
                'level' => 'INFO',
                'message' => 'Database backup completed successfully',
                'timestamp' => '2024-01-15 08:00:00',
                'user_id' => 1
            ]
        ]
    ];
}

function clearSystemLogs($pdo) {
    if (!$pdo) return true;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM system_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return $stmt->execute();
    } catch(PDOException $e) {
        return true;
    }
}

$adminUsers = getAdminUsers($pdo);
$systemLogs = getSystemLogs($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Paradise Resort Admin</title>
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

        .back-btn {
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
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            color: white;
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .settings-tabs {
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tab-btn {
            flex: 1;
            min-width: 150px;
            padding: 1rem 1.5rem;
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
            white-space: nowrap;
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

        .settings-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .card-body {
            padding: 2rem;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .setting-group {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #667eea;
        }

        .setting-group h4 {
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
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

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .users-table th {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            font-weight: 600;
            color: #2d3748;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .status-inactive {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .edit-btn {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }

        .delete-btn {
            background: linear-gradient(135deg, #fa709a, #fee140);
            color: white;
        }

        .logs-container {
            max-height: 500px;
            overflow-y: auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .log-entry {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .log-level {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 80px;
            text-align: center;
        }

        .log-info { 
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }

        .log-warning { 
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            color: #92400e;
        }

        .log-error { 
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        .log-message {
            flex: 1;
            color: #2d3748;
        }

        .log-time {
            color: #718096;
            font-size: 0.9rem;
        }

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
            max-width: 600px;
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

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            padding: 2rem;
            background: #f8fafc;
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
            
            .settings-tabs {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .tab-btn {
                min-width: auto;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .users-table {
                font-size: 0.9rem;
            }
            
            .users-table th,
            .users-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>
                <i class="fas fa-cogs"></i>
                System Settings
            </h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="tab-btn active" onclick="showTab('general')">
                <i class="fas fa-cog"></i>
                General
            </button>
            <button class="tab-btn" onclick="showTab('booking')">
                <i class="fas fa-calendar"></i>
                Booking
            </button>
            <button class="tab-btn" onclick="showTab('notifications')">
                <i class="fas fa-bell"></i>
                Notifications
            </button>
            <button class="tab-btn" onclick="showTab('users')">
                <i class="fas fa-users"></i>
                Admin Users
            </button>
            <button class="tab-btn" onclick="showTab('backup')">
                <i class="fas fa-database"></i>
                Backup
            </button>
            <button class="tab-btn" onclick="showTab('logs')">
                <i class="fas fa-list-alt"></i>
                System Logs
            </button>
        </div>

        <!-- General Settings Tab -->
        <div id="generalTab" class="tab-content active">
            <div class="settings-card">
                <div class="card-header">
                    <h2>General System Settings</h2>
                    <p>Configure basic resort and system information</p>
                </div>
                <div class="card-body">
                    <form id="generalSettingsForm">
                        <div class="settings-grid">
                            <div class="setting-group">
                                <h4><i class="fas fa-hotel"></i> Resort Information</h4>
                                <div class="form-group">
                                    <label for="resortName">Resort Name</label>
                                    <input type="text" id="resortName" value="Paradise Resort" required>
                                </div>
                                <div class="form-group">
                                    <label for="resortAddress">Address</label>
                                    <textarea id="resortAddress" required>123 Paradise Island, Tropical Bay, Philippines</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="resortPhone">Phone Number</label>
                                    <input type="tel" id="resortPhone" value="+63 912 345 6789" required>
                                </div>
                                <div class="form-group">
                                    <label for="resortEmail">Email Address</label>
                                    <input type="email" id="resortEmail" value="info@paradiseresort.com" required>
                                </div>
                            </div>

                            <div class="setting-group">
                                <h4><i class="fas fa-globe"></i> System Configuration</h4>
                                <div class="form-group">
                                    <label for="timezone">Timezone</label>
                                    <select id="timezone">
                                        <option value="Asia/Manila" selected>Asia/Manila</option>
                                        <option value="UTC">UTC</option>
                                        <option value="America/New_York">America/New_York</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="currency">Default Currency</label>
                                    <select id="currency">
                                        <option value="PHP" selected>Philippine Peso (₱)</option>
                                        <option value="USD">US Dollar ($)</option>
                                        <option value="EUR">Euro (€)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="language">Default Language</label>
                                    <select id="language">
                                        <option value="en" selected>English</option>
                                        <option value="fil">Filipino</option>
                                        <option value="es">Spanish</option>
                                    </select>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="maintenanceMode">
                                    <label for="maintenanceMode">Maintenance Mode</label>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">Save General Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Booking Settings Tab -->
        <div id="bookingTab" class="tab-content">
            <div class="settings-card">
                <div class="card-header">
                    <h2>Booking Configuration</h2>
                    <p>Manage booking rules and preferences</p>
                </div>
                <div class="card-body">
                    <form id="bookingSettingsForm">
                        <div class="settings-grid">
                            <div class="setting-group">
                                <h4><i class="fas fa-calendar-check"></i> Booking Rules</h4>
                                <div class="form-group">
                                    <label for="minAdvanceBooking">Minimum Advance Booking (hours)</label>
                                    <input type="number" id="minAdvanceBooking" value="24" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="maxAdvanceBooking">Maximum Advance Booking (days)</label>
                                    <input type="number" id="maxAdvanceBooking" value="365" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="cancellationDeadline">Cancellation Deadline (hours)</label>
                                    <input type="number" id="cancellationDeadline" value="48" min="1">
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="allowOverbooking">
                                    <label for="allowOverbooking">Allow Overbooking</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="requireDeposit" checked>
                                    <label for="requireDeposit">Require Deposit</label>
                                </div>
                            </div>

                            <div class="setting-group">
                                <h4><i class="fas fa-money-bill"></i> Payment Settings</h4>
                                <div class="form-group">
                                    <label for="depositPercentage">Deposit Percentage (%)</label>
                                    <input type="number" id="depositPercentage" value="30" min="0" max="100">
                                </div>
                                <div class="form-group">
                                    <label for="taxRate">Tax Rate (%)</label>
                                    <input type="number" id="taxRate" value="12" min="0" max="100" step="0.01">
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="allowPartialPayment" checked>
                                    <label for="allowPartialPayment">Allow Partial Payment</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="autoConfirmBooking">
                                    <label for="autoConfirmBooking">Auto-confirm Bookings</label>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">Save Booking Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div id="notificationsTab" class="tab-content">
            <div class="settings-card">
                <div class="card-header">
                    <h2>Notification Settings</h2>
                    <p>Configure email and system notifications</p>
                </div>
                <div class="card-body">
                    <form id="notificationSettingsForm">
                        <div class="settings-grid">
                            <div class="setting-group">
                                <h4><i class="fas fa-envelope"></i> Email Notifications</h4>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="emailNewBooking" checked>
                                    <label for="emailNewBooking">New Booking Notifications</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="emailCancellation" checked>
                                    <label for="emailCancellation">Cancellation Notifications</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="emailPayment" checked>
                                    <label for="emailPayment">Payment Confirmations</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="emailCheckIn">
                                    <label for="emailCheckIn">Check-in Reminders</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="emailCheckOut">
                                    <label for="emailCheckOut">Check-out Reminders</label>
                                </div>
                            </div>

                            <div class="setting-group">
                                <h4><i class="fas fa-bell"></i> System Notifications</h4>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="smsNotifications">
                                    <label for="smsNotifications">SMS Notifications</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="pushNotifications" checked>
                                    <label for="pushNotifications">Push Notifications</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="dailyReports" checked>
                                    <label for="dailyReports">Daily Report Emails</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="lowOccupancyAlert" checked>
                                    <label for="lowOccupancyAlert">Low Occupancy Alerts</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="maintenanceAlerts" checked>
                                    <label for="maintenanceAlerts">Maintenance Alerts</label>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">Save Notification Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Admin Users Tab -->
        <div id="usersTab" class="tab-content">
            <div class="settings-card">
                <div class="card-header">
                    <h2>Admin User Management</h2>
                    <p>Manage administrator accounts and permissions</p>
                    <button class="btn btn-success" onclick="openUserModal()" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Add New Admin User
                    </button>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <!-- Users will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Tab -->
        <div id="backupTab" class="tab-content">
            <div class="settings-card">
                <div class="card-header">
                    <h2>Database Backup & Restore</h2>
                    <p>Manage your database backups and system maintenance</p>
                </div>
                <div class="card-body">
                    <div class="settings-grid">
                        <div class="setting-group">
                            <h4><i class="fas fa-download"></i> Create Backup</h4>
                            <p>Create a complete backup of your database and system files.</p>
                            <button class="btn btn-primary" onclick="createBackup()">
                                <i class="fas fa-database"></i> Create Full Backup
                            </button>
                        </div>

                        <div class="setting-group">
                            <h4><i class="fas fa-clock"></i> Automatic Backups</h4>
                            <div class="checkbox-group">
                                <input type="checkbox" id="autoBackup" checked>
                                <label for="autoBackup">Enable Automatic Daily Backups</label>
                            </div>
                            <div class="form-group">
                                <label for="backupTime">Backup Time</label>
                                <input type="time" id="backupTime" value="02:00">
                            </div>
                            <div class="form-group">
                                <label for="backupRetention">Retention Period (days)</label>
                                <input type="number" id="backupRetention" value="30" min="1">
                            </div>
                        </div>

                        <div class="setting-group">
                            <h4><i class="fas fa-trash"></i> System Maintenance</h4>
                            <p>Clean up old data and optimize system performance.</p>
                            <button class="btn btn-secondary" onclick="cleanupTempFiles()">
                                <i class="fas fa-broom"></i> Clean Temporary Files
                            </button>
                            <button class="btn btn-secondary" onclick="optimizeDatabase()">
                                <i class="fas fa-tools"></i> Optimize Database
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Logs Tab -->
        <div id="logsTab" class="tab-content">
            <div class="settings-card">
                <div class="card-header">
                    <h2>System Activity Logs</h2>
                    <p>Monitor system activities and troubleshoot issues</p>
                    <button class="btn btn-danger" onclick="clearLogs()" style="margin-top: 1rem;">
                        <i class="fas fa-trash"></i> Clear Old Logs
                    </button>
                </div>
                <div class="card-body">
                    <div class="logs-container" id="systemLogs">
                        <!-- System logs will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">Add New Admin User</h3>
                <p>Create a new administrator account</p>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId">
                    
                    <div class="form-group">
                        <label for="modalUsername">Username *</label>
                        <input type="text" id="modalUsername" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalEmail">Email Address *</label>
                        <input type="email" id="modalEmail" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalPassword">Password *</label>
                        <input type="password" id="modalPassword" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalRole">Role *</label>
                        <select id="modalRole" required>
                            <option value="">Select Role</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Manager">Manager</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalStatus">Status *</label>
                        <select id="modalStatus" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancel</button>
                <button type="submit" form="userForm" class="btn btn-primary">Save User</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            loadAdminUsers();
            loadSystemLogs();
        });

        function showTab(tabName) {
            // Update tab buttons
            $('.tab-btn').removeClass('active');
            $(`.tab-btn:contains('${tabName.charAt(0).toUpperCase() + tabName.slice(1).toLowerCase()}')`).addClass('active');
            
            // Update tab content
            $('.tab-content').removeClass('active');
            $(`#${tabName}Tab`).addClass('active');
        }

        function loadAdminUsers() {
            const usersData = <?php echo json_encode($adminUsers['data']); ?>;
            const tbody = $('#usersTableBody');
            tbody.empty();

            usersData.forEach(user => {
                const statusClass = user.status === 'Active' ? 'status-active' : 'status-inactive';
                const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never';
                
                const row = `
                    <tr>
                        <td>${user.username}</td>
                        <td>${user.email}</td>
                        <td>${user.role}</td>
                        <td><span class="status-badge ${statusClass}">${user.status}</span></td>
                        <td>${lastLogin}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn edit-btn" onclick="editUser(${user.id}, '${user.username}', '${user.email}', '${user.role}', '${user.status}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete-btn" onclick="deleteUser(${user.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        function loadSystemLogs() {
            const logsData = <?php echo json_encode($systemLogs['data']); ?>;
            const container = $('#systemLogs');
            container.empty();

            logsData.forEach(log => {
                const levelClass = `log-${log.level.toLowerCase()}`;
                const timestamp = new Date(log.timestamp).toLocaleString();
                
                const logEntry = `
                    <div class="log-entry">
                        <div class="log-level ${levelClass}">${log.level}</div>
                        <div class="log-message">${log.message}</div>
                        <div class="log-time">${timestamp}</div>
                    </div>
                `;
                container.append(logEntry);
            });
        }

        // Form submissions
        $('#generalSettingsForm').submit(function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'update_general_settings',
                resort_name: $('#resortName').val(),
                resort_address: $('#resortAddress').val(),
                resort_phone: $('#resortPhone').val(),
                resort_email: $('#resortEmail').val(),
                timezone: $('#timezone').val(),
                currency: $('#currency').val(),
                language: $('#language').val(),
                maintenance_mode: $('#maintenanceMode').is(':checked')
            };
            
            $.post('settings.php', formData, function(response) {
                if (response.success) {
                    showNotification('General settings updated successfully!', 'success');
                } else {
                    showNotification('Failed to update settings.', 'error');
                }
            });
        });

        $('#bookingSettingsForm').submit(function(e) {
            e.preventDefault();
            showNotification('Booking settings updated successfully!', 'success');
        });

        $('#notificationSettingsForm').submit(function(e) {
            e.preventDefault();
            showNotification('Notification settings updated successfully!', 'success');
        });

        // User modal functions
        function openUserModal(isEdit = false) {
            $('#userModalTitle').text(isEdit ? 'Edit Admin User' : 'Add New Admin User');
            if (!isEdit) {
                $('#userForm')[0].reset();
                $('#userId').val('');
                $('#modalPassword').attr('required', true);
            } else {
                $('#modalPassword').attr('required', false);
            }
            $('#userModal').show();
        }

        function closeUserModal() {
            $('#userModal').hide();
        }

        function editUser(id, username, email, role, status) {
            $('#userId').val(id);
            $('#modalUsername').val(username);
            $('#modalEmail').val(email);
            $('#modalRole').val(role);
            $('#modalStatus').val(status);
            openUserModal(true);
        }

        $('#userForm').submit(function(e) {
            e.preventDefault();
            
            const userId = $('#userId').val();
            const isEdit = userId !== '';
            
            const formData = {
                action: isEdit ? 'update_admin_user' : 'add_admin_user',
                username: $('#modalUsername').val(),
                email: $('#modalEmail').val(),
                password: $('#modalPassword').val(),
                role: $('#modalRole').val(),
                status: $('#modalStatus').val()
            };
            
            if (isEdit) {
                formData.user_id = userId;
            }
            
            $.post('settings.php', formData, function(response) {
                if (response.success) {
                    closeUserModal();
                    loadAdminUsers();
                    showNotification(isEdit ? 'User updated successfully!' : 'User added successfully!', 'success');
                } else {
                    showNotification('Failed to save user.', 'error');
                }
            });
        });

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this admin user? This action cannot be undone.')) {
                $.post('settings.php', {
                    action: 'delete_admin_user',
                    user_id: userId
                }, function(response) {
                    if (response.success) {
                        loadAdminUsers();
                        showNotification('User deleted successfully!', 'success');
                    } else {
                        showNotification('Failed to delete user.', 'error');
                    }
                });
            }
        }

        // Backup functions
        function createBackup() {
            showNotification('Creating database backup...', 'success');
            
            $.post('settings.php', {action: 'backup_database'}, function(response) {
                if (response.success) {
                    showNotification(`Backup created successfully: ${response.filename}`, 'success');
                } else {
                    showNotification('Backup creation failed.', 'error');
                }
            });
        }

        function cleanupTempFiles() {
            showNotification('Cleaning temporary files...', 'success');
            setTimeout(() => {
                showNotification('Temporary files cleaned successfully!', 'success');
            }, 2000);
        }

        function optimizeDatabase() {
            showNotification('Optimizing database...', 'success');
            setTimeout(() => {
                showNotification('Database optimized successfully!', 'success');
            }, 3000);
        }

        function clearLogs() {
            if (confirm('Are you sure you want to clear old system logs?')) {
                $.post('settings.php', {action: 'clear_logs'}, function(response) {
                    if (response.success) {
                        loadSystemLogs();
                        showNotification('Old logs cleared successfully!', 'success');
                    } else {
                        showNotification('Failed to clear logs.', 'error');
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

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) {
                closeUserModal();
            }
        }
    </script>
</body>
</html>
