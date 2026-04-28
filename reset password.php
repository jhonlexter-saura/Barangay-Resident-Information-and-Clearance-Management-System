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

if (empty($_SESSION['reset_email']) || empty($_SESSION['code_verified'])) {
    header('Location: forgot_pass.php');
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = 'Please fill out both password fields.';
        header('Location: reset_password.php');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: reset_password.php');
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = ?, reset_code = NULL WHERE email = ?');
    $stmt->execute([$hashedPassword, $email]);

    unset($_SESSION['reset_email'], $_SESSION['code_verified']);
    $_SESSION['success'] = 'Your password has been reset successfully. You can now log in.';
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="New folder/style.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
  <div class="card p-4 shadow login-card">

    <h3 class="text-center mb-3">Reset Password</h3>

    <?php
    if (isset($_SESSION['error'])) {
      echo '<div class="alert alert-danger text-center">' . $_SESSION['error'] . '</div>';
      unset($_SESSION['error']);
    }
    ?>

    <form action="reset_password.php" method="post">
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" class="form-control" name="password" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" class="form-control" name="confirm_password" required>
      </div>

      <button class="btn btn-success w-100">Reset Password</button>
    </form>

  </div>
</div>

</body>
</html>
