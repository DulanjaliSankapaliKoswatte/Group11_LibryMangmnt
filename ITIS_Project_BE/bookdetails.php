<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET");

require 'validate_token.php';
require 'db_connection.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Fetch and validate the token
    $authHeader = getAuthorizationHeader();
    $decodedToken = validateToken($authHeader);
    // if ($decodedToken['exp'] < time()) {
    //     http_response_code(401);
    //     echo json_encode(["success" => false, "message" => "Token has expired"]);
    //     exit;
    // }

    if ($method === 'POST') {
        // Assuming data is sent via POST as application/x-www-form-urlencoded
        $title = $_POST['title'] ?? '';
        $isbn = $_POST['isbn'] ?? '';
        $author = $_POST['author'] ?? '';
        $year = $_POST['year'] ?? '';
        $category = $_POST['category'] ?? '';

        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO Book_Details (book_title, ISBN, Author_name, Year_made, Category) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $isbn, $author, $year, $category]);
        echo json_encode(["success" => true, "message" => "Book details saved successfully"]);
    } else if ($method === 'GET') {
        // Fetch all books or specific book if ID is provided
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM Book_Details WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->query("SELECT * FROM Book_Details");
        }
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $books]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    error_log("Caught Exception: " . $e->getMessage());
}

// Helper function to get the authorization header
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
