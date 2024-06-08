<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET");

require 'validate_token.php';
require 'db_connection.php';

// ini_set('display_errors', 0); // Turn off error reporting in production
// error_reporting(0);

$method = $_SERVER['REQUEST_METHOD'];

try {
    $authHeader = getAuthorizationHeader();
    $decodedToken = validateToken($authHeader);
    
    if ($method === 'POST') {
        $title = $_POST['title'] ?? '';
        $isbn = $_POST['isbn'] ?? '';
        $author = $_POST['author'] ?? '';
        $year = $_POST['year'] ?? '';
        $category = $_POST['category'] ?? '';
        $filename = $_POST['filename'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO Book_Details (book_title, ISBN, Author_name, Year_made, Category, file_location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $isbn, $author, $year, $category, $filename]);
        echo json_encode(["success" => true, "message" => "Book details saved successfully"]);
    } else if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM Book_Details");
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $books]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $headers = $requestHeaders['Authorization'] ?? $requestHeaders['authorization'] ?? null;
        return trim($headers);
    }
    return $headers;
}
?>
