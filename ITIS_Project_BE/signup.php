<?php
require 'db_connection.php';
require 'send_email.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

function returnError($message) {
    echo json_encode(array("success" => false, "message" => $message));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    // Decode the JSON data
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        returnError("Invalid JSON input: " . json_last_error_msg());
    }

    // Extract the data
    $encryptedUsername = $data['username'] ?? null;
    $email = $data['email'] ?? null;
    $encryptedPassword = $data['password'] ?? null;

    if (!$encryptedUsername || !$email || !$encryptedPassword) {
        returnError("Required fields are missing.");
    }

    // Decrypt the password
    $password = base64_decode($encryptedPassword);
    $username = base64_decode($encryptedUsername);

    if ($password === false) {
        returnError("Failed to decrypt password.");
    }

    // Log the decrypted password
    error_log("Decrypted Password: " . $password);
    
    // Hash the decrypted password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(16)); // Generate a random token

    try {
        // Check if the email already exists
        $stmt1 = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        if (!$stmt1) {
            throw new Exception("Prepare failed: " . $pdo->errorInfo()[2]);
        }
        $stmt1->execute([$username]);
        if ($stmt1->rowCount() > 0) {
            returnError("Username already exists.");
        }
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $pdo->errorInfo()[2]);
        }
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            returnError("Email already exists.");
        } else {
            // Insert the new user into the database with token, inactive status, and user role
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, token, active, userrole) VALUES (?, ?, ?, ?, 0, 'user')");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $pdo->errorInfo()[2]);
            }
            $stmt->execute([$username, $email, $hashedPassword, $token]);

            // Send verification email using sendEmail function
            $subject = 'Verify Your Email Address';
            $body = 'Please click the link below to verify your email address:<br><br>' . '<a href="https://itis-group11.com/Group11_LibryMangmnt/ITIS_Project_BE/verify_email.php?token=' . $token . '">Verify Email</a>';
            if (sendEmail($email, $subject, $body)) {
                echo json_encode(array("success" => true, "message" => "Registration successful! Please check your email to verify your account."));
            } else {
                returnError("Failed to send verification email.");
            }
        }
    } catch (Exception $e) {
        returnError("Error: " . $e->getMessage());
    }
}
?>
