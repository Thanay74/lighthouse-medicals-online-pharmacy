<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    require_once 'db_connect.php';
    
    $orderId = $_GET['order_id'];

    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $status = $result->fetch_assoc();

    echo json_encode(['status' => $status['status']]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 