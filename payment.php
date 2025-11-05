<?php
session_start(); // Start session at the beginning

require 'vendor/autoload.php'; // Include Composer's autoload file
use Razorpay\Api\Api;

// Razorpay Test API Key and Secret
$apiKey = 'rzp_test_sPafHM8S92pTTJ';
$apiSecret = 'H1fKIMZcz92UJ9KmzLn94EPC';

// Initialize Razorpay API
$api = new Api($apiKey, $apiSecret);

// Database connection
require_once 'db_connect.php';

// Fetch order details from database
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_STRING);
if (!$orderId) {
    die("Order ID is required.");
}

// Fetch order details from database
$orderQuery = "SELECT o.id, o.user_id, c.total_amount 
               FROM orders o
               JOIN carts c ON o.cart_id = c.id
               WHERE o.id = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    die("Invalid order ID.");
}

$orderData = $orderResult->fetch_assoc();
$orderAmount = $orderData['total_amount'] * 100; // Convert to paise
$orderCurrency = 'INR';

// Fetch delivery status
$deliveryStatusQuery = "SELECT delivery_status FROM orders WHERE id = ?";
$stmt = $conn->prepare($deliveryStatusQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$deliveryResult = $stmt->get_result();
$deliveryStatus = $deliveryResult->fetch_assoc()['delivery_status'] ?? 'order pending';

try {
    // Create a Razorpay order
    $razorpayOrder = $api->order->create([
        'amount' => $orderAmount,
        'currency' => $orderCurrency,
        'receipt' => 'order_' . $orderId,
        'payment_capture' => 1
    ]);

    // Store Razorpay order ID and order details in session
    $_SESSION['razorpay_order_id'] = $razorpayOrder->id;
    $_SESSION['order_id'] = $orderId;
    $_SESSION['user_id'] = $orderData['user_id'];

} catch (Exception $e) {
    die("Error creating order: " . htmlspecialchars($e->getMessage()));
}

// Razorpay Checkout Form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        /* ... existing styles ... */

        .delivery-status {
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .status-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        .status-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        .status-icon.pending {
            background-color: #ffc107;
            color: yellow;
        }

        .status-icon.completed {
            background-color: #28a745;
            color: green;
        }

        .status-text {
            font-size: 16px;
            color: #495057;
        }
    </style>
    <script>
        // Function to update delivery status
function updateDeliveryStatus(orderId) {
    fetch(`get_delivery_status.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const statusIcon = document.querySelector('.status-icon');
                const statusText = document.querySelector('.status-text');

                // Update status icon and text
                if (data.delivery_status === 'delivered') {
                    statusIcon.classList.remove('pending');
                    statusIcon.classList.add('completed');
                    statusText.textContent = 'Delivered';
                } else {
                    statusIcon.classList.remove('completed');
                    statusIcon.classList.add('pending');
                    statusText.textContent = 'Order Pending';
                }
            } else {
                console.error('Failed to fetch delivery status:', data.message);
            }
        })
        .catch(error => {
            console.error('Error updating delivery status:', error);
        });
}

// Function to auto-refresh status every 30 seconds
function autoRefreshStatus(orderId) {
    setInterval(() => {
        updateDeliveryStatus(orderId);
    }, 30000); // Refresh every 30 seconds
}

// Call the function when the page loads
document.addEventListener('DOMContentLoaded', () => {
    const orderId = <?php echo $orderId; ?>; // Pass the order ID from PHP
    updateDeliveryStatus(orderId); // Initial status update
    autoRefreshStatus(orderId); // Start auto-refresh
});
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
  
    <script>
        // Automatically trigger Razorpay payment on page load
        window.onload = function() {
            var options = {
                "key": "<?php echo htmlspecialchars($apiKey); ?>", // Your Test API Key
                "amount": "<?php echo htmlspecialchars($orderAmount); ?>", // Amount in paise
                "currency": "<?php echo htmlspecialchars($orderCurrency); ?>",
                "name": "Light House Medicals", // Your company name
                "description": "Payment for Order #<?php echo htmlspecialchars($orderId); ?>",
                "image": "https://example.com/logo.png", // Your logo URL
                "order_id": "<?php echo htmlspecialchars($razorpayOrder->id); ?>", // Razorpay Order ID
                "notes": {
                    order_id: "<?php echo $orderId; ?>"
                },
                "handler": function (response) {
                    // Send payment details to server
                    fetch('process_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_signature: response.razorpay_signature,
                            order_id: "<?php echo $orderId; ?>"
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect with both payment_id and order_id
                            window.location.href = "payment_success.php?payment_id=" + 
                                response.razorpay_payment_id + 
                                "&order_id=<?php echo $orderId; ?>";
                        } else {
                            alert("Payment verification failed: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("An error occurred while processing your payment");
                    });
                },
                "prefill": {
                    "name": "John Doe", // Prefill customer name
                    "email": "john.doe@example.com", // Prefill customer email
                    "contact": "9999999999" // Prefill customer phone
                },
                "theme": {
                    "color": "#F37254" // Customize the theme color
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        };
    </script>

    <!-- Delivery Status Section -->
    <div class="delivery-status">
        <h3>Delivery Status</h3>
        <div class="status-item">
            <div class="status-icon <?php echo ($deliveryStatus === 'delivered') ? 'completed' : 'pending'; ?>">
                <i class="fas fa-truck"></i>
            </div>
            <div class="status-text">
                <?php echo ucfirst(str_replace('_', ' ', $deliveryStatus)); ?>
            </div>
        </div>
    </div>
</body>
</html> 