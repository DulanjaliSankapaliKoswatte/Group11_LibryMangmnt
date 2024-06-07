<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// require __DIR__ . '/../vendor/autoload.php'; // Ensure your autoload path is correct
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$key = "Group11"; // Secret key for JWT
$ttl = 3600; // Time-To-Live for the access token (1 hour)
$algorithm = "HS256"; // Algorithm used for token

function getNewAccessTokenUsingExpiredToken($expiredToken, $key, $ttl, $algorithm) {
    // $token = str_replace('Bearer ', '', $expiredToken); // Remove 'Bearer ' prefix
    // error_log("Attempting to decode token: $token");

    try {
        $token = str_replace('Bearer ', '', $expiredToken); // Remove 'Bearer ' prefix
        error_log("Attempting to decode token: $token");

        // Decode the token manually to extract the payload without verifying its expiration
        $tks = explode('.', $token);
        if (count($tks) != 3) {
            return json_encode(["success" => false, "message" => "Invalid token"]);
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
        $username = $payload->username; // Extract 'username'

        $conn = new mysqli('172.31.36.218', 'root', '', 'library_managment');
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
        }

        $stmt = $conn->prepare("SELECT refresh_token, expires_at FROM token_storage WHERE username = ?");
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $conn->error);
            return json_encode(["success" => false, "message" => "Failed to prepare statement: " . $conn->error]);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            error_log("No valid session found for username: $username");
            $stmt->close();
            $conn->close();
            return json_encode(["success" => false, "message" => "No valid session found, please log in."]);
        }

        $token_data = $result->fetch_assoc();
        if (time() >= $token_data['expires_at']) {
            error_log("Refresh token has expired for username: $username");
            $stmt->close();
            $conn->close();
            return json_encode(["success" => false, "message" => "Refresh token has expired, please log in again."]);
        }

        // Refresh token is valid, generate a new access token
        $issuedAt = time();
        $newAccessToken = JWT::encode([
            'iat' => $issuedAt,
            'exp' => $issuedAt + $ttl,
            'username' => $username
        ], $key, $algorithm);

        error_log("New access token generated for username: $username");
        $stmt->close();
        $conn->close();
        return json_encode([
            "success" => true,
            "token" => $newAccessToken
        ]);

    } catch (Exception $e) {
        error_log("Token validation failed: " . $e->getMessage());
        return json_encode(["success" => false, "message" => "Token validation failed: " . $e->getMessage()]);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? '';
    error_log("Received Authorization header: $authHeader");
    echo getNewAccessTokenUsingExpiredToken($authHeader, $key, $ttl, $algorithm);
}
