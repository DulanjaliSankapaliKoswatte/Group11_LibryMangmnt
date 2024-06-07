<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'validate_token.php';

try {
    // Fetch Authorization header
    $authHeader = null;

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    // Validate the token
    $decodedToken = validateToken($authHeader);

    // Access the payload
    $username = $decodedToken['payload']['username']; // Assuming the token has a 'username' claim
    $issuedAt = $decodedToken['payload']['iat'];
    $expirationTime = $decodedToken['payload']['exp'];

    error_log('Username: ' . $username);
    error_log('Issued At: ' . date('Y-m-d H:i:s', $issuedAt));
    error_log('Expiration Time: ' . date('Y-m-d H:i:s', $expirationTime));

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

// Check if a specific file is requested
if (isset($_GET['file'])) {
    $fileName = $_GET['file'];
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Files' . DIRECTORY_SEPARATOR . $fileName;

    if (!file_exists($filePath)) {
        error_log("File not found: " . $filePath);
        echo json_encode([
            "success" => false,
            "message" => "File not found.",
            "data" => null
        ]);
        exit;
    }

    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        error_log("Failed to read file: " . $filePath);
        echo json_encode([
            "success" => false,
            "message" => "Failed to read file.",
            "data" => null
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "File content retrieved successfully.",
        "data" => base64_encode($fileContent)
    ]);
    exit;
}

// Base URL of your project
$baseUrl = 'https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/Files/';

// Directory path
$directoryPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Files';

// Check if directory exists
if (!is_dir($directoryPath)) {
    error_log("Directory not found: " . $directoryPath);
    echo json_encode([
        "success" => false,
        "message" => "Directory not found.",
        "data" => null
    ]);
    exit;
}

// Get list of files from the directory
$files = scandir($directoryPath);
if ($files === false) {
    error_log("Failed to read directory: " . $directoryPath);
    echo json_encode([
        "success" => false,
        "message" => "Failed to read directory.",
        "data" => null
    ]);
    exit;
}

$fileList = array();

foreach ($files as $file) {
    // Skip the current and parent directory entries
    if ($file !== '.' && $file !== '..') {
        $fileList[] = array(
            "name" => $file,
            "url" => $baseUrl . $file
        );
    }
}

// Return the file list as JSON
echo json_encode([
    "success" => true,
    "message" => "Files listed successfully.",
    "data" => $fileList
]);
?>