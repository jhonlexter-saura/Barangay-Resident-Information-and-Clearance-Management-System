<?php 

session_start();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MySerbisyo — Create Account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/resident-signup.css" rel="stylesheet">
</head>

<body>

  <!-- ── Top nav (matches resident-portal) ── -->
  <nav class="top-nav">
    <a href="resident-portal.php" class="nav-brand">
      <div class="brand-logo"><i class="bi bi-buildings-fill"></i></div>
      <div class="brand-text">
        <span class="brand-name">MySerbisyo</span>
        <span class="brand-tagline">Municipal Resident Portal</span>
      </div>
    </a>
    <div class="nav-links">
      <a href="#" class="nav-link-item">Announcements</a>
      <a href="#" class="nav-link-item">Services</a>
      <a href="#" class="nav-link-item">Track Request</a>
      <a href="#" class="nav-link-item">Contact Us</a>
      <a href="resident-portal.php" class="nav-link-item signin">Sign In</a>
    </div>
  </nav>

  <!-- ── Hero strip (matches resident-portal) ── -->
  <div class="hero-strip">
    <div class="hero-deco"></div>
    <div class="hero-content">
      <div class="hero-eyebrow">
        <i class="bi bi-person-plus-fill"></i>
        Create Your Account
      </div>
      <div class="hero-title">
        Join your<br><em>community online.</em>
      </div>
      <div class="hero-sub">
        Register once and access all municipal services from anywhere, anytime.
      </div>
    </div>
  </div>

  <!-- ── Main content ── -->
  <div class="main-content">
    <div class="signup-layout">

      <!-- ── Left: benefits panel ── -->
      <div class="benefits-panel">

        <div class="benefits-heading">What you get with a free account</div>

        <div class="benefit-item">
          <div class="benefit-icon" style="background:#e8f3fc; color:#1a7fd4;">
            <i class="bi bi-file-earmark-check-fill"></i>
          </div>
          <div class="benefit-text">
            <div class="benefit-title">Request Documents Online</div>
            <div class="benefit-desc">Get barangay clearances, cedula, and certificates without lining up.</div>
          </div>
        </div>

        <div class="benefit-item">
          <div class="benefit-icon" style="background:#e6f7ef; color:#1a9e5f;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="benefit-text">
            <div class="benefit-title">Track Your Applications</div>
            <div class="benefit-desc">See real-time status updates on all your filed requests.</div>
          </div>
        </div>

        <div class="benefit-item">
          <div class="benefit-icon" style="background:#fef3c7; color:#d97706;">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div class="benefit-text">
            <div class="benefit-title">Pay Fees Digitally</div>
            <div class="benefit-desc">Pay real property tax and other government fees securely online.</div>
          </div>
        </div>

        <div class="benefit-item">
          <div class="benefit-icon" style="background:#f0e8ff; color:#7c3aed;">
            <i class="bi bi-bell-fill"></i>
          </div>
          <div class="benefit-text">
            <div class="benefit-title">Stay Informed</div>
            <div class="benefit-desc">Receive announcements, reminders, and updates from your LGU.</div>
          </div>
        </div>

        <!-- Already have account -->
        <div class="already-account">
          Already have an account?
          <a href="resident-portal.php">Sign in here</a>
        </div>

      </div>

      <!-- ── Right: sign-up card ── -->
      <div class="signup-card">

        <div class="signup-card-header">
          <div class="signup-card-header-deco"></div>
          <div class="signup-welcome">
            <i class="bi bi-person-circle"></i> Create Your Account
          </div>
          <div class="signup-welcome-sub">Fill in your details to get started — it's free</div>
        </div>

        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
          <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($_SESSION['error']) ?>
          </div>
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <div class="signup-card-body">
          <form id="signupForm" action="resident-signup_validate.php" method="POST" novalidate>

            <!-- Name row -->
            <div class="field-row">
              <div class="field-group">
                <label for="firstname" class="form-label">
                  <i class="bi bi-person-fill"></i> First Name
                </label>
                <input
                  type="text"
                  id="firstname"
                  class="form-control"
                  placeholder="Juan" 
                  name="firstname"
                  required
                  autocomplete="given-name"
                >
                <span class="field-error" id="firstname-error"></span>
              </div>

              <div class="field-group">
                <label for="lastname" class="form-label">
                  <i class="bi bi-person-fill"></i> Last Name
                </label>
                <input
                  type="text"
                  id="lastname"
                  class="form-control"
                  placeholder="Dela Cruz" 
                  name="lastname"
                  required
                  autocomplete="family-name"
                >
                <span class="field-error" id="lastname-error"></span>
              </div>
            </div>

            <!-- Email -->
            <div class="field-group">
              <label for="email" class="form-label">
                <i class="bi bi-envelope-fill"></i> Email Address
              </label>
              <input
                type="email"
                id="email"
                class="form-control"
                placeholder="juan.delacruz@email.com" 
                name="email"
                required
                autocomplete="email"
              >
              <span class="form-text">Use a valid email — you'll verify it after signing up</span>
              <span class="field-error" id="email-error"></span>
            </div>

            <!-- Password -->
            <div class="field-group">
              <label for="password" class="form-label">
                <i class="bi bi-lock-fill"></i> Password
              </label>
              <div class="input-with-icon">
                <input
                  type="password"
                  id="password"
                  class="form-control"
                  placeholder="••••••••" name="password"
                  required
                  autocomplete="new-password"
                >
                <button type="button" class="input-end-icon" id="togglePassword" aria-label="Show password">
                  <i class="bi bi-eye" id="pwEyeIcon"></i>
                </button>
              </div>
              <!-- Password strength bar -->
              <div class="strength-bar" id="strengthBar">
                <div class="strength-track">
                  <div class="strength-fill" id="strengthFill"></div>
                </div>
                <span class="strength-label" id="strengthLabel">Enter a password</span>
              </div>
              <span class="field-error" id="password-error"></span>
            </div>

            <!-- Confirm password -->
            <div class="field-group">
              <label for="confirmPassword" class="form-label">
                <i class="bi bi-lock-fill"></i> Confirm Password
              </label>
              <div class="input-with-icon">
                <input
                  type="password"
                  id="confirmPassword"
                  class="form-control"
                  placeholder="••••••••" name="confirm_password"
                  required
                  autocomplete="new-password"
                >
                <button type="button" class="input-end-icon" id="toggleConfirm" aria-label="Show confirm password">
                  <i class="bi bi-eye" id="confirmEyeIcon"></i>
                </button>
              </div>
              <span class="field-error" id="confirm-error"></span>
              <!-- Match indicator -->
              <span class="match-indicator" id="matchIndicator"></span>
            </div>

            <!-- Terms -->
            <div class="terms-row">
              <input type="checkbox" id="terms" class="form-check-input" required>
              <label for="terms" class="terms-label">
                I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
              </label>
            </div>
            <span class="field-error" id="terms-error"></span>

            <!-- Submit -->
            <button type="submit" class="btn-signup" id="submitBtn">
              <i class="bi bi-person-check-fill"></i>
              Create My Account
            </button>

          </form>

          <!-- Sign in link -->
          <div class="signin-prompt">
            Already registered? <a href="resident-portal.php">Sign in to your account</a>
          </div>

        </div>
      </div>

    </div>
  </div>

  <!-- ── Footer ── -->
  <div class="page-footer">
    <div class="footer-links">
      <a href="#" class="footer-link">Privacy Policy</a>
      <a href="#" class="footer-link">Terms of Use</a>
      <a href="#" class="footer-link">Accessibility</a>
      <a href="#" class="footer-link">Sitemap</a>
    </div>
    <div class="footer-copy">&copy; 2026 Municipal Government of [Municipality] &nbsp;·&nbsp; All rights reserved</div>
  </div>

  <script src="js/resident-signup.js"></script>

</body>
</html>