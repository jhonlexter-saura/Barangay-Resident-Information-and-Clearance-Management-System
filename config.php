<?php

$host     = "localhost";
$dbname   = "bricms_db";
$username = "root";
$password = "";

// reCAPTCHA Configuration
// Get your keys from: https://www.google.com/recaptcha/admin
$recaptcha_site_key   = "6LedQNssAAAAAKj9DK8CPN2cfse4dcl7b1Tat1xJ";   // Replace with your actual site key
$recaptcha_secret_key = "6LedQNssAAAAAHzZordQrUznYDP_IwcR8XZfMKBe"; // Replace with your actual secret key

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}