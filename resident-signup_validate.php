<?php

session_start();

require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstname = trim($_POST["firstname"] ?? '');
    $lastname  = trim($_POST["lastname"]  ?? '');   // fixed: was $lasttname
    $email     = trim($_POST["email"]     ?? '');
    $password  =      $_POST["password"]  ?? '';
    $confirm   =      $_POST["confirm_password"] ?? '';

    // ── Server-side validation (mirrors JS checks) ──────────────────────────

    if (!$firstname || !$lastname || !$email || !$password || !$confirm) {
        $_SESSION['error'] = "All fields are required.";
        header('Location: resident-signup.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header('Location: resident-signup.php');
        exit();
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters.";
        header('Location: resident-signup.php');
        exit();
    }

    if ($password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: resident-signup.php');
        exit();
    }

    // ── Check for existing email ─────────────────────────────────────────────

    $stmt = $pdo->prepare("SELECT resident_id FROM resident WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "An account with that email already exists.";
        header('Location: resident-signup.php');
        exit();
    }

    // ── Insert new user ──────────────────────────────────────────────────────

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO resident (first_name, last_name, email, password) VALUES (?, ?, ?, ?)"
    );

    if ($stmt->execute([$firstname, $lastname, $email, $hashedPassword])) {
        $_SESSION['success'] = "Your account has been created. You can now sign in.";
        header('Location: resident-portal.php');
        exit();
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header('Location: resident-signup.php');
        exit();
    }

} else {
    // Not a POST request — send back to the form
    header('Location: resident-signup.php');
    exit();
}