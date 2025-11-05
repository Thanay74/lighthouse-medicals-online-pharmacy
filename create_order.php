<?php
session_start();
require 'db_connect.php';

// Create order in database
$stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("id", $_SESSION['user_id'], $totalAmount);
$stmt->execute();
$dbOrderId = $stmt->insert_id;

// Create Razorpay order
$api = new Razorpay\Api\Api($apiKey, $apiSecret);
$razorpayOrder = $api->order->create([
    'amount' => $totalAmount * 100, // in paise
    'currency' => 'INR',
    'receipt' => $dbOrderId
]);

echo json_encode([
    'order_id' => $dbOrderId,
    'razorpay_order_id' => $razorpayOrder->id,
    'amount' => $razorpayOrder->amount
]);