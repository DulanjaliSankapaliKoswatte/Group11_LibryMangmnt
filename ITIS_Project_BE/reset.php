<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any unwanted output
ob_start();

require 'db_connection.php'; // Ensure you have a file to handle DB connection

try {
    // Check database connection
    if (!$pdo) {
        throw new Exception("Database connection failed.");
    }

    // Read the request body
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input.");
    }
    
    $resetCode = $data['reset_code'] ?? '';
    $newPassword = $data['new_password'] ?? '';
 
    if (empty($resetCode) || empty($newPassword)) {
        throw new Exception("Reset code and new password are required.");
    }

    // Check if the reset token exists in the resetcredentials table
    $stmt = $pdo->prepare('SELECT * FROM resetcredentials WHERE reset_token = ?');
    if (!$stmt) {
        throw new Exception("Database query failed.");
    }

    $stmt->execute([$resetCode]);
    $resetEntry = $stmt->fetch();

    if (!$resetEntry) {
        throw new Exception("Invalid reset token.");
    }
    // Decrypt the password
    error_log("newPassword Password in Reset.php: " . $newPassword);
    $password = base64_decode($newPassword);
    // Log the decrypted password
    error_log("Decrypted Password in Reset.php: " . $password);
    // Database connection

    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Update the user's password in the users table
    $email = $resetEntry['email'];
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    if (!$stmt) {
        throw new Exception("Failed to prepare the update statement.");
    }
    $stmt->execute([$hashedPassword, $email]);

    // Remove the reset token from the resetcredentials table
    $stmt = $pdo->prepare('DELETE FROM resetcredentials WHERE reset_token = ?');
    if (!$stmt) {
        throw new Exception("Failed to prepare the delete statement.");
    }
    $stmt->execute([$resetCode]);

    $response = [
        "success" => true,
        "message" => "Your password has been reset successfully."
    ];
} catch (Exception $e) {
    $response = [
        "success" => false,
        "message" => $e->getMessage()
    ];
}

// Clean (erase) the output buffer and turn off output buffering
ob_end_clean();

// Output the JSON response
echo json_encode($response);
?>
