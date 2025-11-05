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

    $data = json_decode(file_get_contents('php://input'), true);

    $stmt = $conn->prepare("INSERT INTO products (name, description, image_url, manufacture_date, expiry_date, stock, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $data['name'],
        $data['description'],
        $data['image_url'],
        $data['manufacture_date'],
        $data['expiry_date'],
        $data['stock'],
        $data['price']
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Product added successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 