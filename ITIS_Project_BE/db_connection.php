<?php
try {
    // Database connection
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=library_managment', 'appuser', 'Abcd@1234');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed.");
}
?>
