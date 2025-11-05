<?php
session_start();
require 'db_connect.php';

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$orderId = $_GET['order_id'];

$stmt = $conn->prepare("SELECT delivery_status FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $status = $result->fetch_assoc()['delivery_status'];
    echo json_encode(['success' => true, 'delivery_status' => $status]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
} 