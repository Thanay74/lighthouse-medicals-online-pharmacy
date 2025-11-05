<?php
header('Content-Type: application/json');

// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "db1";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        "success" => false, 
        "message" => "Connection failed: " . $conn->connect_error
    ]));
}

if (isset($_POST['submit'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']); 
    $address = $conn->real_escape_string($_POST['address']);
    $password = $_POST['pass'];
    $confirm_password = $_POST['cpass'];

    // Handle file upload
    if(isset($_FILES['aadhaar_doc'])) {
        $file = $_FILES['aadhaar_doc'];
        $file_type = $file['type'];
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        
        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid file type. Only PDF, JPEG, and PNG files are allowed."
            ]);
            exit();
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode([
                "success" => false,
                "message" => "File size too large. Maximum size is 5MB."
            ]);
            exit();
        }

        // Read file content
        $aadhaar_doc = file_get_contents($file['tmp_name']);
        $aadhaar_doc = $conn->real_escape_string($aadhaar_doc);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Aadhaar document is required."
        ]);
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid email format"
        ]);
        exit();
    }

    // Validate phone number
    if (!preg_match("/^\d{10}$/", $phone)) {
        echo json_encode([
            "success" => false, 
            "message" => "Please enter a valid 10-digit phone number"
        ]);
        exit();
    }

    // Validate password length
    if (strlen($password) < 8) {
        echo json_encode([
            "success" => false, 
            "message" => "Password must be at least 8 characters long"
        ]);
        exit();
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo json_encode([
            "success" => false, 
            "message" => "Passwords do not match."
        ]);
        exit();
    }

    // Check if email already exists
    $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Email already exists. Please use a different email."
        ]);
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Modified insert query without Aadhaar number
    $insert_query = "INSERT INTO users (name, email, phone, address, password, aadhaar_doc, aadhaar_doc_type) 
                    VALUES ('$name', '$email', '$phone', '$address', '$hashed_password', '$aadhaar_doc', '$file_type')";
    
    if ($conn->query($insert_query) === TRUE) {
        echo json_encode([
            "success" => true,
            "message" => "Registration successful!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error: " . $conn->error
        ]);
    }
}

$conn->close();
?>
