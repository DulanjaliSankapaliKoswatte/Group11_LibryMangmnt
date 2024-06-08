<?php

header("Access-Control-Allow-Origin: *");  // You can also use '*' to allow all origins
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");  // Ensure to allow POST for your request


require 'validate_token.php';

try {
    // Fetch Authorization header
    $authHeader = null;

    function getAuthorizationHeader() {
        $headers = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Check for both normal and lowercase.
            $headers = isset($requestHeaders['Authorization']) ? trim($requestHeaders['Authorization']) : 
                       (isset($requestHeaders['authorization']) ? trim($requestHeaders['authorization']) : null);
        }
        return $headers;
    }
    
    
    
    try {
        // $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    $authHeader = getAuthorizationHeader();

    // Validate the token
    $decodedToken = validateToken($authHeader);

    // Access the payload
    $username = $decodedToken['payload']['username'];
    $issuedAt = $decodedToken['payload']['iat'];
    $expirationTime = $decodedToken['payload']['exp'];

    error_log('Username: ' . $username);
    error_log('Issued At: ' . date('Y-m-d H:i:s', $issuedAt));
    error_log('uploadbooks.php Expiration Time: ' . date('Y-m-d H:i:s', $expirationTime));

    if ($expirationTime < time()) {
        // Log an appropriate message or handle it as needed
        error_log("Token has expired");
        http_response_code(401);  // Set HTTP status code to 401 Unauthorized
        echo json_encode([
            "success" => false,
            "message" => "Token has expired",
            "data" => null
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => null
    ]);
    exit;
}

// Set up the response array
$response = ['success' => false, 'message' => ''];

// Specify where to save the uploaded files
$uploadDirectory = __DIR__ . '/Files/';

// Check if the directory exists, if not create it
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

// Check if file is uploaded
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Files' . DIRECTORY_SEPARATOR . basename($file['name']);
    

    // You can add file validation checks here (e.g., file size, type)
    if ($file['size'] > 10000000) { // for example, limit file size to 10MB
        $response['message'] = 'File size is too large. Please upload a smaller file.';
    } else {
        // Move the uploaded file to the uploads directory
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $response['success'] = true;
            $response['message'] = 'File uploaded successfully.';
        } else {
            $response['message'] = 'Failed to upload file.';
        }
    }
} else {
    $response['message'] = 'No file was uploaded.';
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
