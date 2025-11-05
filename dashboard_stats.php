<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=db1;charset=utf8mb4",
        "root",
        "",
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    // Get total users
    $userStmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $userStmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Get total products
    $productStmt = $conn->query("SELECT COUNT(*) as total_products FROM products");
    $totalProducts = $productStmt->fetch(PDO::FETCH_ASSOC)['total_products'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 