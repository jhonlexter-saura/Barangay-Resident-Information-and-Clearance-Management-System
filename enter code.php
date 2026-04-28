<?php

session_start();
require 'Admin/Includes/db.php';

function ensureResetCodeColumn(PDO $pdo) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'reset_code'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_code VARCHAR(6) DEFAULT NULL");
    }
}

ensureResetCodeColumn($pdo);

if (empty($_SESSION['reset_email'])) {
    header('Location: forgot_pass.php');
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if (empty($code)) {
        $_SESSION['error'] = 'Please enter the verification code.';
        header('Location: enter_code.php');
        exit();
    }

    $stmt = $pdo->prepare('SELECT reset_code FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['reset_code'] !== $code) {
        $_SESSION['error'] = 'Invalid verification code.';
        header('Location: enter_code.php');
        exit();
    }

    $_SESSION['code_verified'] = true;
    header('Location: reset_password.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Code</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="New folder/style.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
  <div class="card p-4 shadow login-card">

    <h3 class="text-center mb-3">Verify Code</h3>

    <?php
    if (isset($_SESSION['success'])) {
      echo '<div class="alert alert-success text-center">' . $_SESSION['success'] . '</div>';
      unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
      echo '<div class="alert alert-danger text-center">' . $_SESSION['error'] . '</div>';
      unset($_SESSION['error']);
    }
    ?>

    <form action="enter_code.php" method="post">
      <div class="mb-3">
        <label class="form-label">6-Digit Verification Code</label>
        <input 
          type="text"
          name="code"
          class="form-control number-center"
          placeholder="000000"
          maxlength="6"
          pattern="[0-9]{6}"
          inputmode="numeric"
          required
        >
      </div>

      <button class="btn btn-primary w-100">Verify Code</button>
    </form>

  </div>
</div>

</body>
</html>
