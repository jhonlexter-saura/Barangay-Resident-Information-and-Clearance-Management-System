<?php

session_start();

require 'Admin/Includes/db.php';

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        $reset_code = rand(100000, 999999);

        $update = $pdo->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
        $update->execute([$reset_code, $email]);

        $_SESSION['reset_email'] = $email;

       $mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = '2401104830@student.buksu.edu.ph';
    $mail->Password = 'pibh arsg rodz uguy';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('2401104830@student.buksu.edu.ph', 'Saturnino Telamo Jr');
    $mail->addAddress($email, 'This is your Client');
    
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Code';
    $mail->Body = "Your reset code is <b>$reset_code</b>";

    $mail->AltBody = "Hello, Use this code below to reset your password: $reset_code";
    $mail->send();

    $_SESSION['email_sent'] = true;

    $_SESSION['success'] = "A reset code has been sent to your email.";
    header("Location: enter_code.php");

    echo "Email sent successfully!";
} catch (Exception $e) {
     $_SESSION["error"] = $e->getMessage();
      header("Location: forgot_pass.php");
      exit();
}
         } else {

        $_SESSION['error'] = "No user found with that email";
        header("Location: forgot_pass.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="New folder/style.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
  <div class="card p-4 shadow login-card">

    <h3 class="text-center mb-3">Forgot Password</h3>

    <?php
    if (isset($_GET['error'])) {
      echo '<div class="alert alert-info text-center">' .($_SESSION['message']) . '</div>';
      unset($_SESSION['message']);
    }
    if (isset($_SESSION['error'])) {
      echo '<div class="alert alert-danger text-center">' .($_SESSION['error']) . '</div>';
      unset($_SESSION['error']);
    }
    ?>


    <form action="forgot_pass.php" method="post">
  <div class="mb-3">
    <label class="form-label">Email Address</label>

    <input type="email" name="email" class="form-control" required>
  </div>

  <button class="btn btn-primary w-100">Send Code</button>
</form>

      <p class="text-center mt-3">
        <a href="login.php">Back to Login</a>
      </p>
    </form>

  </div>
</div>

</body>
</html>
