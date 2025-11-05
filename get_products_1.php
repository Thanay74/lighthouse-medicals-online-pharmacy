<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    $query = "SELECT name, description, price, image_url
              FROM products limit 4 offset 51"; // Adjust limit as needed

    $result = mysqli_query($conn, $query);

    if (!$result) {
        throw new Exception("Database Query Failed: " . mysqli_error($conn));
    }

    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = [
            'name' => htmlspecialchars($row['name']),
            'description' => htmlspecialchars($row['description']),
            'price' => number_format($row['price'], 2),
            'image_url' => htmlspecialchars($row['image_url']),
            ];
    }

    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 