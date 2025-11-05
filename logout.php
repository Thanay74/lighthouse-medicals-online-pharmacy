<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear cookies
setcookie('user_id', '', time() - 3600, '/');
setcookie('email', '', time() - 3600, '/');

// Return success response
echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
?> 