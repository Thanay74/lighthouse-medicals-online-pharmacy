<?php
session_start();

$response = [
    'loggedIn' => isset($_SESSION['user_id']),
    'userId' => $_SESSION['user_id'] ?? null
];

header('Content-Type: application/json');
echo json_encode($response);
?> 