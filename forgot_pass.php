<?php

session_start();
require 'config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function ensureResetCodeColumn(PDO $pdo) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM resident LIKE 'reset_code'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE resident ADD COLUMN reset_code VARCHAR(6) DEFAULT NULL");
    }
}

ensureResetCodeColumn($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $_SESSION['error'] = 'Please enter your email address.';
        header('Location: forgot_pass.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT resident_id FROM resident WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'No resident account found with that email address.';
        header('Location: forgot_pass.php');
        exit();
    }

    $resetCode = strval(rand(100000, 999999));
    $update = $pdo->prepare("UPDATE resident SET reset_code = ? WHERE email = ?");
    $update->execute([$resetCode, $email]);

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '2401104830@student.buksu.edu.ph';
        $mail->Password   = 'pibh arsg rodz uguy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('2401104830@student.buksu.edu.ph', 'KALASUNGAY Support');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'KALASUNGAY Password Reset Code';
        $mail->Body    = "<p>Your password reset code is <strong>{$resetCode}</strong>.</p>";
        $mail->AltBody = "Your password reset code is: {$resetCode}";

        $mail->send();

        $_SESSION['reset_email'] = $email;
        $_SESSION['success']     = 'A 6-digit verification code has been sent to your email address.';
        header('Location: enter_code.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Unable to send reset email. Please try again later.';
        header('Location: forgot_pass.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/resident-portal.css" rel="stylesheet">
</head>
<body>
  <div class="r-main" style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; background:var(--gray-50);">
    <div class="card shadow-sm" style="width:min(100%,420px); border:none; border-radius:22px; overflow:hidden;">
      <div class="p-4" style="background:var(--white);">
        <div class="mb-4 text-center">
          <div class="mb-3"><i class="bi bi-shield-lock-fill" style="font-size:2rem; color:var(--sky);"></i></div>
          <h2 class="mb-2" style="font-weight:700;">Forgot Password</h2>
          <p class="text-muted" style="font-size:0.95rem;">Enter your registered email address and we will send you a verification code.</p>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
          <div class="alert alert-success" role="alert"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger" role="alert"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form action="forgot_pass.php" method="post">
          <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="you@email.com" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">Send Verification Code</button>
        </form>

        <div class="text-center mt-4" style="font-size:0.92rem;">
          <a href="resident/resident-portal.php" class="text-decoration-none">Back to Login</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
