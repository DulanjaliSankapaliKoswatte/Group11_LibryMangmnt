<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';
require 'validate_token.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// AWS S3 Configuration
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1' // Change to your bucket's region
]);

$bucketName = 'itis-group11_librymanagment';

try {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
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
        echo json_encode([
            "success" => true,
            "message" => "File content retrieved successfully.",
            "data" => base64_encode($result['Body']->getContents())
        ]);
    } catch (AwsException $e) {
        error_log($e->getMessage());
        echo json_encode(["success" => false, "message" => "Failed to retrieve file.", "data" => null]);
    }
    exit;
}

// Listing files from S3 bucket
try {
    $objects = $s3->listObjects([
        'Bucket' => $bucketName
    ]);
    $fileList = [];
    foreach ($objects['Contents'] as $object) {
        $fileList[] = [
            "name" => $object['Key'],
            "url" => "https://{$bucketName}.s3.amazonaws.com/{$object['Key']}"
        ];
    }
    echo json_encode(["success" => true, "message" => "Files listed successfully.", "data" => $fileList]);
} catch (AwsException $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "Failed to list files.", "data" => null]);
}
?>
