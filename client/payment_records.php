<?php
/**
 * Payment Records View (Admin/Testing)
 * Paradise Resort - Guest Accommodation System
 */

require_once '../config/database.php';

// Get all payments with booking details
$paymentsQuery = "SELECT p.*, b.Booking_ID, b.Booking_Status, b.Booking_Cost, 
                         c.Cust_FN, c.Cust_LN, r.Room_Type
                  FROM payment p
                  JOIN booking b ON p.Booking_ID = b.Booking_ID
                  JOIN customer c ON b.Cust_ID = c.Cust_ID
                  JOIN bookingroom br ON b.Booking_ID = br.Booking_ID
                  JOIN room r ON br.Room_ID = r.Room_ID
                  ORDER BY p.Payment_Date DESC";

$payments = executeQuery($paymentsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Records - Paradise Resort</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c5530;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .payments-table th,
        .payments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .payments-table th {
            background: #4a9960;
            color: white;
            font-weight: 600;
        }
        .payments-table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-badge.paid {
            background: #d4edda;
            color: #155724;
        }
        .amount {
            font-weight: 700;
            color: #2c5530;
        }
        .receipt-link {
            color: #4a9960;
            text-decoration: none;
        }
        .receipt-link:hover {
            text-decoration: underline;
        }
        .no-payments {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-credit-card"></i> Payment Records</h1>
        
        <?php if (empty($payments)): ?>
            <div class="no-payments">
                <i class="fas fa-receipt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <h3>No payments found</h3>
                <p>There are no payment records in the system yet.</p>
            </div>
        <?php else: ?>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Booking</th>
                        <th>Customer</th>
                        <th>Room Type</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>#<?php echo str_pad($payment['Payment_ID'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td>#<?php echo str_pad($payment['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($payment['Cust_FN'] . ' ' . $payment['Cust_LN']); ?></td>
                        <td><?php echo htmlspecialchars($payment['Room_Type']); ?></td>
                        <td class="amount">₱<?php echo number_format($payment['Payment_Amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['Payment_Method']); ?></td>
                        <td><?php echo date('M d, Y g:i A', strtotime($payment['Payment_Date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($payment['Booking_Status']); ?>">
                                <?php echo $payment['Booking_Status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($payment['Receipt_Image']): ?>
                                <a href="../<?php echo htmlspecialchars($payment['Receipt_Image']); ?>" 
                                   target="_blank" class="receipt-link">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            <?php else: ?>
                                <span style="color: #999;">No receipt</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
