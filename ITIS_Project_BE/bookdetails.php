<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require 'validate_token.php';
require 'db_connection.php'; // Include the database connection file

$method = $_SERVER['REQUEST_METHOD'];
$fileName = $_GET['file'] ?? null; // Check if a filename is provided in the URL

try {
    // Validate JWT
    $decodedToken = validateToken($_SERVER['HTTP_AUTHORIZATION']);
    if ($decodedToken['exp'] < time()) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Token has expired"]);
        exit;
    }

    if ($method === 'GET' && $fileName) {
        // Handle file download
        $stmt = $pdo->prepare("SELECT file_location FROM Book_Details WHERE book_title = ?");
        $stmt->bind_param("s", $fileName);
        $stmt->execute();
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($file) {
            $filePath = 'path/to/files/' . $file['file_location']; // Set your files path correctly
            if (file_exists($filePath)) {
                http_response_code(200);
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                readfile($filePath);
                exit;
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "File not found"]);
                exit;
            }
        }
    } else if ($method === 'POST') {
        $title = $_POST['title'];
        $isbn = $_POST['isbn'];
        $author = $_POST['author'];
        $year = $_POST['year'];
        $category = $_POST['category'];
        $file = $_FILES['file'];

        // File upload path
        $uploadPath = 'uploads/' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $uploadPath);

        // Insert book into database
        $stmt = $pdo->prepare("INSERT INTO Book_Details (book_title, ISBN, Author_name, Year_made, Category, file_location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $isbn, $author, $year, $category, $uploadPath]);

        echo json_encode(["success" => true, "message" => "Book uploaded successfully"]);
    }else {
        // Fetch books data
        $query = "SELECT id, ISBN, Author_name, Year_made, Category, book_title, file_location FROM Book_Details";
        $result = $pdo->query($query);
        $books = $result->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $books]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
