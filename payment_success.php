<?php
session_start();
require 'vendor/autoload.php';
use Razorpay\Api\Api;
require 'db_connect.php'; // Add this line to include your database connection file

// Razorpay Test API Key and Secret
$apiKey = 'rzp_test_sPafHM8S92pTTJ';
$apiSecret = 'H1fKIMZcz92UJ9KmzLn94EPC';
// Initialize Razorpay API
$api = new Api($apiKey, $apiSecret);

// Get payment ID from URL parameter
$paymentId = $_GET['payment_id'] ?? null;

if (!$paymentId) {
    die("Payment ID is required.");
}

// Fetch order ID from payments table
$paymentQuery = "SELECT order_id FROM payments WHERE payment_id = ?";
$stmt = $conn->prepare($paymentQuery);

if (!$stmt) {
    die("Error preparing query: " . $conn->error);
}

$stmt->bind_param("s", $paymentId);
$stmt->execute();
$paymentResult = $stmt->get_result();

if ($paymentResult->num_rows === 0) {
    die("Payment not found in database.");
}

$payment = $paymentResult->fetch_assoc();
$orderId = $payment['order_id'];

// Fetch order details
$orderQuery = "SELECT * FROM orders WHERE id = ?";
$stmt = $conn->prepare($orderQuery);

if (!$stmt) {
    die("Error preparing query: " . $conn->error);
}

$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    die("Order not found in database.");
}

$order = $orderResult->fetch_assoc();
$deliveryStatus = $order['delivery_status'] ?? 'order pending';

// Debugging: Log the fetched order
error_log("Fetched order: " . print_r($order, true));

// Verify payment
try {
    $payment = $api->payment->fetch($paymentId);

    if ($payment->status === 'captured') {
        // Payment successful
      
        echo "<p></p>";
    
        echo "<p> </p>";
    } else {
        // Payment failed
        echo "<h1>Payment Failed</h1>";
        echo "<p>Status: " . $payment->status . "</p>";
    }
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #ffffff 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .payment-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 74, 123, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
            border: 1px solid rgba(15, 74, 123, 0.1);
        }
        
        .success-icon {
            color: #28a745;
            font-size: 64px;
            margin-bottom: 20px;
            background: #e8f5e9;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(40, 167, 69, 0.1);
        }
        
        h1 {
            color: #0f4a7b;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .payment-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: left;
            border: 1px solid rgba(15, 74, 123, 0.05);
        }
        
        .payment-details p {
            margin: 12px 0;
            color: #495057;
            font-size: 15px;
        }
        
        .payment-details strong {
            color: #0f4a7b;
            display: inline-block;
            width: 100px;
            font-weight: 500;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-top: 30px;
            color: #0f4a7b;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(15, 74, 123, 0.05);
        }
        
        .back-link:hover {
            background: rgba(15, 74, 123, 0.1);
            transform: translateY(-1px);
        }
        
        .back-link:active {
            transform: translateY(0);
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            border: 1px solid rgba(220, 53, 69, 0.1);
        }
        
        .razorpay-brand {
            margin-top: 30px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .razorpay-brand a {
            color: #0f4a7b;
            text-decoration: none;
            font-weight: 500;
        }
        
        .razorpay-brand a:hover {
            text-decoration: underline;
        }
        
        .details-toggle {
            color: #0f4a7b;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            display: inline-block;
            transition: color 0.2s ease;
        }
        
        .details-toggle:hover {
            color: #0c3a60;
        }
        
        .payment-details.hidden {
            display: none;
        }
        
        .track-button {
            display: inline-flex;
            align-items: center;
            margin: 20px 0;
            padding: 12px 24px;
            background-color: #0f4a7b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .track-button:hover {
            background-color: #0c3a60;
            transform: translateY(-1px);
        }
        
        .track-button:active {
            transform: translateY(0);
        }
        
        .track-button i {
            margin-right: 8px;
        }
        
        .delivery-status {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 12px;
            border: 1px solid rgba(15, 74, 123, 0.05);
        }
        
        .status-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        
        .status-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .status-icon.pending {
            background-color: #ffc107;
            color: white;
        }
        
        .status-icon.completed {
            background-color: #28a745;
            color: white;
        }
        
        .status-text {
            color: #495057;
        }
        
        .order-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .order-summary p {
            margin: 10px 0;
            color: #495057;
        }
        
        .status-refresh {
            margin-top: 10px;
            color: #0f4a7b;
            cursor: pointer;
            font-size: 14px;
        }
       
        .status-refresh:hover {
            text-decoration: underline;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="payment-container">
        <?php if ($payment->status === 'captured'): ?>
            <div class="success-icon">✓</div>
            <h1>Payment Successful!</h1>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
                <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                <p><strong>Order Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
            </div>

            <!-- Delivery Status Section -->
            <div class="delivery-status" id="deliveryStatus">
                <div class="status-item">
                    <div class="status-icon1 <?php echo ($deliveryStatus === 'delivered') ? 'completed' : 'pending'; ?>">
                    
                    </div>
                    <div class="status-text"></div>
                </div>
                <div class="status-item">
                    <div class="status-icon <?php echo ($deliveryStatus === 'delivered') ? 'completed' : 'pending'; ?>">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="status-text">Not Delivered</div>
                </div>
            </div>

            <div class="status-refresh" onclick="refreshStatus()">
                <i class="fas fa-sync-alt"></i> Refresh Status
            </div>

            <a href="index.html" class="back-link">
                ← Back to Home
            </a>
            <div class="razorpay-brand">
                Powered by <a href="https://razorpay.com" target="_blank">Razorpay</a>
            </div>
        <?php else: ?>
            <div class="error-message">
                <h1>Payment Failed</h1>
                <p>Status: <?php echo $payment->status; ?></p>
            </div>
            <div class="razorpay-brand">
                Powered by <a href="https://razorpay.com" target="_blank">Razorpay</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function refreshStatus() {
        fetch('get_delivery_status.php?order_id=<?php echo $orderId; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update status icons
                    const statusIcons = document.querySelectorAll('.status-icon');
                    const statusTexts = document.querySelectorAll('.status-text');
                    
                    if (data.delivery_status === 'delivered') {
                        statusIcons.forEach(icon => icon.classList.replace('pending', 'completed'));
                        statusTexts[1].textContent = 'Delivered';
                    } else {
                        statusIcons.forEach(icon => icon.classList.replace('completed', 'pending'));
                        statusTexts[1].textContent = 'Not Delivered';
                    }
                }
            });
    }

    // Auto-refresh status every 30 seconds
    setInterval(refreshStatus, 300);
    </script>
</body>
</html> 