<?php

session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']        ?? '');
    $password =      $_POST['hashed_password'] ?? '';

    if (!$username || !$password) {
        $_SESSION['error'] = 'Please enter your username and password.';
        header('Location: staff-portal.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM barangay_official WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['hashed_password'])) {

        session_regenerate_id(true);

        $_SESSION['loggedin'] = true;
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role_position'];

        header('Location: staff-dashboard.php');
        exit();

    } else {
        $_SESSION['error'] = 'Incorrect username or password. Please try again.';
        header('Location: staff-portal.php');
        exit();
    }
}

header('Location: staff-portal.php');
exit();
?>