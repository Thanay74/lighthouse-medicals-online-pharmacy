<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login first',
        'redirect' => 'login.html'
    ]);
    exit();
}

require_once 'db_connect.php';

try {
    // Log incoming data
    error_log('Received POST data: ' . print_r($_POST, true));

    if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
        throw new Exception('Invalid request data');
    }

    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Log processed data
    error_log("Processing cart addition - User ID: $user_id, Product ID: $product_id, Quantity: $quantity");

    // Start transaction
    $conn->begin_transaction();

    // Check if product exists and get its price
    $product_query = "SELECT price FROM products WHERE id = ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Product not found');
    }

    $product = $result->fetch_assoc();
    $price = $product['price'];

    // Check if product already exists in cart
    $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing cart item
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        $update_query = "UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $stmt->execute();
    } else {
        // Insert new cart item
        $insert_query = "INSERT INTO cart (user_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $price);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Get updated cart count
    $count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['total'];

    // Add more detailed logging
    error_log('Cart addition successful - Cart count: ' . $cart_count);

    // When creating a new cart
    if (!isset($_SESSION['current_cart_id'])) {
        $insert_cart = "INSERT INTO carts (user_id, created_at) VALUES (?, NOW())";
        $stmt = $conn->prepare($insert_cart);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $_SESSION['current_cart_id'] = $conn->insert_id; // Store new cart ID in session
    }

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart successfully',
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    // Log the error
    error_log('Error in add_to_cart.php: ' . $e->getMessage());
    
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?> 
