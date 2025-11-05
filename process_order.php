<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_cart_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

try {
    require_once 'db_connect.php';

    // Check if prescription file was uploaded
    if (!isset($_FILES['prescription']) || $_FILES['prescription']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Prescription file is required');
    }

    // Read the prescription file
    $prescriptionContent = file_get_contents($_FILES['prescription']['tmp_name']);
    
    // Get delivery address from cart
    $address_query = "SELECT delivery_address FROM carts WHERE id = ?";
    $stmt = $conn->prepare($address_query);
    $stmt->bind_param("i", $_SESSION['current_cart_id']);
    $stmt->execute();
    $address_result = $stmt->get_result();
    $address_data = $address_result->fetch_assoc();
    $delivery_address = $address_data['delivery_address'] ?? '';

    // Start transaction
    $conn->begin_transaction();

    // Create order record with address
    $order_query = "INSERT INTO orders (user_id, cart_id, prescription, status, delivery_address, created_at) 
                    VALUES (?, ?, ?, 'pending', ?, NOW())";
    
    $stmt = $conn->prepare($order_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iiss", $_SESSION['user_id'], $_SESSION['current_cart_id'], $prescriptionContent, $delivery_address);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $order_id = $conn->insert_id;

    // Mark cart as processed
    $update_cart = "UPDATE carts SET status = 'processed' WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_cart);
    $stmt->bind_param("ii", $_SESSION['current_cart_id'], $_SESSION['user_id']);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Clear the current cart from session
    unset($_SESSION['current_cart_id']);

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order placed successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Error in process_order.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);
}
?> 