<?php
// Turn off all error reporting to prevent HTML output
error_reporting(0);

session_start();
require 'vendor/autoload.php';
use Razorpay\Api\Api;

header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Verify payment details
    $apiKey = 'rzp_test_sPafHM8S92pTTJ';
    $apiSecret = 'H1fKIMZcz92UJ9KmzLn94EPC';
    $api = new Api($apiKey, $apiSecret);

    // Get POST data
    $rawData = file_get_contents('php://input');
    if (empty($rawData)) {
        throw new Exception('No input data received');
    }
    
    $data = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    // Verify required fields
    if (empty($data['razorpay_order_id']) || empty($data['razorpay_payment_id']) || empty($data['razorpay_signature']) || empty($data['order_id'])) {
        throw new Exception('Missing required payment data');
    }

    // Verify payment signature
    $attributes = [
        'razorpay_order_id' => $data['razorpay_order_id'],
        'razorpay_payment_id' => $data['razorpay_payment_id'],
        'razorpay_signature' => $data['razorpay_signature']
    ];
    
    $api->utility->verifyPaymentSignature($attributes);

    // Database connection
    require_once 'db_connect.php';
    
    // Check if connection is successful
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Get order details
    $orderId = $data['order_id'];
    $orderQuery = "SELECT c.total_amount 
                   FROM carts c
                   JOIN orders o ON c.id = o.cart_id
                   WHERE o.id = ?";
    
    $stmt = $conn->prepare($orderQuery);
    if (!$stmt) {
        // Get the specific database error
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $orderId);
    if (!$stmt->execute()) {
        throw new Exception('Database query execution failed: ' . $stmt->error);
    }
    
    $orderResult = $stmt->get_result();
    if ($orderResult->num_rows === 0) {
        throw new Exception('Order not found');
    }
    
    $orderData = $orderResult->fetch_assoc();
    if (empty($orderData['total_amount'])) {
        throw new Exception('Invalid order amount');
    }

    // Insert payment record
    $insertPayment = "INSERT INTO payments (
                        order_id, 
                        user_id, 
                        payment_id, 
                        amount, 
                        status, 
                        created_at
                      ) VALUES (?, ?, ?, ?, 'success', NOW())";
    
    $stmt = $conn->prepare($insertPayment);
    if (!$stmt) {
        throw new Exception('Payment record preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("iisi", 
        $orderId,
        $_SESSION['user_id'],
        $data['razorpay_payment_id'],
        $orderData['total_amount']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Payment record insertion failed: ' . $stmt->error);
    }

    // Update order status
    $updateOrder = "UPDATE orders SET status = 'paid' WHERE id = ?";
    $stmt = $conn->prepare($updateOrder);
    if (!$stmt) {
        throw new Exception('Order status update preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $orderId);
    if (!$stmt->execute()) {
        throw new Exception('Order status update failed: ' . $stmt->error);
    }

    $response['success'] = true;
    $response['message'] = 'Payment processed successfully';

} catch (Exception $e) {
    $response['message'] = 'Payment processing failed: ' . $e->getMessage();
    error_log("Payment processing error: " . $e->getMessage());
}

// Ensure we only output JSON
echo json_encode($response);
exit;
?>