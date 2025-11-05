<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=db1;charset=utf8mb4",
        "root",
        "",
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    // Updated query to include phone and address
    $stmt = $conn->prepare("SELECT id, name, email, phone, address, aadhaar_doc, aadhaar_doc_type FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if document_type column exists, if not create it
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'document_type'");
    if ($checkColumn->rowCount() == 0) {
        $conn->exec("ALTER TABLE users ADD COLUMN document_type VARCHAR(50) AFTER aadhaar_doc_type");
    }

    // Debug: Print table structure
    $tableInfo = $conn->query("DESCRIBE users");
    error_log("Table structure: " . print_r($tableInfo->fetchAll(PDO::FETCH_ASSOC), true));

    // Process each user's data
    foreach ($users as &$user) {
        error_log("Processing user ID: " . $user['id']);
        error_log("Document type: " . ($user['aadhaar_doc_type'] ?? 'null'));
        error_log("Has document: " . ($user['aadhaar_doc'] ? 'yes' : 'no'));

        if (!is_null($user['aadhaar_doc'])) {
            // Convert BLOB to base64
            $user['aadhaar_doc'] = base64_encode($user['aadhaar_doc']);
            
            // Set default document type if not specified
            if (empty($user['aadhaar_doc_type'])) {
                $user['aadhaar_doc_type'] = 'application/pdf';
            }
            
            error_log("Document processed successfully for user " . $user['id']);
        } else {
            $user['aadhaar_doc'] = null;
            $user['aadhaar_doc_type'] = null;
            error_log("No document found for user " . $user['id']);
        }
    }

    // Debug: Print processed data
    error_log("Processed data: " . print_r($users, true));

    echo json_encode([
        'status' => 'success',
        'data' => $users,
        'debug' => [
            'userCount' => count($users),
            'hasDocuments' => array_map(function($user) {
                return [
                    'id' => $user['id'],
                    'hasDoc' => !is_null($user['aadhaar_doc'])
                ];
            }, $users)
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in users_api.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}

$conn = null;
?> 