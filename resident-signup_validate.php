<?php

session_start();

require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstname = $_POST["firstname"];
    $lasttname = $_POST["lastname"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    if ($password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match";
        header('Location: signup.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt-> rowCount() > 0) {
         $_SESSION['error'] = "Email already exists";
        header('Location: signup.php');
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users(firstname, lastname, email, password) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$firstname,$lasttname,$email,$hashedPassword])) {
        $_SESSION['success'] = "Your account has been created. You can now login";
        header('Location: login.php');
        exit();
    } else {
        echo("There's an error");
        exit();
    }
}