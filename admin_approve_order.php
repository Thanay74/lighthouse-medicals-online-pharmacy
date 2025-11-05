<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure no output before headers
ob_start();

header('Content-Type: application/json');

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

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get cart items for the order
        $cart_items_query = "SELECT ci.product_name, ci.quantity 
                            FROM cart_items ci
                            JOIN orders o ON ci.cart_id = o.cart_id
                            WHERE o.id = ?";
        $stmt = $conn->prepare($cart_items_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $cart_items_result = $stmt->get_result();
        $cart_items = $cart_items_result->fetch_all(MYSQLI_ASSOC);

        if (empty($cart_items)) {
            throw new Exception('No items found in the order');
        }

        // Reduce product stock
        foreach ($cart_items as $item) {
            // Get product_id from product_name
            $get_product_id_query = "SELECT id FROM products WHERE name = ?";
            $stmt = $conn->prepare($get_product_id_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $item['product_name']);
            $stmt->execute();
            $product_result = $stmt->get_result();
            $product = $product_result->fetch_assoc();

            if (!$product) {
                throw new Exception("Product not found: " . $item['product_name']);
            }

            $product_id = $product['id'];

            // Update product stock
            $update_stock_query = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
            $stmt = $conn->prepare($update_stock_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iii", $item['quantity'], $product_id, $item['quantity']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update product stock for product: " . $item['product_name']);
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception("Insufficient stock for product: " . $item['product_name']);
            }
        }

        // Update the order status
        $update_order_query = "UPDATE orders SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($update_order_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception('Failed to approve order or order already approved');
        }

        // Commit transaction
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Order approved and stock updated successfully!']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error in admin_approve_order.php: " . $e->getMessage());
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Clear any buffered output
ob_end_flush();
?>