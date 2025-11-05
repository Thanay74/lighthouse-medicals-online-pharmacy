<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'redirect' => 'login.html',
        'message' => 'Please login first'
    ]);
    exit();
}

try {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['cart_items']) || empty($data['cart_items'])) {
        throw new Exception('Cart is empty');
    }

    // Store cart data in session
    $_SESSION['checkout_cart'] = $data['cart_items'];
    $_SESSION['cart_total'] = $data['total'];

    echo json_encode([
        'success' => true,
        'message' => 'Cart data stored successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 