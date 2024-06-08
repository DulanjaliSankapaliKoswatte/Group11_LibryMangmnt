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

require 'db_connection.php';  
require 'send_email.php'; 

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
    
    $email = $data['email'] ?? '';
    if (empty($email)) {
        throw new Exception("Email address is required.");
    }

    // Check if email exists in the database
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    if (!$stmt) {
        throw new Exception("Database query failed.");
    }

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Email address not found.");
    }

    // Generate a temporary reset token (you can use a more secure method)
    $resetToken = rand(100000, 999999);

    // Save the reset token in the resetcredentials table, override if email exists
    $stmt = $pdo->prepare('INSERT INTO resetcredentials (email, reset_token, created_at) VALUES (?, ?, NOW())
                          ON DUPLICATE KEY UPDATE reset_token = VALUES(reset_token), created_at = NOW()');
    if (!$stmt) {
        throw new Exception("Failed to prepare the insert statement.");
    }
    $stmt->execute([$email, $resetToken]);

    // Send the reset token to the user's email
    $subject = "Password Reset Request";
    $body = "Your password reset code is: $resetToken";
    if (!sendEmail($email, $subject, $body)) {
        throw new Exception("Failed to send email.");
    }

    $response = [
        "success" => true,
        "message" => "Password reset instructions have been sent to your email."
    ];

    // Delete entries older than 5 minutes
    $stmt = $pdo->prepare('DELETE FROM resetcredentials WHERE created_at < (NOW() - INTERVAL 5 MINUTE)');
    if (!$stmt) {
        throw new Exception("Failed to prepare the delete statement.");
    }
    $stmt->execute();

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
