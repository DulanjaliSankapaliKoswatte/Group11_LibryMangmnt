<?php
require __DIR__ . '/../../vendor/autoload.php'; // Use __DIR__ to ensure the correct path
use \Firebase\JWT\JWT;

function validateToken($authHeader) {
    $key = "Group11"; // Change this to your secret key
    $algorithm = "HS256"; // Specify the algorithm

    if ($authHeader) {
        list($jwt) = sscanf($authHeader, 'Bearer %s');

        if ($jwt) {
            try {
                $decoded = JWT::decode($jwt, $key, array($algorithm));
                // Token is valid, return decoded token
                return (array) $decoded;
            } catch (Exception $e) {
                throw new Exception("Token is invalid: " . $e->getMessage());
            }
        }
    }

    throw new Exception("Authorization header not found or invalid token.");
}
?>
