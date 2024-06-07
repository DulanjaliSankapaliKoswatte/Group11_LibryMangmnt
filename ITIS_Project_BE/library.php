<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php'; // Ensure the AWS SDK is loaded
require 'validate_token.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Create a S3Client
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
            // Optionally, you can create a pre-signed URL for secure, temporary access to files
            "url" => $s3->getObjectUrl($bucketName, $object['Key'])
        ];
    }
    echo json_encode(["success" => true, "message" => "Files listed successfully.", "data" => $fileList]);
} catch (AwsException $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "Failed to list files.", "data" => null]);
}
?>
