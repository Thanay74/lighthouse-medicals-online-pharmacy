<?php
session_start();
require 'db_connect.php';

// Check if user is logged in as delivery
if (!isset($_SESSION['is_delivery']) || !$_SESSION['is_delivery']) {
    header('Location: login.php');
    exit();
}

// Fetch orders with delivery status
$sql = "SELECT o.id, o.user_id, o.prescription, o.delivery_status, o.delivery_address, o.created_at, 
               u.name AS customer_name, u.phone AS customer_phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.delivery_status IN ('order pending')
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Panel</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        
        .panel {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background-color: #0f4a7b;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .order-card {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .order-id {
            font-size: 1.2em;
            font-weight: 600;
            color: #0f4a7b;
            margin-bottom: 10px;
        }
        
        .order-details p {
            margin: 8px 0;
            color: #495057;
        }
        
        .status {
            font-size: 0.9em;
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
            margin: 10px 0;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-in_progress {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .actions {
            margin-top: 15px;
        }
        
        .status-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 8px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        .status-btn.status-in_progress {
            background-color: #0f4a7b;
            color: white;
        }
        
        .status-btn.status-in_progress:hover {
            background-color: #0c3a60;
        }
        
        .status-btn.status-delivered {
            background-color: #28a745;
            color: white;
        }
        
        .status-btn.status-delivered:hover {
            background-color: #218838;
        }
        
        .no-orders {
            text-align: center;
            color: #6c757d;
            padding: 40px;
        }
    </style>
</head>
<body>
    <div class="panel">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['delivery_name']); ?></h1>
            <p>Manage your delivery orders</p>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while($order = $result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                    <div class="order-details">
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="status status-<?php echo str_replace(' ', '-', $order['delivery_status']); ?>">
                        <?php echo ucfirst($order['delivery_status']); ?>
                    </div>
                    <div class="actions">
                        <button class="status-btn status-delivered" 
                                onclick="updateStatus(<?php echo $order['id']; ?>, 'delivered')">
                            Mark as Delivered
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-orders">
                <p>No orders to deliver at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function updateStatus(orderId, status) {
        fetch('update_delivery_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                new_status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update status');
            }
        });
    }
    </script>
</body>
</html> 