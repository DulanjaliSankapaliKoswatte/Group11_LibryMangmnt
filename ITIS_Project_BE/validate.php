<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// require __DIR__ . '/../vendor/autoload.php'; // Use __DIR__ to ensure the correct path
use \Firebase\JWT\JWT;

$key = "Group11"; // Change this to your secret key
$algorithm = "HS256"; // Specify the algorithm

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the Authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : null;

    if ($authHeader) {
        list($jwt) = sscanf($authHeader, 'Bearer %s');

        if ($jwt) {
            try {
                $decoded = JWT::decode($jwt, $key, array($algorithm));
                // Token is valid
                echo json_encode(array("success" => true, "message" => "Token is valid."));
                exit();
            } catch (Exception $e) {
                // Token is invalid
                echo json_encode(array("success" => false, "message" => "Token is invalid: " . $e->getMessage()));
                exit();
            }
        }
    }

    // No token found or invalid token
    echo json_encode(array("success" => false, "message" => "Authorization header not found or invalid token."));
    exit();
}
?>
