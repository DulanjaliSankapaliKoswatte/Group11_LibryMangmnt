<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

require __DIR__ . '/../../vendor/autoload.php';
require 'validate_token.php';

use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Use the default credential provider
$provider = CredentialProvider::defaultProvider();

// Create an S3 client using the default credentials provider
$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'ap-southeast-2', // Example: 'us-east-1'
    'credentials' => $provider
]);

$bucketName = 'itis-group11-librymanagment2'; // Replace with your actual bucket name

try {
    $authHeader = getAuthorizationHeader();
    $decodedToken = validateToken($authHeader);

    $username = $decodedToken['payload']['username'];
    $issuedAt = $decodedToken['payload']['iat'];
    $expirationTime = $decodedToken['payload']['exp'];

    if ($expirationTime < time()) {
        throw new Exception("Token has expired");
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => null
    ]);
    exit;
}

$response = ['success' => false, 'message' => ''];

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $keyName = '' . basename($file['name']); // S3 Key

    try {
        // Upload the file to the bucket
        $result = $s3Client->putObject([
            'Bucket' => $bucketName,
            'Key'    => $keyName,
            'SourceFile' => $file['tmp_name'],
            'ACL'    => 'public-read' // or 'private' depending on your needs
        ]);

        $response['success'] = true;
        $response['message'] = 'File uploaded successfully to S3.';
        $response['data'] = [
            'objectUrl' => $result['ObjectURL'] // Get the URL of the uploaded object
        ];
    } catch (AwsException $e) {
        $response['message'] = 'Failed to upload file to S3: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'No file was uploaded.';
}

header('Content-Type: application/json');
echo json_encode($response);

function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $headers = isset($requestHeaders['Authorization']) ? trim($requestHeaders['Authorization']) : null;
    }
    return $headers;
}
?>
