<?php
header('Content-Type: application/json'); // Ensure the response is JSON
require_once 'db_connect.php';

try {
    // Check if the request is an AJAX call and the necessary data is provided
    if (!isset($_POST['order_id'])) {
        throw new Exception('No order ID provided');
    }

    $orderId = $_POST['order_id'];

    // Validate order ID
    if (!is_numeric($orderId)) {
        throw new Exception('Invalid order ID');
    }

    // Update the order status to "rejected"
    $stmt = $conn->prepare("UPDATE orders SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Order rejected successfully!']);
    } else {
        throw new Exception('Failed to reject order or order already rejected');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>