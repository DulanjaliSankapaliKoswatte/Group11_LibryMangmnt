<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'library_managment');
    if ($conn->connect_error) {
        echo json_encode(array("success" => false, "message" => "Connection failed: " . $conn->connect_error));
        exit();
    }

    // Check if the token is valid
    $stmt = $conn->prepare("SELECT * FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // Activate the user account
        $stmt = $conn->prepare("UPDATE users SET active = 1, token = NULL WHERE token = ?");
        $stmt->bind_param("s", $token);
        if ($stmt->execute()) {
            echo json_encode(array("success" => true, "message" => "Email verified successfully! You can now log in."));
        } else {
            echo json_encode(array("success" => false, "message" => "Error: " . $stmt->error));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Invalid or expired token."));
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(array("success" => false, "message" => "No token provided."));
}
?>
