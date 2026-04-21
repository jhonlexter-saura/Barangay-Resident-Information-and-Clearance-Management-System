<?php

session_start();

require 'bricms_db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password, $email['password'])) {
        
        $_SESSION["loggedin"] = true;
        $_SESSION["id"] = $user["id"];
        $_SESSION["email"] = $user["email"];
         $_SESSION["name"]      = $user['firstname'] . ' ' . $user['lastname'];

        header('Location: admin/dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = "Invalid Email and Password";
        header('Location: login.php');
        exit();
    }
}