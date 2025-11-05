<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    // Database connection
    require_once 'db_connect.php';

    // Get the latest cart for this user
    $query = "SELECT ci.* 
              FROM cart_items ci 
              JOIN carts c ON ci.cart_id = c.id 
              WHERE c.user_id = ? 
              AND c.id = (SELECT MAX(id) FROM carts WHERE user_id = ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = [
            'product_id' => $row['id'],
            'name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => $row['price']
        ];
        $total += $row['price'] * $row['quantity'];
    }

    echo json_encode([
        'success' => true,
        'cart_items' => $cart_items,
        'total' => $total
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
?> 