<?php
/**
 * Room Management System - Paradise Resort
 * Complete CRUD operations for rooms
 */

// Start secure session and check admin login
require_once 'includes/database.php';
$adminId = AuthManager::checkAdminLogin();
$db = new DatabaseManager();
$pdo = $db->getConnection();
$adminInfo = AuthManager::getAdminInfo($pdo, $adminId);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_rooms':
                echo json_encode(getAllRooms($pdo));
                break;
                
            case 'add_room':
                $result = addRoom($pdo, $_POST);
                echo json_encode(['success' => $result, 'message' => $result ? 'Room added successfully' : 'Failed to add room']);
                break;
                
            case 'update_room':
                $result = updateRoom($pdo, $_POST);
                echo json_encode(['success' => $result, 'message' => $result ? 'Room updated successfully' : 'Failed to update room']);
                break;
                
            case 'delete_room':
                $result = deleteRoom($pdo, $_POST['room_id']);
                echo json_encode(['success' => $result, 'message' => $result ? 'Room deleted successfully' : 'Failed to delete room']);
                break;
                
            case 'update_status':
                $result = updateRoomStatus($pdo, $_POST['room_id'], $_POST['status']);
                echo json_encode(['success' => $result, 'message' => $result ? 'Status updated successfully' : 'Failed to update status']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}

// Room management functions
function getAllRooms($pdo) {
    if (!$pdo) return getFallbackRooms();
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                r.*,
                COUNT(b.Booking_ID) as total_bookings,
                SUM(CASE WHEN b.Booking_Status = 'Paid' THEN b.Booking_Cost ELSE 0 END) as total_revenue
            FROM room r
            LEFT JOIN bookingroom br ON r.Room_ID = br.Room_ID
            LEFT JOIN booking b ON br.Booking_ID = b.Booking_ID
            GROUP BY r.Room_ID
            ORDER BY r.Room_Type, r.Room_ID
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get rooms error: " . $e->getMessage());
        return getFallbackRooms();
    }
}

function addRoom($pdo, $data) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO room (Room_Type, Room_Rate, Room_Cap, Room_Status) 
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            AdminUtils::sanitizeInput($data['room_type']),
            (float)$data['room_rate'],
            (int)$data['room_capacity'],
            AdminUtils::sanitizeInput($data['room_status'])
        ]);
    } catch (PDOException $e) {
        error_log("Add room error: " . $e->getMessage());
        return false;
    }
}

function updateRoom($pdo, $data) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE room 
            SET Room_Type = ?, Room_Rate = ?, Room_Cap = ?, Room_Status = ?
            WHERE Room_ID = ?
        ");
        
        return $stmt->execute([
            AdminUtils::sanitizeInput($data['room_type']),
            (float)$data['room_rate'],
            (int)$data['room_capacity'],
            AdminUtils::sanitizeInput($data['room_status']),
            (int)$data['room_id']
        ]);
    } catch (PDOException $e) {
        error_log("Update room error: " . $e->getMessage());
        return false;
    }
}

function updateRoomStatus($pdo, $roomId, $status) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE room SET Room_Status = ? WHERE Room_ID = ?");
        return $stmt->execute([$status, $roomId]);
    } catch (PDOException $e) {
        error_log("Update room status error: " . $e->getMessage());
        return false;
    }
}

function deleteRoom($pdo, $roomId) {
    if (!$pdo) return false;
    
    try {
        // Check if room has bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookingroom WHERE Room_ID = ?");
        $stmt->execute([$roomId]);
        $bookingCount = $stmt->fetchColumn();
        
        if ($bookingCount > 0) {
            return false; // Cannot delete room with existing bookings
        }
        
        $stmt = $pdo->prepare("DELETE FROM room WHERE Room_ID = ?");
        return $stmt->execute([$roomId]);
    } catch (PDOException $e) {
        error_log("Delete room error: " . $e->getMessage());
        return false;
    }
}

function getFallbackRooms() {
    return [
        [
            'Room_ID' => 1,
            'Room_Type' => 'Deluxe',
            'Room_Rate' => 2500.00,
            'Room_Cap' => 20,
            'Room_Status' => 'Available',
            'total_bookings' => 5,
            'total_revenue' => 12500.00
        ],
        [
            'Room_ID' => 2,
            'Room_Type' => 'Pool',
            'Room_Rate' => 1500.00,
            'Room_Cap' => 20,
            'Room_Status' => 'Available',
            'total_bookings' => 3,
            'total_revenue' => 4500.00
        ],
        [
            'Room_ID' => 3,
            'Room_Type' => 'Family',
            'Room_Rate' => 1500.00,
            'Room_Cap' => 10,
            'Room_Status' => 'Unavailable',
            'total_bookings' => 2,
            'total_revenue' => 3000.00
        ]
    ];
}

$rooms = getAllRooms($pdo);
$flash = NotificationManager::getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Paradise Resort Admin</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-dashboard">
        <!-- Sidebar Navigation -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-umbrella-beach"></i>
                    <h2>Paradise Resort</h2>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_bookings.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
                <a href="manage_rooms.php" class="menu-item active">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="manage_customers.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="manage_amenities.php" class="menu-item">
                    <i class="fas fa-star"></i>
                    <span>Amenities</span>
                </a>
                <a href="manage_services.php" class="menu-item">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Services</span>
                </a>
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
            
            <div class="sidebar-footer">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="admin-details">
                        <div class="admin-name"><?php echo htmlspecialchars($adminInfo['Admin_Email'] ?? 'Administrator'); ?></div>
                        <div class="admin-role">System Admin</div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="content-header">
                <div class="header-left">
                    <h1>Room Management</h1>
                    <p>Manage resort rooms, rates, and availability</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openAddRoomModal()">
                        <i class="fas fa-plus"></i> Add New Room
                    </button>
                    <button class="btn btn-secondary" onclick="location.reload()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
            </div>

            <div class="section-content active">
                <!-- Room Statistics -->
                <div class="stats-grid" style="margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4a9960, #66d9a3);">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($rooms); ?></h3>
                            <p>Total Rooms</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count(array_filter($rooms, fn($r) => $r['Room_Status'] === 'Available')); ?></h3>
                            <p>Available Rooms</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count(array_filter($rooms, fn($r) => $r['Room_Status'] === 'Unavailable')); ?></h3>
                            <p>Unavailable Rooms</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₱<?php echo number_format(array_sum(array_column($rooms, 'total_revenue')), 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- Room Cards Grid -->
                <div class="rooms-grid">
                    <?php foreach ($rooms as $room): ?>
                    <div class="room-card" data-room-id="<?php echo $room['Room_ID']; ?>">
                        <div class="room-header">
                            <div class="room-type">
                                <i class="fas fa-bed"></i>
                                <h3><?php echo htmlspecialchars($room['Room_Type']); ?> Room</h3>
                            </div>
                            <div class="room-status">
                                <select class="status-select" data-room-id="<?php echo $room['Room_ID']; ?>">
                                    <option value="Available" <?php echo $room['Room_Status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="Unavailable" <?php echo $room['Room_Status'] === 'Unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                    <option value="Maintenance" <?php echo $room['Room_Status'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="room-details">
                            <div class="room-info">
                                <div class="info-item">
                                    <span class="label">Rate per Night:</span>
                                    <span class="value">₱<?php echo number_format($room['Room_Rate'], 2); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Capacity:</span>
                                    <span class="value"><?php echo $room['Room_Cap']; ?> guests</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Total Bookings:</span>
                                    <span class="value"><?php echo $room['total_bookings'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Revenue Generated:</span>
                                    <span class="value">₱<?php echo number_format($room['total_revenue'] ?? 0, 2); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="room-actions">
                            <button class="btn-icon btn-edit" onclick="editRoom(<?php echo $room['Room_ID']; ?>)" title="Edit Room">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon btn-view" onclick="viewRoomBookings(<?php echo $room['Room_ID']; ?>)" title="View Bookings">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                            <button class="btn-icon btn-danger" onclick="deleteRoom(<?php echo $room['Room_ID']; ?>)" title="Delete Room">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Room Modal -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="roomModalTitle"><i class="fas fa-bed"></i> Add New Room</h2>
                <button class="close-modal" onclick="closeModal('roomModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="roomForm">
                    <input type="hidden" id="roomId" name="room_id">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="roomType">Room Type *</label>
                            <input type="text" id="roomType" name="room_type" required placeholder="e.g., Deluxe, Standard, Suite">
                        </div>
                        <div class="form-group">
                            <label for="roomRate">Rate per Night (₱) *</label>
                            <input type="number" id="roomRate" name="room_rate" required min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="roomCapacity">Guest Capacity *</label>
                            <input type="number" id="roomCapacity" name="room_capacity" required min="1" placeholder="Maximum number of guests">
                        </div>
                        <div class="form-group">
                            <label for="roomStatus">Status *</label>
                            <select id="roomStatus" name="room_status" required>
                                <option value="Available">Available</option>
                                <option value="Unavailable">Unavailable</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('roomModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <span id="submitBtnText">Save Room</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.admin-sidebar');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });

            // Status change handling
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', function() {
                    const roomId = this.dataset.roomId;
                    const newStatus = this.value;
                    updateRoomStatus(roomId, newStatus);
                });
            });

            // Room form submission
            document.getElementById('roomForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const isEdit = formData.get('room_id') !== '';
                
                formData.append('action', isEdit ? 'update_room' : 'add_room');
                
                fetch('manage_rooms.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        closeModal('roomModal');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Network error occurred', 'error');
                });
            });

            // Show flash message if exists
            <?php if ($flash): ?>
            showNotification('<?php echo addslashes($flash['message']); ?>', '<?php echo $flash['type']; ?>');
            <?php endif; ?>
        });

        function openAddRoomModal() {
            document.getElementById('roomModalTitle').innerHTML = '<i class="fas fa-bed"></i> Add New Room';
            document.getElementById('submitBtnText').textContent = 'Save Room';
            document.getElementById('roomForm').reset();
            document.getElementById('roomId').value = '';
            openModal('roomModal');
        }

        function editRoom(roomId) {
            // Find room data from the page (you could also fetch from server)
            const roomCard = document.querySelector(`[data-room-id="${roomId}"]`);
            const roomType = roomCard.querySelector('.room-type h3').textContent.replace(' Room', '');
            const roomRate = roomCard.querySelector('.info-item .value').textContent.replace('₱', '').replace(/,/g, '');
            
            document.getElementById('roomModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Room';
            document.getElementById('submitBtnText').textContent = 'Update Room';
            document.getElementById('roomId').value = roomId;
            document.getElementById('roomType').value = roomType;
            document.getElementById('roomRate').value = roomRate;
            
            // You would need to get other values from the room data
            openModal('roomModal');
        }

        function updateRoomStatus(roomId, status) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('room_id', roomId);
            formData.append('status', status);

            fetch('manage_rooms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                    location.reload(); // Reload to reset the select
                }
            })
            .catch(error => {
                showNotification('Network error occurred', 'error');
                location.reload();
            });
        }

        function deleteRoom(roomId) {
            if (!confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_room');
            formData.append('room_id', roomId);

            fetch('manage_rooms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Network error occurred', 'error');
            });
        }

        function viewRoomBookings(roomId) {
            // Redirect to bookings page with room filter
            window.location.href = `manage_bookings.php?room=${roomId}`;
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button class="close-notification" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            document.getElementById('notificationContainer').appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }
    </script>

    <style>
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .room-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f7fafc;
        }

        .room-type {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .room-type i {
            color: #4a9960;
            font-size: 1.5rem;
        }

        .room-type h3 {
            color: #2d3748;
            margin: 0;
            font-size: 1.3rem;
        }

        .room-details {
            margin-bottom: 1.5rem;
        }

        .room-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-item .label {
            color: #718096;
            font-size: 0.9rem;
        }

        .info-item .value {
            color: #2d3748;
            font-weight: 600;
        }

        .room-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-icon {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #718096;
        }

        .btn-icon:hover {
            background: #f8fafc;
        }

        .btn-edit:hover {
            background: #4a9960;
            color: white;
            border-color: #4a9960;
        }

        .btn-view:hover {
            background: #3182ce;
            color: white;
            border-color: #3182ce;
        }

        .btn-danger:hover {
            background: #e53e3e;
            color: white;
            border-color: #e53e3e;
        }

        .status-select {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.5rem;
            border-radius: 6px;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #718096;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            color: #2d3748;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #2d3748;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4a9960;
            box-shadow: 0 0 0 3px rgba(74, 153, 96, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a9960, #66d9a3);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(74, 153, 96, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #718096;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .notification-container {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 3000;
        }

        .notification {
            background: white;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 300px;
        }

        .notification.success {
            border-left: 4px solid #48bb78;
        }

        .notification.error {
            border-left: 4px solid #e53e3e;
        }

        .notification.info {
            border-left: 4px solid #4299e1;
        }

        .close-notification {
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            margin-left: auto;
        }

        @media (max-width: 768px) {
            .rooms-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
