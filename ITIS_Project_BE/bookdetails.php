<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require 'validate_token.php';
require 'db_connection.php'; // Database connection

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];

try {
    $decodedToken = validateToken($_SERVER['HTTP_AUTHORIZATION']);
    if ($decodedToken['exp'] < time()) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Token has expired"]);
        exit;
    }

    if ($method === 'GET' && isset($_GET['file'])) {
        $fileName = $_GET['file'];
        // Handle file download
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
        
        $uploadPath = 'uploads/' . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception("Failed to upload file.");
        }

        $stmt = $pdo->prepare("INSERT INTO Book_Details (book_title, ISBN, Author_name, Year_made, Category, file_location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $isbn, $author, $year, $category, $uploadPath]);

        echo json_encode(["success" => true, "message" => "Book uploaded successfully"]);
    } else {
        // Fetch books data
        $result = $pdo->query("SELECT id, ISBN, Author_name, Year_made, Category, book_title, file_location FROM Book_Details");
        $books = $result->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $books]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
