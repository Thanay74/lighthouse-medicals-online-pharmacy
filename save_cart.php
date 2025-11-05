<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get the user ID from session
$user_id = $_SESSION['user_id'];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Database connection
    require_once 'db_connect.php'; // Make sure this file exists with your DB credentials

    // Start transaction
    $conn->begin_transaction();

    // Create cart entry
    $cart_query = "INSERT INTO carts (user_id, total_amount, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($cart_query);
    $total_amount = floatval($data['total']);
    $stmt->bind_param("id", $user_id, $total_amount);
    $stmt->execute();
    $cart_id = $conn->insert_id;

    // Insert cart items
    $item_query = "INSERT INTO cart_items (cart_id, product_id, product_name, quantity, price, image_url) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($item_query);

    foreach ($data['items'] as $item) {
        // Fetch product_id from products table using product_name
        $get_product_id_query = "SELECT id FROM products WHERE name = ?";
        $stmt2 = $conn->prepare($get_product_id_query);
        $stmt2->bind_param("s", $item['name']);
        $stmt2->execute();
        $product_result = $stmt2->get_result();
        $product = $product_result->fetch_assoc();

        if (!$product) {
            throw new Exception("Product not found: " . $item['name']);
        }

        $product_id = $product['id'];

        // Insert cart item with product_id
        $stmt->bind_param("iisids", 
            $cart_id,
            $product_id,
            $item['name'],
            $item['quantity'],
            $item['price'],
            $item['image']
        );
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Store cart_id in session for checkout page
    $_SESSION['current_cart_id'] = $cart_id;

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
?> 