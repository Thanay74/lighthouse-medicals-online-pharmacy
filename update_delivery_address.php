<?php
session_start();
header('Content-Type: application/json');

// Function to send error response
function sendError($message) {
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

try {
    // Check if user is logged in and has a current cart
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_cart_id'])) {
        sendError('User not authenticated');
    }

    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['delivery_address']) || empty(trim($input['delivery_address']))) {
        sendError('Delivery address is required');
    }

    $delivery_address = trim($input['delivery_address']);

    // Validate address length (assuming maximum length in database is 255 characters)
    if (strlen($delivery_address) > 255) {
        sendError('Address is too long');
    }

    // Database connection
    require_once 'db_connect.php';

    // Update the delivery address in the cart
    $update_query = "UPDATE carts SET delivery_address = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if (!$stmt) {
        sendError('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param("sii", $delivery_address, $_SESSION['current_cart_id'], $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        sendError('Failed to update address: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        sendError('No cart found or no changes made');
    }

    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Delivery address updated successfully',
        'address' => $delivery_address
    ]);

} catch (Exception $e) {
    sendError('An error occurred: ' . $e->getMessage());
}
?> 