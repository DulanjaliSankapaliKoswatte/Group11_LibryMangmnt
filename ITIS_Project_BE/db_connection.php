<?php
try {
    // Database connection
    $pdo = new PDO('mysql:host=localhost;dbname=library_managment', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed.");
}
?>
