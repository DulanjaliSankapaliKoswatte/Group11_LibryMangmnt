<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

require 'validate_token.php';
require 'db_connection.php'; // Database connection

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];

// Function to get the authorization header from the request
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
    $authHeader = getAuthorizationHeader();
    error_log("Authorization Header: " . $authHeader);  // Debug log

    $decodedToken = validateToken($authHeader);
    error_log("Token Decoded: " . print_r($decodedToken, true));  // Debug log

    if ($decodedToken['exp'] < time()) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Token has expired"]);
        exit;
    }

    if ($method === 'GET' && isset($_GET['file'])) {
        $fileName = $_GET['file'];
        error_log("Request for file: " . $fileName);  // Debug log

        $stmt = $pdo->prepare("SELECT file_location FROM Book_Details WHERE book_title = ?");
        $stmt->bindParam(1, $fileName);
        $stmt->execute();
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($file && file_exists($filePath = 'path/to/files/' . $file['file_location'])) {
            http_response_code(200);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            readfile($filePath);
            exit;
        }
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "File not found"]);
    } else if ($method === 'POST') {
        $title = $_POST['title'];
        $isbn = $_POST['isbn'];
        $author = $_POST['author'];
        $year = $_POST['year'];
        $category = $_POST['category'];
        $file = $_FILES['file'];

        error_log("Attempting to upload file: " . $file['name']);  // Debug log

        $uploadPath = 'uploads/' . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception("Failed to upload file.");
        }

        $stmt = $pdo->prepare("INSERT INTO Book_Details (book_title, ISBN, Author_name, Year_made, Category, file_location) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt->execute([$title, $isbn, $author, $year, $category, $uploadPath])) {
            error_log("Error executing SQL: " . implode(";", $stmt->errorInfo()));
            throw new Exception("Error executing SQL: " . $stmt->errorInfo()[2]);
        }

        echo json_encode(["success" => true, "message" => "Book uploaded successfully"]);
    } else {
        // Fetch books data
        $result = $pdo->query("SELECT id, ISBN, Author_name, Year_made, Category, book_title, file_location FROM Book_Details");
        if (!$result) {
            error_log("Error executing SQL: " . implode(";", $pdo->errorInfo()));
            throw new Exception("Error executing SQL: " . $pdo->errorInfo()[2]);
        }
        $books = $result->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $books]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    error_log("Caught Exception: " . $e->getMessage());
}
?>
