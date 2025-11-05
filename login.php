<?php
session_start();

// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "db1";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Debugging: Log the email and password
    error_log("Email: " . $email);
    error_log("Password: " . $password);

    // Special case for delivery login
    if ($email === 'delivery01@gmail.com' && $password === '12345678') {
        // Debugging: Log successful delivery login
        error_log("Delivery login successful");
        
        // Query to get delivery user details from database
        $sql = "SELECT * FROM delivery_users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Set session variables for delivery user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = 0; // Not an admin
            $_SESSION['is_delivery'] = 1; // Mark as delivery user
            $_SESSION['delivery_name'] = $user['name']; // Store delivery person's name

            // Debugging: Log session variables
            error_log("Session: " . print_r($_SESSION, true));

            // Set cookies for 30 days
            setcookie('user_id', $user['id'], time() + (86400 * 30), "/");
            setcookie('email', $user['email'], time() + (86400 * 30), "/");
            setcookie('delivery_name', $user['name'], time() + (86400 * 30), "/");

            // Redirect to delivery panel
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'redirect' => 'deliverypanel.html',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name']
                ]
            ]);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delivery user not found']);
            exit();
        }
    }

    // Query to check user credentials and is_admin status
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, start a new session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Set cookies for 30 days
            setcookie('user_id', $user['id'], time() + (86400 * 30), "/");
            setcookie('email', $user['email'], time() + (86400 * 30), "/");
            
            // Check if user is admin and redirect accordingly
            $redirect = $user['is_admin'] ? 'admin-panel.html' : 'index.html';
            
            // Return success response with appropriate redirect URL
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'redirect' => $redirect,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'is_admin' => $user['is_admin']
                ]
            ]);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
            exit();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
        exit();
    }
}

$conn->close();
?> 