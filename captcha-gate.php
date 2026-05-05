<?php
session_start();
require 'config.php';

// Check if CAPTCHA has already been verified
if (isset($_SESSION['captcha_verified']) && $_SESSION['captcha_verified'] === true) {
    header('Location: portal-selection.php');
    exit();
}

// Handle CAPTCHA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptcha_response)) {
        $_SESSION['captcha_error'] = 'Please complete the CAPTCHA verification.';
        header('Location: captcha-gate.php');
        exit();
    }

    // Verify reCAPTCHA response
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $verify_data = [
        'secret' => $recaptcha_secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $ch = curl_init($verify_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($verify_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $verify_response = curl_exec($ch);
    curl_close($ch);

    $verify_result = json_decode($verify_response, true);

    if ($verify_result['success']) {
        $_SESSION['captcha_verified'] = true;
        header('Location: portal-selection.php');
        exit();
    } else {
        $_SESSION['captcha_error'] = 'CAPTCHA verification failed. Please try again.';
        header('Location: captcha-gate.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LGU Portal — Security Verification</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/portal-entry.css" rel="stylesheet">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    .captcha-gate {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 2rem;
    }
    .captcha-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      padding: 3rem;
      text-align: center;
      max-width: 500px;
      width: 100%;
    }
    .captcha-icon {
      font-size: 4rem;
      color: #667eea;
      margin-bottom: 1rem;
    }
    .captcha-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2d3748;
      margin-bottom: 0.5rem;
    }
    .captcha-subtitle {
      color: #718096;
      margin-bottom: 2rem;
      font-size: 1.1rem;
    }
    .captcha-form {
      margin-top: 2rem;
    }
    .g-recaptcha {
      display: inline-block;
      margin: 1rem auto;
    }
    .btn-verify {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 10px;
      padding: 12px 30px;
      font-weight: 600;
      color: white;
      transition: transform 0.2s;
    }
    .btn-verify:hover {
      transform: translateY(-2px);
      color: white;
    }
    .security-notice {
      margin-top: 2rem;
      padding: 1rem;
      background: #f7fafc;
      border-radius: 10px;
      border-left: 4px solid #667eea;
    }
    .security-notice h6 {
      color: #2d3748;
      margin-bottom: 0.5rem;
    }
    .security-notice p {
      color: #718096;
      font-size: 0.9rem;
      margin: 0;
    }
  </style>
</head>
<body>

  <div class="captcha-gate">
    <div class="captcha-card">
      <div class="captcha-icon">
        <i class="bi bi-shield-check"></i>
      </div>

      <h1 class="captcha-title">Security Verification</h1>
      <p class="captcha-subtitle">
        Before accessing our government services portal, please complete the security verification below.
      </p>

      <?php if (!empty($_SESSION['captcha_error'])): ?>
        <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <?= htmlspecialchars($_SESSION['captcha_error']); unset($_SESSION['captcha_error']); ?>
        </div>
      <?php endif; ?>

      <form action="captcha-gate.php" method="post" class="captcha-form">
        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptcha_site_key); ?>"></div>
        <button type="submit" class="btn btn-verify mt-3">
          <i class="bi bi-check-circle me-2"></i>
          Verify & Continue
        </button>
      </form>

      <div class="security-notice">
        <h6><i class="bi bi-info-circle me-2"></i>Why do we need this?</h6>
        <p>This verification helps protect our government services from automated attacks and ensures only legitimate users can access the system.</p>
      </div>
    </div>
  </div>

</body>
</html>