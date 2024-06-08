<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';
require 'validate_token.php';

use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;

// Use the default credential provider
$provider = CredentialProvider::defaultProvider();
use Aws\Exception\AwsException;

// Log all headers for debugging
$headers = getallheaders();
error_log("Received headers: " . json_encode($headers));

// Create an S3 client
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'global',
    'credentials' => $provider
]);

$bucketName = 'itis-group11_librymanagment';

function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // Sometimes used depending on server configuration
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } else if (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server might capitalize all header keys
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}



try {
    // $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    $authHeader = getAuthorizationHeader();
    $decodedToken = validateToken($authHeader);
    $username = $decodedToken['payload']['username'];
    $issuedAt = $decodedToken['payload']['iat'];
    $expirationTime = $decodedToken['payload']['exp'];

    if ($expirationTime < time()) {
        throw new Exception("Token has expired");
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage(), "data" => null]);
    exit;
}

if (isset($_GET['file'])) {
    $fileName = $_GET['file'];
    try {
        // Get the object from S3
        $result = $s3->getObject([
            'Bucket' => $bucketName,
            'Key'    => $fileName
        ]);
        // Direct download link or file content
        $fileContent = $result['Body']->getContents();
        echo json_encode([
            "success" => true,
            "message" => "File content retrieved successfully.",
            "data" => base64_encode($fileContent)
        ]);
    } catch (AwsException $e) {
        error_log($e->getMessage());
        echo json_encode(["success" => false, "message" => "File not found.", "data" => null]);
    }
    exit;
}

try {
    // List objects in a bucket
    $objects = $s3->listObjects([
        'Bucket' => $bucketName
    ]);
    $fileList = [];
    foreach ($objects['Contents'] as $object) {
        $fileList[] = [
            "name" => $object['Key'],
            "url" => $s3->getObjectUrl($bucketName, $object['Key'])
        ];
    }
    echo json_encode(["success" => true, "message" => "Files listed successfully.", "data" => $fileList]);
} catch (AwsException $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "Failed to list files.", "data" => null]);
}
?>
