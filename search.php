<?php
// Enable CORS and set headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Include database connection
require_once 'db_connect.php';

// Function to log errors (optional)
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'search_errors.log');
}

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the search term from the request body
        $json = file_get_contents('php://input');
        $data = json_decode($json);
        
        if (!$data || !isset($data->searchTerm)) {
            throw new Exception('Search term is required');
        }

        // Sanitize the search term
        $searchTerm = $conn->real_escape_string($data->searchTerm);
        
        // Updated SQL query to search for names starting with the search term
        $stmt = $conn->prepare("SELECT name, description, price, image_url, 
                              DATE_FORMAT(manufacture_date, '%Y-%m-%d') as manufacture_date,
                              DATE_FORMAT(expiry_date, '%Y-%m-%d') as expiry_date
                              FROM products 
                              WHERE name LIKE CONCAT(?, '%') 
                              LIMIT 10");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }

        // Bind the search term parameter (only for name)
        $stmt->bind_param('s', $searchTerm);
        
        // Execute the query
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute query: ' . $stmt->error);
        }

        // Get the results
        $result = $stmt->get_result();
        
        // Fetch all results
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'name' => htmlspecialchars($row['name']),
                'description' => htmlspecialchars($row['description']),
                'price' => number_format($row['price'], 2),
                'image' => htmlspecialchars($row['image_url']),
                'manufacture_date' => htmlspecialchars($row['manufacture_date']),
                'expiry_date' => htmlspecialchars($row['expiry_date'])
            ];
        }

        // Return the results
        echo json_encode([
            'status' => 'success',
            'results' => $products,
            'count' => count($products)
        ]);

        // Close the statement
        $stmt->close();

    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    // Log the error
    logError($e->getMessage());
    
    // Send error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close the database connection
$conn->close();
?> 