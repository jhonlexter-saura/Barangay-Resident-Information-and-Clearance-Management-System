<?php

session_start();
require 'config.php';

function ensureResetCodeColumn(PDO $pdo) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM resident LIKE 'reset_code'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE resident ADD COLUMN reset_code VARCHAR(6) DEFAULT NULL");
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
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password === '' || $confirmPassword === '') {
        $_SESSION['error'] = 'Please fill out both password fields.';
        header('Location: reset_password.php');
        exit();
    }

    if ($password !== $confirmPassword) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: reset_password.php');
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE resident SET password = ?, reset_code = NULL WHERE email = ?');
    $stmt->execute([$hashedPassword, $email]);

    unset($_SESSION['reset_email'], $_SESSION['code_verified']);
    $_SESSION['success'] = 'Your password has been reset successfully. You can now log in.';
    header('Location: resident/resident-portal.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/resident-portal.css" rel="stylesheet">
</head>
<body>
  <div class="r-main" style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; background:var(--gray-50);">
    <div class="card shadow-sm" style="width:min(100%,420px); border:none; border-radius:22px; overflow:hidden;">
      <div class="p-4" style="background:var(--white);">
        <div class="mb-4 text-center">
          <div class="mb-3"><i class="bi bi-key-fill" style="font-size:2rem; color:var(--sky);"></i></div>
          <h2 class="mb-2" style="font-weight:700;">Reset Your Password</h2>
          <p class="text-muted" style="font-size:0.95rem;">Set a new password for your resident account.</p>
        </div>

        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger" role="alert"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form action="reset_password.php" method="post">
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter new password" required>
          </div>

          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">Reset Password</button>
        </form>

        <div class="text-center mt-4">
          <a href="resident/resident-portal.php" class="text-decoration-none">Back to Login</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
