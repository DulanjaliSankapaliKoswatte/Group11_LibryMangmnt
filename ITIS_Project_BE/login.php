<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// require __DIR__ . '/../vendor/autoload.php'; // Use __DIR__ to ensure the correct path
use \Firebase\JWT\JWT;

$key = "Group11"; // Change this to your secret key
$ttl = 3600; // Time-To-Live for the token (1 hour)
$refreshttl = 129600;
$algorithm = "HS256"; // Specify the algorithm
//6LcljfEpAAAAAFo1fJnU2CGqCLmQwwdzxPDzkxUY
$recaptchaSecret= "6LdJjPMpAAAAAEsgeZ8w0PyB2hLf7OmyTXJ_yy6k";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    // Decode the JSON data
    $data = json_decode($json, true);

    // Extract the data
    $encryptedUsername = $data['username'];
    $encryptedPassword = $data['password'];
    $recaptchaResponse = $data['g-recaptcha-response'];

    //verify reCAPTCHA
    $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptchaData = array(
        'secret' => $recaptchaSecret,
        'response' => $recaptchaResponse
    );
    $options = array(
        'http' => array(
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($recaptchaData)
        )
    );
 
    $recaptchaContext = stream_context_create($options);
    $recaptchaResult = file_get_contents($recaptchaUrl, false, $recaptchaContext);
    $recaptchaJson = json_decode($recaptchaResult, true);
    $conn = new mysqli('127.0.0.1', 'appuser', 'Abcd@1234', 'library_managment');
    if (!$recaptchaJson['success']) {
        echo json_encode(array("success" => false, "message" => "reCAPTCHA verification failed." . $conn->connect_error));
        exit();
    }
    
    // Decrypt the password
    $password = base64_decode($encryptedPassword);
    $username = base64_decode($encryptedUsername);
    // Log the decrypted password
    error_log("Decrypted Password: " . $password);
    // Database connection
 
    if ($conn->connect_error) {
        echo json_encode(array("success" => false, "message" => "Connection failed: " . $conn->connect_error));
        exit();
    }

    
    // Check if the user exists
    $stmt = $conn->prepare("SELECT id, password, userrole FROM users WHERE username = ? and active='0'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Generate JWT token
            $issuedAt = time();
            $expirationTime = $issuedAt + $ttl;
            $payload = array(
                'iat' => $issuedAt,
                'exp' => $expirationTime,
                'username' => $username,
                'userrole' => $row['userrole'] // Include user role in the payload
            );
            $jwt = JWT::encode($payload, $key, $algorithm);

            $refreshExpirationTime = $issuedAt + $refreshttl;
            $refreshPayload = array(
                'iat' => $issuedAt,
                'exp' => $refreshExpirationTime,
                'username' => $username  
            );
            $refreshJwt = JWT::encode($refreshPayload, $key, $algorithm);

            $expiresAt = $issuedAt + $refreshttl;
            $updateStmt = $conn->prepare("REPLACE INTO token_storage (username, refresh_token, expires_at) VALUES (?, ?, ?)");
            $updateStmt->bind_param("ssi", $username, $refreshJwt, $expiresAt);
            $updateStmt->execute();
               

            // Set cookie with token
            setcookie("token", $jwt, $expirationTime, "/", "", false, true);

            // Set role in response header
            $role = $row['userrole'];
         
            header("X-User-Role: $role");
           

            echo json_encode(array("success" => true, "message" => "Login successful! Redirecting to library page...", "userrole" => $role, "username" => $username,"token" => $jwt));
        } else {
            echo json_encode(array("success" => false, "message" => "Invalid password."));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Invalid username."));
    }

    $stmt->close();
    $conn->close();
}
?>
