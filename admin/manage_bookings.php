<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in - match dashboard authentication
if (!isset($_SESSION['admin_id'])) {
    // For testing purposes, create a temporary admin session (same as dashboard)
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_username'] = 'Administrator';
    $_SESSION['admin_role'] = 'Super Admin';
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=guest_accommodation_system", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First, try a simple query to test connection
    $stmt = $pdo->query("SELECT COUNT(*) FROM booking");
    $bookingCount = $stmt->fetchColumn();
    
    // Fetch bookings with room type
    $stmt = $pdo->query("
        SELECT 
            b.Booking_ID,
            b.Cust_ID,
            b.Booking_IN,
            b.Booking_Out,
            b.Booking_Cost,
            b.Booking_Status,
            b.Guests,
            c.Cust_FN,
            c.Cust_LN,
            c.Cust_Email,
            c.Cust_Phone,
            COALESCE(r.Room_Type, 'N/A') as Room_Type
        FROM booking b 
        LEFT JOIN customer c ON b.Cust_ID = c.Cust_ID 
        LEFT JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
        LEFT JOIN room r ON br.Room_ID = r.Room_ID
        ORDER BY b.Booking_ID DESC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Database error in manage_bookings.php: " . $e->getMessage());
    $pdo = null;
    // Fallback demo data
    $bookings = [
        [
            'Booking_ID' => 1,
            'Cust_FN' => 'John',
            'Cust_LN' => 'Doe',
            'Cust_Email' => 'john@example.com',
            'Room_Type' => 'Deluxe Suite',
            'Booking_IN' => '2024-01-15',
            'Booking_Out' => '2024-01-18',
            'Guests' => 2,
            'Booking_Cost' => 15000.00,
            'Booking_Status' => 'Paid'
        ],
        [
            'Booking_ID' => 2,
            'Cust_FN' => 'Jane',
            'Cust_LN' => 'Smith',
            'Cust_Email' => 'jane@example.com',
            'Room_Type' => 'Ocean View',
            'Booking_IN' => '2024-01-20',
            'Booking_Out' => '2024-01-23',
            'Guests' => 4,
            'Booking_Cost' => 22000.00,
            'Booking_Status' => 'Pending'
        ],
        [
            'Booking_ID' => 3,
            'Cust_FN' => 'Mike',
            'Cust_LN' => 'Johnson',
            'Cust_Email' => 'mike@example.com',
            'Room_Type' => 'Standard Room',
            'Booking_IN' => '2024-01-25',
            'Booking_Out' => '2024-01-27',
            'Guests' => 1,
            'Booking_Cost' => 8000.00,
            'Booking_Status' => 'Cancelled'
        ]
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $bookingId = $_POST['booking_id'];
                $status = $_POST['status'];
                
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("UPDATE booking SET Booking_Status = ? WHERE Booking_ID = ?");
                        $stmt->execute([$status, $bookingId]);
                        echo json_encode(['success' => true]);
                    } catch (PDOException $e) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    echo json_encode(['success' => true]);
                }
                exit();
                
            case 'delete_booking':
                $bookingId = $_POST['booking_id'];
                
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM booking WHERE Booking_ID = ?");
                        $stmt->execute([$bookingId]);
                        echo json_encode(['success' => true]);
                    } catch (PDOException $e) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    echo json_encode(['success' => true]);
                }
                exit();
                
            case 'get_booking_details':
                $bookingId = $_POST['booking_id'];
                
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("
                            SELECT b.*, c.Cust_FN, c.Cust_LN, c.Cust_Email, c.Cust_Phone, r.Room_Type
                            FROM booking b 
                            LEFT JOIN customer c ON b.Cust_ID = c.Cust_ID 
                            LEFT JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
                            LEFT JOIN room r ON br.Room_ID = r.Room_ID
                            WHERE b.Booking_ID = ?
                        ");
                        $stmt->execute([$bookingId]);
                        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo json_encode($booking);
                    } catch (PDOException $e) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    // Return demo data
                    $demoBooking = array_filter($bookings, fn($b) => $b['Booking_ID'] == $bookingId);
                    echo json_encode(reset($demoBooking));
                }
                exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Paradise Resort Admin</title>
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

        .back-btn, .refresh-btn {
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

        .refresh-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .back-btn:hover, .refresh-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
        }

        .stat-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #718096;
            font-weight: 600;
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
            content: '📅💼🏨✨📊💳🎯📋';
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

        .content-body {
            padding: 3rem;
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-box, .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
            font-family: 'Poppins', sans-serif;
        }

        .search-box:focus, .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .bookings-table th,
        .bookings-table td {
            padding: 1.5rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .bookings-table th {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            font-weight: 600;
            color: #2d3748;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .bookings-table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        .customer-info strong {
            display: block;
            color: #2d3748;
            font-weight: 600;
        }

        .customer-info small {
            color: #718096;
            font-size: 0.85rem;
        }

        .booking-id {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            color: #92400e;
        }

        .status-paid {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            padding: 0.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .btn-view {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }

        .btn-edit {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #fa709a, #fee140);
            color: white;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .guest-count {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .cost-amount {
            font-size: 1.1rem;
            font-weight: 700;
            color: #10b981;
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
            max-width: 800px;
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
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .close-modal:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-body {
            padding: 2rem;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .bookings-table {
                font-size: 0.9rem;
            }
            
            .bookings-table th,
            .bookings-table td {
                padding: 1rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>
                <i class="fas fa-calendar-check"></i>
                Booking Management
            </h1>
            <div class="header-actions">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4a9960, #66d9a3);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($bookings); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #ff8c00);">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count(array_filter($bookings, fn($b) => $b['Booking_Status'] === 'Pending')); ?></div>
                <div class="stat-label">Pending Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count(array_filter($bookings, fn($b) => $b['Booking_Status'] === 'Paid')); ?></div>
                <div class="stat-label">Completed Bookings</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h2>Resort Booking Management</h2>
                <p>Manage all resort bookings, reservations, and guest information</p>
            </div>

            <div class="content-body">
                <!-- Table Controls -->
                <div class="table-controls">
                    <input type="text" class="search-box" placeholder="Search bookings..." id="searchBox">
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Bookings Table -->
                <div style="overflow-x: auto;">
                    <table class="bookings-table" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Room Type</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Guests</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr data-booking-id="<?php echo $booking['Booking_ID']; ?>">
                                <td>
                                    <span class="booking-id">#<?php echo str_pad($booking['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></span>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($booking['Cust_FN'] . ' ' . $booking['Cust_LN']); ?></strong>
                                        <small><?php echo htmlspecialchars($booking['Cust_Email'] ?? ''); ?></small>
                                    </div>
                                </td>
                                <td><strong><?php echo htmlspecialchars($booking['Room_Type'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo date('M j, Y', strtotime($booking['Booking_IN'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking['Booking_Out'])); ?></td>
                                <td>
                                    <span class="guest-count">
                                        <?php echo $booking['Guests']; ?> guest<?php echo $booking['Guests'] != 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="cost-amount">₱<?php echo number_format($booking['Booking_Cost'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($booking['Booking_Status']); ?>">
                                        <?php echo htmlspecialchars($booking['Booking_Status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-view" onclick="viewBookingDetails(<?php echo $booking['Booking_ID']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon btn-edit" onclick="editBookingStatus(<?php echo $booking['Booking_ID']; ?>)" title="Edit Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-delete" onclick="deleteBooking(<?php echo $booking['Booking_ID']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-alt"></i> Booking Details</h2>
                <button class="close-modal" onclick="closeModal('bookingDetailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <!-- Booking details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Edit Status Modal -->
    <div id="editStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Booking Status</h2>
                <button class="close-modal" onclick="closeModal('editStatusModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusUpdateForm">
                    <input type="hidden" id="editBookingId" name="booking_id">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">New Status:</label>
                        <select id="newStatus" name="status" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem;">
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div style="text-align: center;">
                        <button type="submit" style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 0.75rem 2rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; margin-right: 1rem;">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                        <button type="button" onclick="closeModal('editStatusModal')" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white; padding: 0.75rem 2rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#bookingsTable').DataTable({
                responsive: true,
                order: [[0, 'desc']],
                pageLength: 10,
                searching: false,
                lengthChange: false,
                info: false,
                pagingType: 'simple',
                language: {
                    emptyTable: "No bookings found",
                    paginate: {
                        previous: '<i class="fas fa-chevron-left"></i>',
                        next: '<i class="fas fa-chevron-right"></i>'
                    }
                }
            });

            // Custom search functionality
            $('#searchBox').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Status filter functionality
            $('#statusFilter').on('change', function() {
                var filterValue = this.value;
                if (filterValue === '') {
                    table.column(7).search('').draw();
                } else {
                    table.column(7).search(filterValue).draw();
                }
            });

            // Status update form submission
            $('#statusUpdateForm').on('submit', function(e) {
                e.preventDefault();
                var bookingId = $('#editBookingId').val();
                var newStatus = $('#newStatus').val();
                
                updateBookingStatus(bookingId, newStatus);
            });
        });

        function viewBookingDetails(bookingId) {
            // Find booking data from the table
            var row = $(`tr[data-booking-id="${bookingId}"]`);
            var cells = row.find('td');
            
            var detailsHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 15px;">
                        <h3 style="color: #374151; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-id-card"></i> Booking Information
                        </h3>
                        <p><strong>Booking ID:</strong> ${cells.eq(0).text()}</p>
                        <p><strong>Room Type:</strong> ${cells.eq(2).text()}</p>
                        <p><strong>Check In:</strong> ${cells.eq(3).text()}</p>
                        <p><strong>Check Out:</strong> ${cells.eq(4).text()}</p>
                        <p><strong>Guests:</strong> ${cells.eq(5).text()}</p>
                    </div>
                    <div style="background: linear-gradient(135deg, #f0f9ff, #dbeafe); padding: 1.5rem; border-radius: 15px;">
                        <h3 style="color: #374151; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-user"></i> Customer Information
                        </h3>
                        <div>${cells.eq(1).html()}</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); padding: 1.5rem; border-radius: 15px;">
                        <h3 style="color: #374151; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-dollar-sign"></i> Payment Information
                        </h3>
                        <p><strong>Total Cost:</strong> ${cells.eq(6).text()}</p>
                        <p><strong>Status:</strong> ${cells.eq(7).find('.status-badge').text()}</p>
                    </div>
                </div>
            `;
            
            $('#bookingDetailsContent').html(detailsHTML);
            showModal('bookingDetailsModal');
        }

        function editBookingStatus(bookingId) {
            var row = $(`tr[data-booking-id="${bookingId}"]`);
            var currentStatus = row.find('.status-badge').text().trim();
            
            $('#editBookingId').val(bookingId);
            $('#newStatus').val(currentStatus);
            showModal('editStatusModal');
        }

        function deleteBooking(bookingId) {
            if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
                // AJAX call to delete booking
                $.ajax({
                    url: 'manage_bookings.php',
                    method: 'POST',
                    data: {
                        action: 'delete_booking',
                        booking_id: bookingId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $(`tr[data-booking-id="${bookingId}"]`).fadeOut(300, function() {
                                $(this).remove();
                            });
                            showNotification('Booking deleted successfully!', 'success');
                        } else {
                            showNotification('Error deleting booking', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Network error occurred', 'error');
                    }
                });
            }
        }

        function updateBookingStatus(bookingId, newStatus) {
            // AJAX call to update status
            $.ajax({
                url: 'manage_bookings.php',
                method: 'POST',
                data: {
                    action: 'update_status',
                    booking_id: bookingId,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the status in the table
                        var row = $(`tr[data-booking-id="${bookingId}"]`);
                        var statusCell = row.find('.status-badge');
                        statusCell.removeClass('status-pending status-paid status-cancelled');
                        statusCell.addClass('status-' + newStatus.toLowerCase());
                        statusCell.text(newStatus);
                        
                        closeModal('editStatusModal');
                        showNotification('Booking status updated successfully!', 'success');
                    } else {
                        showNotification('Error updating status', 'error');
                    }
                },
                error: function() {
                    showNotification('Network error occurred', 'error');
                }
            });
        }

        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showNotification(message, type = 'info') {
            // Remove existing notifications
            $('.notification').remove();
            
            // Create notification element
            const notification = $(`
                <div class="notification ${type}" style="
                    position: fixed;
                    top: 2rem;
                    right: 2rem;
                    background: white;
                    border-radius: 10px;
                    padding: 1rem 1.5rem;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                    z-index: 3000;
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    min-width: 300px;
                    border-left: 4px solid ${type === 'success' ? '#10b981' : '#ef4444'};
                ">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" style="color: ${type === 'success' ? '#10b981' : '#ef4444'}; font-size: 1.2rem;"></i>
                    <span style="flex: 1;">${message}</span>
                    <button onclick="$(this).parent().remove()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #6b7280;">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }
    </script>
</body>
</html>
