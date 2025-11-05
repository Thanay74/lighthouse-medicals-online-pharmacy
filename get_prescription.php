<?php
session_start();
header('Content-Type: application/json');

// Verify admin authentication here

if (!isset($_GET['order_id']) || !filter_var($_GET['order_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'Valid Order ID required']);
    exit;
}

require_once 'db_connect.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $query = "SELECT prescription, mime_type FROM orders WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_GET['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        header('Content-Type: ' . $row['mime_type']); // Set the correct MIME type
        echo $row['prescription']; // Output the raw prescription data
    } else {
        echo json_encode(['success' => false, 'message' => 'Prescription not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>