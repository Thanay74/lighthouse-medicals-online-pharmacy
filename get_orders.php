<?php
session_start();
header('Content-Type: application/json');

// Verify admin authentication here (if applicable)

require_once 'db_connect.php';

try {
    $query = "SELECT o.id, u.name as user_name, u.email, c.delivery_address, 
                     o.created_at, c.total_amount, o.status, o.prescription, o.prescription_type,
                     GROUP_CONCAT(CONCAT(p.name, ' (ID:', ci.product_id, ', Qty:', ci.quantity, ')') SEPARATOR '<br>') as products
              FROM orders o
              JOIN users u ON o.user_id = u.id
              JOIN carts c ON o.cart_id = c.id
              JOIN cart_items ci ON c.id = ci.cart_id
              JOIN products p ON ci.product_id = p.id
              GROUP BY o.id
              ORDER BY o.created_at DESC";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        throw new Exception("Database Query Failed: " . mysqli_error($conn));
    }

    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['prescription'])) {
            $row['prescription'] = base64_encode($row['prescription']); // Convert BLOB to Base64
        } else {
            $row['prescription'] = null; // Handle empty prescriptions
        }
        
        $orders[] = $row;
    }

    echo json_encode($orders);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
