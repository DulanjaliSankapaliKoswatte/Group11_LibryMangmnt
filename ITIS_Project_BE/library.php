<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

require __DIR__ . '/../../vendor/autoload.php';
require 'validate_token.php';

use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Set up the AWS S3 client
$provider = CredentialProvider::defaultProvider();
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'ap-southeast-1', // Make sure the region is correct
    'credentials' => $provider
]);

$bucketName = 'itis-group11-librymanagment2';

// Helper function to get the authorization header
function getAuthorizationHeader() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        return $requestHeaders['Authorization'] ?? $requestHeaders['authorization'] ?? null;
    }
    return null;
}

try {
    $authHeader = getAuthorizationHeader();
    $decodedToken = validateToken($authHeader);
    if ($decodedToken['payload']['exp'] < time()) {
        throw new Exception("Token has expired");
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => $e->getMessage(), "data" => null]);
    exit;
}

// Determine action based on the presence of 'file' parameter
if (isset($_GET['file'])) {
    $fileName = $_GET['file'];
    try {
        $result = $s3->getObject([
            'Bucket' => $bucketName,
            'Key'    => $fileName
        ]);
        echo json_encode([
            "success" => true,
            "message" => "File retrieved successfully.",
            "data" => base64_encode($result['Body']->getContents())
        ]);
    } catch (AwsException $e) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "File not found: " . $e->getMessage(), "data" => null]);
    }
} else {
    try {
        $result = $s3->listObjects([
            'Bucket' => $bucketName
        ]);
        $fileList = array_map(function ($object) use ($s3, $bucketName) {
            return [
                "name" => $object['Key'],
                "url" => $s3->getObjectUrl($bucketName, $object['Key'])
            ];
        }, $result['Contents'] ?? []);

        echo json_encode(["success" => true, "message" => "Files listed successfully.", "data" => $fileList]);
    } catch (AwsException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to list files: " . $e->getMessage(), "data" => null]);
    }
}
?>
