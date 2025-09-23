<?php
/**
 * Room Management System - Paradise Resort
 * Modern redesigned interface for complete room CRUD operations
 */

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

// Database connection
require_once 'includes/database.php';
$db = new DatabaseManager();
$pdo = $db->getConnection();

// Get admin info for display
$adminInfo = ['Admin_Email' => $_SESSION['admin_username']];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT Admin_Email FROM administrator WHERE Admin_ID = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $result = $stmt->fetch();
        if ($result) {
            $adminInfo = $result;
        }
    } catch (PDOException $e) {
        error_log("Admin info fetch error: " . $e->getMessage());
    }
}

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
            htmlspecialchars(trim($data['room_type']), ENT_QUOTES, 'UTF-8'),
            (float)$data['room_rate'],
            (int)$data['room_capacity'],
            htmlspecialchars(trim($data['room_status']), ENT_QUOTES, 'UTF-8')
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
            htmlspecialchars(trim($data['room_type']), ENT_QUOTES, 'UTF-8'),
            (float)$data['room_rate'],
            (int)$data['room_capacity'],
            htmlspecialchars(trim($data['room_status']), ENT_QUOTES, 'UTF-8'),
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
$totalRooms = count($rooms);
$availableRooms = count(array_filter($rooms, fn($r) => $r['Room_Status'] === 'Available'));
$unavailableRooms = count(array_filter($rooms, fn($r) => $r['Room_Status'] === 'Unavailable'));
$totalRevenue = array_sum(array_column($rooms, 'total_revenue'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Paradise Resort Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4a9960',
                        'primary-dark': '#3a7a4e',
                        secondary: '#66d9a3',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-inter">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 fixed w-full top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-umbrella-beach text-primary text-2xl mr-3"></i>
                        <h1 class="text-xl font-bold text-gray-900">Paradise Resort</h1>
                    </div>
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="manage_bookings.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-calendar-check mr-2"></i>Bookings
                        </a>
                        <a href="manage_rooms.php" class="text-primary border-b-2 border-primary px-3 py-2 text-sm font-medium">
                            <i class="fas fa-bed mr-2"></i>Rooms
                        </a>
                        <a href="manage_customers.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-users mr-2"></i>Customers
                        </a>
                        <a href="manage_amenities.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-star mr-2"></i>Amenities
                        </a>
                        <a href="manage_services.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-concierge-bell mr-2"></i>Services
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($adminInfo['Admin_Email'] ?? 'Administrator'); ?></span>
                        <a href="../logout.php" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Room Management</h1>
                        <p class="mt-2 text-sm text-gray-600">Manage resort rooms, rates, and availability</p>
                    </div>
                    <div class="mt-4 sm:mt-0 flex space-x-3">
                        <button onclick="openAddRoomModal()" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                            <i class="fas fa-plus mr-2"></i>Add New Room
                        </button>
                        <button onclick="location.reload()" class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <i class="fas fa-bed text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $totalRooms; ?></h3>
                            <p class="text-sm text-gray-600">Total Rooms</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-50 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $availableRooms; ?></h3>
                            <p class="text-sm text-gray-600">Available</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-50 rounded-lg">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $unavailableRooms; ?></h3>
                            <p class="text-sm text-gray-600">Unavailable</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-50 rounded-lg">
                            <i class="fas fa-peso-sign text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-bold text-gray-900">₱<?php echo number_format($totalRevenue, 0); ?></h3>
                            <p class="text-sm text-gray-600">Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rooms Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($rooms as $room): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <!-- Room Header -->
                    <div class="p-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="p-2 bg-primary bg-opacity-10 rounded-lg mr-3">
                                    <i class="fas fa-bed text-primary text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($room['Room_Type']); ?> Room</h3>
                                    <p class="text-sm text-gray-500">ID: #<?php echo $room['Room_ID']; ?></p>
                                </div>
                            </div>
                            <div class="relative">
                                <select class="status-select appearance-none bg-gray-50 border border-gray-300 rounded-lg px-3 py-1 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent cursor-pointer" 
                                        data-room-id="<?php echo $room['Room_ID']; ?>"
                                        onchange="updateRoomStatus(<?php echo $room['Room_ID']; ?>, this.value)">
                                    <option value="Available" <?php echo $room['Room_Status'] === 'Available' ? 'selected' : ''; ?> class="text-green-600">Available</option>
                                    <option value="Unavailable" <?php echo $room['Room_Status'] === 'Unavailable' ? 'selected' : ''; ?> class="text-red-600">Unavailable</option>
                                    <option value="Maintenance" <?php echo $room['Room_Status'] === 'Maintenance' ? 'selected' : ''; ?> class="text-yellow-600">Maintenance</option>
                                </select>
                            </div>
                        </div>

                        <!-- Room Details -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-600">Rate per Night</span>
                                <span class="text-sm font-semibold text-gray-900">₱<?php echo number_format($room['Room_Rate'], 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-600">Guest Capacity</span>
                                <span class="text-sm font-semibold text-gray-900"><?php echo $room['Room_Cap']; ?> guests</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-600">Total Bookings</span>
                                <span class="text-sm font-semibold text-gray-900"><?php echo $room['total_bookings'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm text-gray-600">Revenue Generated</span>
                                <span class="text-sm font-semibold text-green-600">₱<?php echo number_format($room['total_revenue'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex justify-center space-x-3">
                            <button onclick="editRoom(<?php echo $room['Room_ID']; ?>)" 
                                    class="flex-1 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                    title="Edit Room">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <button onclick="viewRoomBookings(<?php echo $room['Room_ID']; ?>)" 
                                    class="flex-1 bg-white hover:bg-green-50 text-green-600 border border-green-200 hover:border-green-300 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                    title="View Bookings">
                                <i class="fas fa-calendar-alt mr-1"></i>Bookings
                            </button>
                            <button onclick="deleteRoom(<?php echo $room['Room_ID']; ?>)" 
                                    class="flex-1 bg-white hover:bg-red-50 text-red-600 border border-red-200 hover:border-red-300 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                    title="Delete Room">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Add/Edit Room Modal -->
    <div id="roomModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 id="roomModalTitle" class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-bed mr-2 text-primary"></i>Add New Room
                </h2>
                <button onclick="closeModal('roomModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form id="roomForm" class="p-6">
                <input type="hidden" id="roomId" name="room_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="roomType" class="block text-sm font-medium text-gray-700 mb-2">Room Type *</label>
                        <input type="text" id="roomType" name="room_type" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                               placeholder="e.g., Deluxe, Standard, Suite">
                    </div>
                    
                    <div>
                        <label for="roomRate" class="block text-sm font-medium text-gray-700 mb-2">Rate per Night (₱) *</label>
                        <input type="number" id="roomRate" name="room_rate" required min="0" step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                               placeholder="0.00">
                    </div>
                    
                    <div>
                        <label for="roomCapacity" class="block text-sm font-medium text-gray-700 mb-2">Guest Capacity *</label>
                        <input type="number" id="roomCapacity" name="room_capacity" required min="1"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                               placeholder="Maximum number of guests">
                    </div>
                    
                    <div>
                        <label for="roomStatus" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select id="roomStatus" name="room_status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeModal('roomModal')" 
                            class="px-6 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-3 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors font-medium">
                        <i class="fas fa-save mr-2"></i><span id="submitBtnText">Save Room</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="fixed top-4 right-4 z-50 space-y-4"></div>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
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
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
            });
        });

        // Modal functions
        function openAddRoomModal() {
            document.getElementById('roomModalTitle').innerHTML = '<i class="fas fa-bed mr-2 text-primary"></i>Add New Room';
            document.getElementById('submitBtnText').textContent = 'Save Room';
            document.getElementById('roomForm').reset();
            document.getElementById('roomId').value = '';
            openModal('roomModal');
        }

        function editRoom(roomId) {
            // Find room data from the page
            const roomCard = document.querySelector(`[data-room-id="${roomId}"]`);
            if (!roomCard) return;
            
            const roomTypeElement = roomCard.querySelector('h3');
            const roomDetails = roomCard.querySelectorAll('.font-semibold');
            
            if (roomTypeElement && roomDetails.length >= 2) {
                const roomType = roomTypeElement.textContent.replace(' Room', '');
                const roomRate = roomDetails[0].textContent.replace('₱', '').replace(/,/g, '');
                const roomCapacity = roomDetails[1].textContent.replace(' guests', '');
                
                document.getElementById('roomModalTitle').innerHTML = '<i class="fas fa-edit mr-2 text-primary"></i>Edit Room';
                document.getElementById('submitBtnText').textContent = 'Update Room';
                document.getElementById('roomId').value = roomId;
                document.getElementById('roomType').value = roomType;
                document.getElementById('roomRate').value = roomRate;
                document.getElementById('roomCapacity').value = roomCapacity;
                
                const statusSelect = roomCard.querySelector('.status-select');
                if (statusSelect) {
                    document.getElementById('roomStatus').value = statusSelect.value;
                }
                
                openModal('roomModal');
            }
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
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
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
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            });
        }

        function viewRoomBookings(roomId) {
            window.location.href = `manage_bookings.php?room=${roomId}`;
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' :
                           type === 'error' ? 'bg-red-50 border-red-200 text-red-800' :
                           'bg-blue-50 border-blue-200 text-blue-800';
                           
            const iconClass = type === 'success' ? 'fa-check-circle text-green-500' :
                             type === 'error' ? 'fa-exclamation-circle text-red-500' :
                             'fa-info-circle text-blue-500';
            
            notification.className = `${bgColor} border rounded-lg p-4 shadow-lg max-w-md transform transition-all duration-300 translate-x-0`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${iconClass} mr-3"></i>
                    <span class="font-medium">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fixed') && e.target.classList.contains('inset-0')) {
                closeModal('roomModal');
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('roomModal');
            }
        });
    </script>
</body>
</html>
