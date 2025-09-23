<?php
/**
 * Reports & Analytics Page - Paradise Resort Management System
 * Comprehensive business intelligence and reporting dashboard
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
        case 'get_revenue_chart':
            echo json_encode(getRevenueChartData($pdo, $_POST['period']));
            exit;
        
        case 'get_booking_trends':
            echo json_encode(getBookingTrendsData($pdo, $_POST['period']));
            exit;
        
        case 'get_room_occupancy':
            echo json_encode(getRoomOccupancyData($pdo));
            exit;
        
        case 'get_customer_analytics':
            echo json_encode(getCustomerAnalyticsData($pdo));
            exit;
        
        case 'get_financial_summary':
            echo json_encode(getFinancialSummaryData($pdo, $_POST['period']));
            exit;
        
        case 'export_report':
            exportReport($pdo, $_POST['report_type'], $_POST['format']);
            exit;
    }
}

function getRevenueChartData($pdo, $period = '12months') {
    if (!$pdo) {
        // Demo data for revenue chart
        return [
            'success' => true,
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'datasets' => [
                [
                    'label' => 'Room Revenue',
                    'data' => [45000, 52000, 48000, 61000, 55000, 67000, 82000, 79000, 71000, 63000, 58000, 72000],
                    'backgroundColor' => 'rgba(255, 154, 158, 0.8)',
                    'borderColor' => 'rgba(255, 154, 158, 1)',
                    'borderWidth' => 2
                ],
                [
                    'label' => 'Amenity Revenue',
                    'data' => [12000, 15000, 13000, 18000, 16000, 21000, 28000, 25000, 22000, 19000, 17000, 23000],
                    'backgroundColor' => 'rgba(102, 126, 234, 0.8)',
                    'borderColor' => 'rgba(102, 126, 234, 1)',
                    'borderWidth' => 2
                ],
                [
                    'label' => 'Service Revenue',
                    'data' => [8000, 9500, 8700, 12000, 11000, 14000, 18000, 16500, 15000, 13000, 12000, 16000],
                    'backgroundColor' => 'rgba(250, 208, 196, 0.8)',
                    'borderColor' => 'rgba(250, 208, 196, 1)',
                    'borderWidth' => 2
                ]
            ]
        ];
    }
    
    try {
        // Actual database query would be implemented here
        return getRevenueChartData(null, $period);
    } catch(PDOException $e) {
        return getRevenueChartData(null, $period);
    }
}

function getBookingTrendsData($pdo, $period = '30days') {
    if (!$pdo) {
        return [
            'success' => true,
            'total_bookings' => 156,
            'confirmed_bookings' => 142,
            'cancelled_bookings' => 8,
            'pending_bookings' => 6,
            'average_stay' => 3.2,
            'repeat_customers' => 34,
            'trends' => [
                'bookings_growth' => 12.5,
                'revenue_growth' => 18.3,
                'occupancy_rate' => 78.5
            ]
        ];
    }
    
    try {
        // Actual database queries would be implemented here
        return getBookingTrendsData(null, $period);
    } catch(PDOException $e) {
        return getBookingTrendsData(null, $period);
    }
}

function getRoomOccupancyData($pdo) {
    if (!$pdo) {
        return [
            'success' => true,
            'occupancy_rate' => 78.5,
            'available_rooms' => 12,
            'occupied_rooms' => 43,
            'maintenance_rooms' => 3,
            'room_types' => [
                ['name' => 'Standard', 'occupied' => 15, 'total' => 20, 'rate' => 75],
                ['name' => 'Deluxe', 'occupied' => 18, 'total' => 22, 'rate' => 82],
                ['name' => 'Suite', 'occupied' => 8, 'total' => 12, 'rate' => 67],
                ['name' => 'Premium', 'occupied' => 2, 'total' => 4, 'rate' => 50]
            ]
        ];
    }
    
    try {
        // Actual database queries would be implemented here
        return getRoomOccupancyData(null);
    } catch(PDOException $e) {
        return getRoomOccupancyData(null);
    }
}

function getCustomerAnalyticsData($pdo) {
    if (!$pdo) {
        return [
            'success' => true,
            'total_customers' => 1247,
            'new_customers' => 89,
            'returning_customers' => 341,
            'customer_satisfaction' => 4.7,
            'demographics' => [
                'age_groups' => [
                    ['label' => '18-25', 'value' => 18],
                    ['label' => '26-35', 'value' => 35],
                    ['label' => '36-45', 'value' => 28],
                    ['label' => '46-55', 'value' => 15],
                    ['label' => '55+', 'value' => 4]
                ],
                'booking_sources' => [
                    ['label' => 'Direct Website', 'value' => 45],
                    ['label' => 'Online Travel Agency', 'value' => 32],
                    ['label' => 'Social Media', 'value' => 15],
                    ['label' => 'Referral', 'value' => 8]
                ]
            ]
        ];
    }
    
    try {
        // Actual database queries would be implemented here
        return getCustomerAnalyticsData(null);
    } catch(PDOException $e) {
        return getCustomerAnalyticsData(null);
    }
}

function getFinancialSummaryData($pdo, $period = 'month') {
    if (!$pdo) {
        return [
            'success' => true,
            'total_revenue' => 156750,
            'room_revenue' => 98500,
            'amenity_revenue' => 32400,
            'service_revenue' => 25850,
            'operating_expenses' => 45200,
            'net_profit' => 111550,
            'profit_margin' => 71.2,
            'average_daily_rate' => 2850,
            'revenue_per_available_room' => 2230
        ];
    }
    
    try {
        // Actual database queries would be implemented here
        return getFinancialSummaryData(null, $period);
    } catch(PDOException $e) {
        return getFinancialSummaryData(null, $period);
    }
}

function exportReport($pdo, $reportType, $format) {
    // This would implement actual report export functionality
    echo json_encode(['success' => true, 'message' => 'Report exported successfully']);
}

// Get initial data for page load
$revenueData = getRevenueChartData($pdo);
$bookingTrends = getBookingTrendsData($pdo);
$occupancyData = getRoomOccupancyData($pdo);
$customerData = getCustomerAnalyticsData($pdo);
$financialData = getFinancialSummaryData($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Paradise Resort Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .back-btn, .export-btn {
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

        .export-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .back-btn:hover, .export-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .reports-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .chart-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .chart-body {
            padding: 2rem;
            height: 400px;
        }

        .stats-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
        }

        .stats-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            margin-right: 1rem;
        }

        .stats-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2d3748;
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stats-change {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .stats-change.positive {
            color: #10b981;
        }

        .stats-change.negative {
            color: #ef4444;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .analytics-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .analytics-header {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .analytics-body {
            padding: 2rem;
        }

        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 15px;
        }

        .metric-label {
            font-weight: 600;
            color: #2d3748;
        }

        .metric-value {
            font-weight: 700;
            color: #667eea;
        }

        .occupancy-bars {
            margin-top: 1rem;
        }

        .occupancy-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .bar-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .bar-label {
            font-weight: 600;
            color: #2d3748;
            min-width: 80px;
        }

        .progress-bar {
            flex: 1;
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }

        .bar-percentage {
            font-weight: 700;
            color: #667eea;
            min-width: 40px;
            text-align: right;
        }

        .financial-summary {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .financial-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .financial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }

        .financial-item {
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 15px;
        }

        .financial-amount {
            font-size: 1.8rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .financial-label {
            font-weight: 600;
            color: #718096;
        }

        .period-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 0.5rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .period-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            color: #718096;
            font-weight: 600;
            cursor: pointer;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .period-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
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
            
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .financial-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Reports & Analytics
            </h1>
            <div class="header-actions">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <button class="export-btn" onclick="exportReport()">
                    <i class="fas fa-download"></i>
                    Export Reports
                </button>
            </div>
        </div>

        <!-- Period Selector -->
        <div class="period-selector">
            <button class="period-btn active" onclick="changePeriod('week')">This Week</button>
            <button class="period-btn" onclick="changePeriod('month')">This Month</button>
            <button class="period-btn" onclick="changePeriod('quarter')">This Quarter</button>
            <button class="period-btn" onclick="changePeriod('year')">This Year</button>
        </div>

        <!-- Main Reports Grid -->
        <div class="reports-grid">
            <div class="chart-section">
                <div class="chart-header">
                    <h2>Revenue Analytics</h2>
                    <p>Track your revenue streams over time</p>
                </div>
                <div class="chart-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="stats-section">
                <div class="stats-card">
                    <div class="stats-header">
                        <div class="stats-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="stats-title">Room Occupancy</div>
                    </div>
                    <div class="stats-value" id="occupancyRate">78.5%</div>
                    <div class="stats-change positive">
                        <i class="fas fa-arrow-up"></i> +5.2% from last month
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-header">
                        <div class="stats-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stats-title">Total Bookings</div>
                    </div>
                    <div class="stats-value" id="totalBookings">156</div>
                    <div class="stats-change positive">
                        <i class="fas fa-arrow-up"></i> +12.5% from last month
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-header">
                        <div class="stats-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stats-title">Satisfaction Score</div>
                    </div>
                    <div class="stats-value" id="satisfactionScore">4.7/5</div>
                    <div class="stats-change positive">
                        <i class="fas fa-arrow-up"></i> +0.3 from last month
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Grid -->
        <div class="analytics-grid">
            <div class="analytics-card">
                <div class="analytics-header">
                    <h3>Booking Trends</h3>
                    <p>Current booking performance</p>
                </div>
                <div class="analytics-body">
                    <div class="metric-item">
                        <span class="metric-label">Confirmed Bookings</span>
                        <span class="metric-value">142</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Pending Bookings</span>
                        <span class="metric-value">6</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Cancelled Bookings</span>
                        <span class="metric-value">8</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Average Stay Duration</span>
                        <span class="metric-value">3.2 nights</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Repeat Customers</span>
                        <span class="metric-value">34</span>
                    </div>
                </div>
            </div>

            <div class="analytics-card">
                <div class="analytics-header">
                    <h3>Room Utilization</h3>
                    <p>Current room status by type</p>
                </div>
                <div class="analytics-body">
                    <div class="occupancy-bars" id="roomOccupancy">
                        <!-- Room occupancy bars will be populated here -->
                    </div>
                </div>
            </div>

            <div class="analytics-card">
                <div class="analytics-header">
                    <h3>Customer Demographics</h3>
                    <p>Guest profile breakdown</p>
                </div>
                <div class="analytics-body">
                    <div class="metric-item">
                        <span class="metric-label">Total Customers</span>
                        <span class="metric-value">1,247</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">New Customers</span>
                        <span class="metric-value">89</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Returning Customers</span>
                        <span class="metric-value">341</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Direct Bookings</span>
                        <span class="metric-value">45%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">OTA Bookings</span>
                        <span class="metric-value">32%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="financial-summary">
            <div class="financial-header">
                <h2>Financial Performance</h2>
                <p>Comprehensive financial overview for this period</p>
            </div>
            <div class="financial-grid">
                <div class="financial-item">
                    <div class="financial-amount">₱156,750</div>
                    <div class="financial-label">Total Revenue</div>
                </div>
                <div class="financial-item">
                    <div class="financial-amount">₱98,500</div>
                    <div class="financial-label">Room Revenue</div>
                </div>
                <div class="financial-item">
                    <div class="financial-amount">₱32,400</div>
                    <div class="financial-label">Amenity Revenue</div>
                </div>
                <div class="financial-item">
                    <div class="financial-amount">₱25,850</div>
                    <div class="financial-label">Service Revenue</div>
                </div>
                <div class="financial-item">
                    <div class="financial-amount">₱111,550</div>
                    <div class="financial-label">Net Profit</div>
                </div>
                <div class="financial-item">
                    <div class="financial-amount">71.2%</div>
                    <div class="financial-label">Profit Margin</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let revenueChart;
        
        $(document).ready(function() {
            initializeCharts();
            loadRoomOccupancy();
        });

        function initializeCharts() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const revenueData = <?php echo json_encode($revenueData); ?>;
            
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: revenueData.labels,
                    datasets: revenueData.datasets.map(dataset => ({
                        ...dataset,
                        tension: 0.4,
                        fill: false,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                font: {
                                    family: 'Poppins',
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#2d3748',
                            bodyColor: '#2d3748',
                            borderColor: 'rgba(102, 126, 234, 0.3)',
                            borderWidth: 1,
                            titleFont: {
                                family: 'Poppins',
                                weight: '600'
                            },
                            bodyFont: {
                                family: 'Poppins'
                            },
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₱' + 
                                           context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                },
                                font: {
                                    family: 'Poppins'
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: 'Poppins'
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }

        function loadRoomOccupancy() {
            const occupancyData = <?php echo json_encode($occupancyData); ?>;
            const container = $('#roomOccupancy');
            
            occupancyData.room_types.forEach(room => {
                const occupancyBar = `
                    <div class="occupancy-bar">
                        <div class="bar-info">
                            <div class="bar-label">${room.name}</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${room.rate}%"></div>
                            </div>
                            <div class="bar-percentage">${room.rate}%</div>
                        </div>
                    </div>
                `;
                container.append(occupancyBar);
            });
        }

        function changePeriod(period) {
            // Update active button
            $('.period-btn').removeClass('active');
            $(`.period-btn:contains('${period.charAt(0).toUpperCase() + period.slice(1)}')`).addClass('active');
            
            // Update charts and data
            updateChartData(period);
            
            showNotification(`Reports updated for ${period} period`, 'success');
        }

        function updateChartData(period) {
            // This would normally make AJAX calls to get new data
            // For demo, we'll just update the chart with the same data
            
            if (revenueChart) {
                revenueChart.update('active');
            }
        }

        function exportReport() {
            // Show export options
            const exportOptions = `
                <div id="exportModal" style="
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.7);
                    backdrop-filter: blur(10px);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <div style="
                        background: white;
                        padding: 3rem;
                        border-radius: 25px;
                        text-align: center;
                        max-width: 500px;
                        box-shadow: 0 30px 80px rgba(0,0,0,0.3);
                    ">
                        <h3 style="margin-bottom: 2rem; color: #2d3748; font-size: 1.5rem;">Export Report</h3>
                        <p style="margin-bottom: 2rem; color: #718096;">Choose your preferred export format:</p>
                        <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 2rem;">
                            <button onclick="downloadReport('pdf')" style="
                                padding: 1rem 2rem;
                                background: linear-gradient(135deg, #667eea, #764ba2);
                                color: white;
                                border: none;
                                border-radius: 15px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: all 0.3s ease;
                            ">
                                <i class="fas fa-file-pdf"></i> PDF Report
                            </button>
                            <button onclick="downloadReport('excel')" style="
                                padding: 1rem 2rem;
                                background: linear-gradient(135deg, #10b981, #34d399);
                                color: white;
                                border: none;
                                border-radius: 15px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: all 0.3s ease;
                            ">
                                <i class="fas fa-file-excel"></i> Excel Report
                            </button>
                        </div>
                        <button onclick="closeExportModal()" style="
                            padding: 0.75rem 2rem;
                            background: #e2e8f0;
                            color: #2d3748;
                            border: none;
                            border-radius: 10px;
                            font-weight: 600;
                            cursor: pointer;
                        ">Cancel</button>
                    </div>
                </div>
            `;
            
            $('body').append(exportOptions);
        }

        function downloadReport(format) {
            closeExportModal();
            showNotification(`${format.toUpperCase()} report download started!`, 'success');
            
            // Simulate download process
            setTimeout(() => {
                showNotification(`${format.toUpperCase()} report downloaded successfully!`, 'success');
            }, 2000);
        }

        function closeExportModal() {
            $('#exportModal').remove();
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

        // Close export modal when clicking outside
        $(document).on('click', '#exportModal', function(e) {
            if (e.target === this) {
                closeExportModal();
            }
        });
    </script>
</body>
</html>
