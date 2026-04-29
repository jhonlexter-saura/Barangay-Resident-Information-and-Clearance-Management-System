<?php

session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['error'] = 'Please enter your email and password.';
        header('Location: resident-portal.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM residents WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true); // prevent session fixation

        $_SESSION['loggedin']  = true;
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['firstname'] = $user['firstname'];

        header('Location: resident-home.php');
        exit();

    } else {
        $_SESSION['error'] = 'Incorrect email or password. Please try again.';
        header('Location: resident-portal.php');
        exit();
    }
}

// Not a POST — send back to login
header('Location: resident-portal.php');
exit();