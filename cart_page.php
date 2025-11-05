<?php

if (!isset($_SESSION['current_cart_id'])) {
    // Create new empty cart
    $insert_cart = "INSERT INTO carts (user_id, created_at) VALUES (?, NOW())";
    $stmt = $conn->prepare($insert_cart);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $_SESSION['current_cart_id'] = $conn->insert_id;
} 