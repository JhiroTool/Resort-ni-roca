<?php
/**
 * Amenities Management Page - Paradise Resort Management System
 * Full CRUD functionality for managing resort amenities
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
        case 'get_amenities':
            echo json_encode(getAllAmenities($pdo));
            exit;
        
        case 'add_amenity':
            $result = addAmenity($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'update_amenity':
            $result = updateAmenity($pdo, $_POST);
            echo json_encode(['success' => $result]);
            exit;
        
        case 'delete_amenity':
            $result = deleteAmenity($pdo, $_POST['amenity_id']);
            echo json_encode(['success' => $result]);
            exit;
    }
}

function getAllAmenities($pdo) {
    if (!$pdo) return ['success' => false, 'data' => []];
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                COUNT(ba.Booking_ID) as usage_count,
                COALESCE(SUM(a.Amenity_Cost), 0) as total_revenue
            FROM amenity a
            LEFT JOIN bookingamenity ba ON a.Amenity_ID = ba.Amenity_ID
            GROUP BY a.Amenity_ID
            ORDER BY a.Amenity_Name
        ");
        $stmt->execute();
        return ['success' => true, 'data' => $stmt->fetchAll()];
    } catch(PDOException $e) {
        error_log("Get amenities error: " . $e->getMessage());
        return ['success' => false, 'data' => []];
    }
}

function addAmenity($pdo, $data) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO amenity (Amenity_Name, Amenity_Desc, Amenity_Cost) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['cost']
        ]);
    } catch(PDOException $e) {
        error_log("Add amenity error: " . $e->getMessage());
        return false;
    }
}

function updateAmenity($pdo, $data) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE amenity 
            SET Amenity_Name = ?, Amenity_Desc = ?, Amenity_Cost = ?
            WHERE Amenity_ID = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['cost'],
            $data['amenity_id']
        ]);
    } catch(PDOException $e) {
        error_log("Update amenity error: " . $e->getMessage());
        return false;
    }
}

function deleteAmenity($pdo, $amenityId) {
    if (!$pdo) return false;
    
    try {
        // Check if amenity is being used in any bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookingamenity WHERE Amenity_ID = ?");
        $stmt->execute([$amenityId]);
        $usageCount = $stmt->fetchColumn();
        
        if ($usageCount > 0) {
            return false; // Cannot delete if in use
        }
        
        $stmt = $pdo->prepare("DELETE FROM amenity WHERE Amenity_ID = ?");
        return $stmt->execute([$amenityId]);
    } catch(PDOException $e) {
        error_log("Delete amenity error: " . $e->getMessage());
        return false;
    }
}

$amenitiesData = getAllAmenities($pdo);
$amenities = $amenitiesData['data'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amenities Management - Paradise Resort Admin</title>
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
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
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
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
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
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .add-btn {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            color: white;
        }

        .back-btn:hover, .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
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
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            color: white;
            padding: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .content-header::before {
            content: '✨🏖️🌺🏨⭐🎯🏊‍♀️🌴';
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

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            padding: 3rem;
        }

        .amenity-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-left: 5px solid;
        }

        .amenity-card:nth-child(odd) { border-left-color: #ff9a9e; }
        .amenity-card:nth-child(even) { border-left-color: #667eea; }

        .amenity-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(255, 154, 158, 0.1), rgba(250, 208, 196, 0.1));
            border-radius: 50%;
            transform: translate(40px, -40px);
        }

        .amenity-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
        }

        .amenity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .amenity-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .amenity-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .amenity-description {
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .amenity-cost {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ff9a9e;
            margin-bottom: 1rem;
        }

        .amenity-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 10px;
        }

        .stat-number {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 0.25rem;
        }

        .amenity-actions {
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
            margin: 8% auto;
            padding: 0;
            border-radius: 25px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .modal-body {
            padding: 2rem;
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
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff9a9e;
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
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
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
            
            .amenities-grid {
                grid-template-columns: 1fr;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>
                <i class="fas fa-star"></i>
                Amenities Management
            </h1>
            <div class="header-actions">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <button class="add-btn" onclick="openAmenityModal()">
                    <i class="fas fa-plus"></i>
                    Add Amenity
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h2>Resort Amenities & Services</h2>
                <p>Manage all resort amenities, features, and additional services</p>
            </div>

            <!-- Loading State -->
            <div id="loadingAmenities" class="loading">
                <i class="fas fa-spinner fa-spin"></i>
                <h3>Loading amenities...</h3>
                <p>Please wait while we fetch amenity data</p>
            </div>

            <!-- Amenities Grid -->
            <div id="amenitiesGrid" class="amenities-grid" style="display: none;">
                <!-- Amenity cards will be populated here -->
            </div>

            <!-- No Data State -->
            <div id="noAmenitiesData" class="no-data" style="display: none;">
                <i class="fas fa-star"></i>
                <h3>No Amenities Found</h3>
                <p>Start by adding your first resort amenity.</p>
            </div>
        </div>
    </div>

    <!-- Add/Edit Amenity Modal -->
    <div id="amenityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Amenity</h3>
                <p>Enhance your resort with amazing amenities</p>
            </div>
            <div class="modal-body">
                <form id="amenityForm">
                    <input type="hidden" id="amenityId">
                    
                    <div class="form-group">
                        <label for="amenityName">Amenity Name *</label>
                        <input type="text" id="amenityName" required placeholder="e.g., Swimming Pool, Wi-Fi, Breakfast">
                    </div>
                    
                    <div class="form-group">
                        <label for="amenityDescription">Description *</label>
                        <textarea id="amenityDescription" required placeholder="Detailed description of the amenity..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="amenityCost">Cost (₱) *</label>
                        <input type="number" id="amenityCost" required min="0" step="0.01" placeholder="0.00">
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAmenityModal()">Cancel</button>
                <button type="submit" form="amenityForm" class="btn btn-primary">Save Amenity</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            loadAmenities();
        });

        function loadAmenities() {
            $.post('manage_amenities.php', {action: 'get_amenities'}, function(response) {
                if (response.success && response.data.length > 0) {
                    displayAmenities(response.data);
                    $('#loadingAmenities').hide();
                    $('#amenitiesGrid').show();
                } else {
                    $('#loadingAmenities').hide();
                    $('#noAmenitiesData').show();
                }
            }).fail(function() {
                $('#loadingAmenities').hide();
                $('#noAmenitiesData').show();
            });
        }

        function displayAmenities(amenities) {
            const grid = $('#amenitiesGrid');
            grid.empty();

            amenities.forEach((amenity, index) => {
                const iconClass = getAmenityIcon(amenity.Amenity_Name);
                
                const card = `
                    <div class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas ${iconClass}"></i>
                        </div>
                        <h3 class="amenity-name">${amenity.Amenity_Name}</h3>
                        <p class="amenity-description">${amenity.Amenity_Desc}</p>
                        <div class="amenity-cost">₱${parseFloat(amenity.Amenity_Cost).toLocaleString()}</div>
                        <div class="amenity-stats">
                            <div class="stat-item">
                                <div class="stat-number">${amenity.usage_count}</div>
                                <div class="stat-label">Bookings</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">₱${parseFloat(amenity.total_revenue).toLocaleString()}</div>
                                <div class="stat-label">Revenue</div>
                            </div>
                        </div>
                        <div class="amenity-actions">
                            <button class="action-btn edit-btn" onclick="editAmenity(${amenity.Amenity_ID}, '${amenity.Amenity_Name}', '${amenity.Amenity_Desc}', ${amenity.Amenity_Cost})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteAmenity(${amenity.Amenity_ID}, ${amenity.usage_count})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                `;
                
                grid.append(card);
            });
        }

        function getAmenityIcon(name) {
            const icons = {
                'wifi': 'fa-wifi',
                'pool': 'fa-swimming-pool',
                'breakfast': 'fa-coffee',
                'spa': 'fa-spa',
                'gym': 'fa-dumbbell',
                'parking': 'fa-car',
                'restaurant': 'fa-utensils',
                'bar': 'fa-cocktail',
                'beach': 'fa-umbrella-beach',
                'tv': 'fa-tv',
                'ac': 'fa-snowflake',
                'atv': 'fa-motorcycle'
            };
            
            const lowerName = name.toLowerCase();
            for (const [key, icon] of Object.entries(icons)) {
                if (lowerName.includes(key)) {
                    return icon;
                }
            }
            return 'fa-star'; // Default icon
        }

        function openAmenityModal(isEdit = false) {
            $('#modalTitle').text(isEdit ? 'Edit Amenity' : 'Add New Amenity');
            if (!isEdit) {
                $('#amenityForm')[0].reset();
                $('#amenityId').val('');
            }
            $('#amenityModal').show();
        }

        function closeAmenityModal() {
            $('#amenityModal').hide();
        }

        function editAmenity(id, name, description, cost) {
            $('#amenityId').val(id);
            $('#amenityName').val(name);
            $('#amenityDescription').val(description);
            $('#amenityCost').val(cost);
            openAmenityModal(true);
        }

        $('#amenityForm').submit(function(e) {
            e.preventDefault();
            
            const amenityId = $('#amenityId').val();
            const isEdit = amenityId !== '';
            
            const formData = {
                action: isEdit ? 'update_amenity' : 'add_amenity',
                name: $('#amenityName').val(),
                description: $('#amenityDescription').val(),
                cost: $('#amenityCost').val()
            };
            
            if (isEdit) {
                formData.amenity_id = amenityId;
            }
            
            $.post('manage_amenities.php', formData, function(response) {
                if (response.success) {
                    closeAmenityModal();
                    loadAmenities(); // Reload amenities
                    showNotification(isEdit ? 'Amenity updated successfully!' : 'Amenity added successfully!', 'success');
                } else {
                    showNotification('Failed to save amenity. Please try again.', 'error');
                }
            }).fail(function() {
                showNotification('Error occurred. Please try again.', 'error');
            });
        });

        function deleteAmenity(amenityId, usageCount) {
            if (usageCount > 0) {
                showNotification('Cannot delete amenity that is currently being used in bookings.', 'error');
                return;
            }
            
            if (confirm('Are you sure you want to delete this amenity? This action cannot be undone.')) {
                $.post('manage_amenities.php', {
                    action: 'delete_amenity',
                    amenity_id: amenityId
                }, function(response) {
                    if (response.success) {
                        loadAmenities(); // Reload amenities
                        showNotification('Amenity deleted successfully!', 'success');
                    } else {
                        showNotification('Failed to delete amenity.', 'error');
                    }
                }).fail(function() {
                    showNotification('Error occurred while deleting amenity.', 'error');
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
            const modal = document.getElementById('amenityModal');
            if (event.target === modal) {
                closeAmenityModal();
            }
        }
    </script>
</body>
</html>
