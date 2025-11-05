<?php
header('Content-Type: application/json'); // Ensure JSON response

session_start();

// Debugging: Log session data
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Connect to your database
$conn = new mysqli('localhost', 'root', '', 'db1');

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$userId = $_SESSION['user_id'];
$query = "SELECT name, email, phone, address FROM users WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'name' => $user['name'] ?? 'Not provided',
        'email' => $user['email'] ?? 'Not provided',
        'phone' => $user['phone'] ?? 'Not provided',
        'address' => $user['address'] ?? 'Not provided'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?> 