<?php
session_start(); // Add this at the top

$data = json_decode(file_get_contents('php://input'), true);
$productId = $data['product_id'];
$change = $data['change'];

if (isset($_SESSION['cart'][$productId])) {
    $_SESSION['cart'][$productId] += $change;
    
    if ($_SESSION['cart'][$productId] < 1) {
        unset($_SESSION['cart'][$productId]);
    }
}

echo json_encode(['success' => true]);
?> 