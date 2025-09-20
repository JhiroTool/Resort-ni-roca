<?php
/**
 * Payment Page
 * Paradise Resort - Guest Accommodation System
 */

require_once '../config/database.php';

// Start secure session
startSecureSession();

// Require login
requireLogin();

// Get customer information
$customer = DatabaseUtilities::getCustomerById($_SESSION['cust_id']);

if (!$customer) {
    logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}

// Get booking ID from URL parameter
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header('Location: bookings.php?error=no_booking_id');
    exit();
}

$bookingId = (int)$_GET['booking_id'];
$customerId = $_SESSION['cust_id'];

// Get booking details with room information
$bookingQuery = "SELECT b.*, r.Room_Type, r.Room_Rate, r.Room_Cap,
                        GROUP_CONCAT(DISTINCT a.Amenity_Name SEPARATOR ', ') as amenities,
                        GROUP_CONCAT(DISTINCT s.Service_Name SEPARATOR ', ') as services,
                        GROUP_CONCAT(DISTINCT a.Amenity_Cost) as amenity_costs,
                        GROUP_CONCAT(DISTINCT s.Service_Cost) as service_costs,
                        p.Payment_ID, p.Payment_Amount as paid_amount
                 FROM booking b
                 JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
                 JOIN room r ON br.Room_ID = r.Room_ID
                 LEFT JOIN bookingamenity ba ON b.Booking_ID = ba.Booking_ID
                 LEFT JOIN amenity a ON ba.Amenity_ID = a.Amenity_ID
                 LEFT JOIN bookingservice bs ON b.Booking_ID = bs.Booking_ID
                 LEFT JOIN service s ON bs.Service_ID = s.Service_ID
                 LEFT JOIN payment p ON b.Booking_ID = p.Booking_ID
                 WHERE b.Booking_ID = ? AND b.Cust_ID = ?
                 GROUP BY b.Booking_ID";

$bookingResult = executeQuery($bookingQuery, [$bookingId, $customerId], 'ii');

if (empty($bookingResult)) {
    header('Location: bookings.php?error=booking_not_found');
    exit();
}

$booking = $bookingResult[0];

// Check if booking is payable
if ($booking['Booking_Status'] !== 'Pending') {
    header('Location: bookings.php?error=booking_not_payable');
    exit();
}

// Check if already paid
if ($booking['paid_amount'] && $booking['paid_amount'] >= $booking['Booking_Cost']) {
    header('Location: bookings.php?error=already_paid');
    exit();
}

// Calculate dates and nights
$checkinDate = new DateTime($booking['Booking_IN']);
$checkoutDate = new DateTime($booking['Booking_Out']);
$nights = $checkinDate->diff($checkoutDate)->days;

// Calculate breakdown
$roomCost = $booking['Room_Rate'] * $nights;
$amenityCost = 0;
$serviceCost = 0;

if ($booking['amenity_costs']) {
    $amenityCosts = explode(',', $booking['amenity_costs']);
    $amenityCost = array_sum($amenityCosts);
}

if ($booking['service_costs']) {
    $serviceCosts = explode(',', $booking['service_costs']);
    $serviceCost = array_sum($serviceCosts);
}

$remainingAmount = $booking['Booking_Cost'] - ($booking['paid_amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Paradise Resort</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/booking.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar dashboard-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-palm-tree"></i> Paradise Resort</h2>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="bookings.php" class="nav-link">My Bookings</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="../index.php" class="nav-link">Home</a>
                <div class="user-dropdown">
                    <button class="user-dropdown-btn">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($customer['Cust_FN']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Payment Content -->
    <div class="payment-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <div class="header-content">
                    <div class="breadcrumb">
                        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <span><i class="fas fa-chevron-right"></i></span>
                        <a href="bookings.php">My Bookings</a>
                        <span><i class="fas fa-chevron-right"></i></span>
                        <span>Payment</span>
                    </div>
                    <h1><i class="fas fa-credit-card"></i> Payment</h1>
                    <p>Complete your booking payment for Paradise Resort</p>
                </div>
            </div>
        </div>

        <!-- Payment Section -->
        <div class="payment-section">
            <div class="container">
                <div class="payment-grid">
                    <!-- Booking Summary -->
                    <div class="booking-summary">
                        <div class="summary-card">
                            <h3><i class="fas fa-receipt"></i> Booking Summary</h3>
                            
                            <div class="booking-info">
                                <div class="booking-header">
                                    <h4><?php echo htmlspecialchars($booking['Room_Type']); ?></h4>
                                    <span class="booking-id">#<?php echo str_pad($booking['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                
                                <div class="booking-details">
                                    <div class="detail-row">
                                        <i class="fas fa-calendar-alt"></i>
                                        <div>
                                            <strong>Check-in</strong>
                                            <span><?php echo $checkinDate->format('M d, Y'); ?></span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-calendar-alt"></i>
                                        <div>
                                            <strong>Check-out</strong>
                                            <span><?php echo $checkoutDate->format('M d, Y'); ?></span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-clock"></i>
                                        <div>
                                            <strong>Duration</strong>
                                            <span><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-users"></i>
                                        <div>
                                            <strong>Guests</strong>
                                            <span><?php echo $booking['Guests']; ?> guest<?php echo $booking['Guests'] > 1 ? 's' : ''; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($booking['amenities']): ?>
                                <div class="extras">
                                    <strong><i class="fas fa-star"></i> Amenities:</strong>
                                    <span><?php echo htmlspecialchars($booking['amenities']); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ($booking['services']): ?>
                                <div class="extras">
                                    <strong><i class="fas fa-concierge-bell"></i> Services:</strong>
                                    <span><?php echo htmlspecialchars($booking['services']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Cost Breakdown -->
                            <div class="cost-breakdown">
                                <h4>Cost Breakdown</h4>
                                <div class="cost-item">
                                    <span>Room (<?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>):</span>
                                    <span>₱<?php echo number_format($roomCost, 2); ?></span>
                                </div>
                                
                                <?php if ($amenityCost > 0): ?>
                                <div class="cost-item">
                                    <span>Amenities:</span>
                                    <span>₱<?php echo number_format($amenityCost, 2); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ($serviceCost > 0): ?>
                                <div class="cost-item">
                                    <span>Services:</span>
                                    <span>₱<?php echo number_format($serviceCost, 2); ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="cost-divider"></div>
                                <div class="cost-item total">
                                    <strong>Total Amount:</strong>
                                    <strong>₱<?php echo number_format($booking['Booking_Cost'], 2); ?></strong>
                                </div>

                                <?php if ($booking['paid_amount'] > 0): ?>
                                <div class="cost-item paid">
                                    <span>Already Paid:</span>
                                    <span>-₱<?php echo number_format($booking['paid_amount'], 2); ?></span>
                                </div>
                                <div class="cost-item remaining">
                                    <strong>Amount Due:</strong>
                                    <strong>₱<?php echo number_format($remainingAmount, 2); ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="payment-form">
                        <div class="form-card">
                            <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                            
                            <form id="paymentForm" enctype="multipart/form-data">
                                <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                                <input type="hidden" name="amount" value="<?php echo $remainingAmount; ?>">
                                
                                <!-- Payment Method Selection -->
                                <div class="payment-methods">
                                    <div class="method-option">
                                        <input type="radio" id="gcash" name="payment_method" value="GCash" checked>
                                        <label for="gcash" class="method-label">
                                            <div class="method-icon gcash">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <div class="method-info">
                                                <strong>GCash</strong>
                                                <span>Pay using GCash mobile wallet</span>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="method-option">
                                        <input type="radio" id="paymaya" name="payment_method" value="PayMaya">
                                        <label for="paymaya" class="method-label">
                                            <div class="method-icon paymaya">
                                                <i class="fas fa-wallet"></i>
                                            </div>
                                            <div class="method-info">
                                                <strong>PayMaya</strong>
                                                <span>Pay using PayMaya digital wallet</span>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="method-option">
                                        <input type="radio" id="bank_transfer" name="payment_method" value="Bank Transfer">
                                        <label for="bank_transfer" class="method-label">
                                            <div class="method-icon bank">
                                                <i class="fas fa-university"></i>
                                            </div>
                                            <div class="method-info">
                                                <strong>Bank Transfer</strong>
                                                <span>Direct bank account transfer</span>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="method-option">
                                        <input type="radio" id="credit_card" name="payment_method" value="Credit Card">
                                        <label for="credit_card" class="method-label">
                                            <div class="method-icon credit">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                            <div class="method-info">
                                                <strong>Credit Card</strong>
                                                <span>Pay using credit or debit card</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Payment Instructions -->
                                <div id="payment-instructions" class="payment-instructions">
                                    <!-- Instructions will be loaded based on selected method -->
                                </div>

                                <!-- Receipt Upload -->
                                <div class="form-group">
                                    <label for="receipt"><i class="fas fa-receipt"></i> Payment Receipt</label>
                                    <div class="file-upload">
                                        <input type="file" id="receipt" name="receipt" accept="image/*" required>
                                        <label for="receipt" class="file-upload-label">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Click to upload receipt image</span>
                                        </label>
                                    </div>
                                    <small class="form-text">Please upload a clear image of your payment receipt</small>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" id="submitPayment" class="btn btn-primary btn-lg">
                                    <i class="fas fa-lock"></i> Submit Payment
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Payment method selection
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const instructionsDiv = document.getElementById('payment-instructions');
            
            // Payment instructions for each method
            const instructions = {
                'GCash': `
                    <div class="instructions-card">
                        <h4><i class="fas fa-mobile-alt"></i> GCash Payment Instructions</h4>
                        <ol>
                            <li>Open your GCash app</li>
                            <li>Select "Send Money" or "Pay Bills"</li>
                            <li>Send ₱<?php echo number_format($remainingAmount, 2); ?> to:</li>
                        </ol>
                        <div class="payment-details">
                            <strong>GCash Number:</strong> 09123456789<br>
                            <strong>Account Name:</strong> Paradise Resort<br>
                            <strong>Amount:</strong> ₱<?php echo number_format($remainingAmount, 2); ?>
                        </div>
                        <p><strong>Important:</strong> Take a screenshot of the successful transaction and upload it below.</p>
                    </div>
                `,
                'PayMaya': `
                    <div class="instructions-card">
                        <h4><i class="fas fa-wallet"></i> PayMaya Payment Instructions</h4>
                        <ol>
                            <li>Open your PayMaya app</li>
                            <li>Select "Send Money"</li>
                            <li>Send ₱<?php echo number_format($remainingAmount, 2); ?> to:</li>
                        </ol>
                        <div class="payment-details">
                            <strong>PayMaya Number:</strong> 09987654321<br>
                            <strong>Account Name:</strong> Paradise Resort<br>
                            <strong>Amount:</strong> ₱<?php echo number_format($remainingAmount, 2); ?>
                        </div>
                        <p><strong>Important:</strong> Take a screenshot of the successful transaction and upload it below.</p>
                    </div>
                `,
                'Bank Transfer': `
                    <div class="instructions-card">
                        <h4><i class="fas fa-university"></i> Bank Transfer Instructions</h4>
                        <ol>
                            <li>Go to your bank or use online banking</li>
                            <li>Transfer ₱<?php echo number_format($remainingAmount, 2); ?> to:</li>
                        </ol>
                        <div class="payment-details">
                            <strong>Bank:</strong> BDO Unibank<br>
                            <strong>Account Number:</strong> 1234567890<br>
                            <strong>Account Name:</strong> Paradise Resort Inc.<br>
                            <strong>Amount:</strong> ₱<?php echo number_format($remainingAmount, 2); ?>
                        </div>
                        <p><strong>Important:</strong> Keep the bank transfer slip and upload a photo below.</p>
                    </div>
                `,
                'Credit Card': `
                    <div class="instructions-card">
                        <h4><i class="fas fa-credit-card"></i> Credit Card Payment Instructions</h4>
                        <p>For credit card payments, please visit our resort front desk or call us to process your payment securely.</p>
                        <div class="payment-details">
                            <strong>Phone:</strong> (032) 123-4567<br>
                            <strong>Email:</strong> payments@paradiseresort.com<br>
                            <strong>Amount:</strong> ₱<?php echo number_format($remainingAmount, 2); ?>
                        </div>
                        <p><strong>Note:</strong> Upload the receipt or confirmation after completing the payment.</p>
                    </div>
                `
            };

            // Update instructions when payment method changes
            function updateInstructions() {
                const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
                instructionsDiv.innerHTML = instructions[selectedMethod];
            }

            // Initial load
            updateInstructions();

            // Listen for changes
            paymentMethods.forEach(method => {
                method.addEventListener('change', updateInstructions);
            });

            // File upload preview
            const fileInput = document.getElementById('receipt');
            const fileLabel = document.querySelector('.file-upload-label span');
            
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    fileLabel.textContent = this.files[0].name;
                } else {
                    fileLabel.textContent = 'Click to upload receipt image';
                }
            });

            // Form submission
            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = document.getElementById('submitPayment');
                const formData = new FormData(this);
                
                // Disable button and show loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
                
                // Submit form
                fetch('process_payment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment submitted successfully! Please wait for confirmation.');
                        window.location.href = 'bookings.php?payment_success=1';
                    } else {
                        alert(data.message || 'Payment submission failed. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-lock"></i> Submit Payment';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-lock"></i> Submit Payment';
                });
            });
        });
    </script>

    <style>
        /* Payment Page Styles */
        .payment-container {
            margin-top: 80px;
            min-height: 100vh;
            background: #f8f9fa;
        }

        .payment-section {
            padding: 2rem 0;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .summary-card,
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .summary-card h3,
        .form-card h3 {
            color: #2c5530;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .booking-header h4 {
            color: #4a9960;
            margin: 0;
        }

        .booking-id {
            color: #666;
            font-size: 0.9rem;
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .detail-row i {
            color: #4a9960;
            width: 16px;
        }

        .detail-row div strong {
            display: block;
            font-size: 0.85rem;
            color: #666;
            font-weight: 500;
        }

        .detail-row div span {
            color: #333;
            font-weight: 600;
        }

        .extras {
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #4a9960;
        }

        .extras strong {
            color: #4a9960;
            margin-right: 0.5rem;
        }

        .cost-breakdown {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }

        .cost-breakdown h4 {
            color: #2c5530;
            margin-bottom: 1rem;
        }

        .cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }

        .cost-divider {
            height: 1px;
            background: #ddd;
            margin: 1rem 0;
        }

        .cost-item.total {
            font-size: 1.2rem;
            color: #2c5530;
            border-top: 2px solid #4a9960;
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .cost-item.paid {
            color: #28a745;
        }

        .cost-item.remaining {
            color: #dc3545;
            font-size: 1.1rem;
            border-top: 1px solid #ddd;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }

        .payment-methods {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .method-option {
            position: relative;
        }

        .method-option input[type="radio"] {
            display: none;
        }

        .method-label {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .method-option input[type="radio"]:checked + .method-label {
            border-color: #4a9960;
            background: #f8f9fa;
        }

        .method-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .method-icon.gcash {
            background: #007bff;
        }

        .method-icon.paymaya {
            background: #00d4aa;
        }

        .method-icon.bank {
            background: #6c757d;
        }

        .method-icon.credit {
            background: #ffc107;
            color: #333;
        }

        .method-info strong {
            display: block;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .method-info span {
            color: #666;
            font-size: 0.9rem;
        }

        .instructions-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .instructions-card h4 {
            color: #4a9960;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .instructions-card ol {
            margin-bottom: 1rem;
        }

        .payment-details {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #4a9960;
            margin: 1rem 0;
        }

        .file-upload {
            position: relative;
            margin-bottom: 1rem;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            border: 2px dashed #ddd;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .file-upload-label:hover {
            border-color: #4a9960;
            background: #f8f9fa;
        }

        .file-upload-label i {
            font-size: 2rem;
            color: #4a9960;
            margin-bottom: 0.5rem;
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            width: 100%;
        }

        @media (max-width: 768px) {
            .payment-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .summary-card,
            .form-card {
                padding: 1.5rem;
            }

            .method-label {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }

            .method-info {
                text-align: center;
            }
        }
    </style>
</body>
</html>
