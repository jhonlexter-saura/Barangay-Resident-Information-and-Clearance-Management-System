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

if (empty($_SESSION['reset_email'])) {
    header('Location: forgot_pass.php');
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if ($code === '') {
        $_SESSION['error'] = 'Please enter the verification code.';
        header('Location: enter_code.php');
        exit();
    }

    $stmt = $pdo->prepare('SELECT reset_code FROM resident WHERE email = ?');
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Verify Code</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/resident-portal.css" rel="stylesheet">
</head>
<body>
  <div class="r-main" style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; background:var(--gray-50);">
    <div class="card shadow-sm" style="width:min(100%,420px); border:none; border-radius:22px; overflow:hidden;">
      <div class="p-4" style="background:var(--white);">
        <div class="mb-4 text-center">
          <div class="mb-3"><i class="bi bi-shield-check"></i></div>
          <h2 class="mb-2" style="font-weight:700;">Verify Your Code</h2>
          <p class="text-muted" style="font-size:0.95rem;">Enter the 6-digit code sent to your registered email.</p>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
          <div class="alert alert-success" role="alert"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger" role="alert"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form action="enter_code.php" method="post">
          <div class="mb-3">
            <label for="code" class="form-label">6-Digit Verification Code</label>
            <input type="text" id="code" name="code" class="form-control" placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">Verify Code</button>
        </form>

        <div class="text-center mt-4">
          <a href="forgot_pass.php" class="text-decoration-none">Resend code</a> ·
          <a href="resident/resident-portal.php" class="text-decoration-none">Back to Login</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
