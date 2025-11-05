<?php
session_start();
require 'db_connect.php';

// Check if user is logged in as delivery
if (!isset($_SESSION['is_delivery']) || !$_SESSION['is_delivery']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'];
$newStatus = $data['new_status'];

// Validate status
$allowedStatuses = ['order pending', 'delivered'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Update delivery_status
$stmt = $conn->prepare("UPDATE orders SET delivery_status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $orderId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}