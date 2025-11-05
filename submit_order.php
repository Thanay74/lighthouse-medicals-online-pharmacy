<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Assume $_POST contains order details like product IDs, quantities, etc.
$orderDetails = $_POST['orderDetails']; // This should be an array of details
$userId = $_SESSION['user_id'];

// Insert order into database
$stmt = $conn->prepare("INSERT INTO orders (user_id, status) VALUES (?, 'pending')");
$stmt->bind_param("i", $userId);
$stmt->execute();
$orderId = $stmt->insert_id;

// Insert order items (simplified example)
foreach ($orderDetails as $item) {
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $orderId, $item['product_id'], $item['quantity']);
    $stmt->execute();
}

echo json_encode(['success' => true, 'orderId' => $orderId]);
?> 