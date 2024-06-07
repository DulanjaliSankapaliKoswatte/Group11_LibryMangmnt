<?php
function base64UrlDecode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
}

function decodeJWT($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        throw new Exception("Invalid token format.");
    }
    
    $header = json_decode(base64UrlDecode($parts[0]), true);
    $payload = json_decode(base64UrlDecode($parts[1]), true);
    $signature = base64UrlDecode($parts[2]);

    return array('header' => $header, 'payload' => $payload, 'signature' => $signature);
}

function validateToken($authHeader) {
    if (!$authHeader) {
        throw new Exception("Authorization header not found.");
    }

    // Remove "Bearer " part from the token
    $token = str_replace('Bearer ', '', $authHeader);
    error_log("Token received: " . $token);

    try {
        $decodedToken = decodeJWT($token);
        error_log('Decoded token: ' . print_r($decodedToken, true));
        return $decodedToken;
    } catch (Exception $e) {
        throw new Exception("Invalid token. Error: " . $e->getMessage());
    }
}
?>
