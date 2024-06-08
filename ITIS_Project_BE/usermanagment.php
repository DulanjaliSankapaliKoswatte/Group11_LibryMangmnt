<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Enable error reporting for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require 'db_connection.php';  

require 'validate_token.php';
function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Check for both normal and lowercase.
        $headers = isset($requestHeaders['Authorization']) ? trim($requestHeaders['Authorization']) : 
                    (isset($requestHeaders['authorization']) ? trim($requestHeaders['authorization']) : null);
    }
    return $headers;
}

try {

    $authHeader = null;
    $authHeader = getAuthorizationHeader();
    // Fetch Authorization header
   

    // Validate the token
    $decodedToken = validateToken($authHeader);

    // Access the payload
    $username = $decodedToken['payload']['username'];
    $issuedAt = $decodedToken['payload']['iat'];
    $expirationTime = $decodedToken['payload']['exp'];

    error_log('Username: ' . $username);
    error_log('Issued At: ' . date('Y-m-d H:i:s', $issuedAt));
    error_log('Expiration Time: ' . date('Y-m-d H:i:s', $expirationTime));

    if ($expirationTime < time()) {
       
        error_log("Token has expired");
        http_response_code(401);  // Set HTTP status code to 401 Unauthorized
        echo json_encode([
            "success" => false,
            "message" => "Token has expired",
            "data" => null
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => null
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? $_GET['id'] : null;
$toggle = isset($_GET['toggle']) ? $_GET['toggle'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$role = isset($_GET['role']) ? $_GET['role'] : null;
$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => ''];

// $conn = new mysqli('127.0.0.1', 'appuser', 'Abcd@1234', 'library_managment');

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

switch ($method) {
    case 'GET':
        $result = $pdo->query("SELECT * FROM users");
        $users = $result->fetchall(MYSQLI_ASSOC);
        echo json_encode($users);
        break;
    case 'POST':
        if ($id && $input) {
            // Updating user details
            $userrole = $input['userrole'];
            $stmt = $conn->prepare("UPDATE users SET userrole = ? WHERE id = ?");
            $stmt->bind_param("si", $userrole, $id);
            $response['success'] = $stmt->execute();
            $stmt->close();
        } elseif ($id && $toggle) {
            // Toggling status or role
            if ($toggle === 'status' && $status !== null) {
                $stmt = $conn->prepare("UPDATE users SET active = ? WHERE id = ?");
                $stmt->bind_param("ii", $status, $id);
            } elseif ($toggle === 'role' && $role) {
                $stmt = $conn->prepare("UPDATE users SET userrole = ? WHERE id = ?");
                $stmt->bind_param("si", $role, $id);
            }
            $response['success'] = $stmt->execute();
            $stmt->close();
        }
        echo json_encode($response);
        break;
    case 'DELETE':
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $response['success'] = $stmt->execute();
            $stmt->close();
        }
        echo json_encode($response);
        break;
}

$conn->close();
?>
