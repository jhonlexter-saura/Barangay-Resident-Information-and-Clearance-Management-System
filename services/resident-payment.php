<?php
$active_nav = 'payments';
require '../aut.php';
require '../config.php';
include 'resident-sidebar.php';

$uploadBase = realpath(__DIR__ . '/../../files');
if (!$uploadBase) {
    mkdir(__DIR__ . '/../../files', 0755, true);
    $uploadBase = realpath(__DIR__ . '/../../files');
}

// Add this to debug
if (!$uploadBase) {
    echo json_encode(['success' => false, 'message' => 'Upload directory could not be created.']);
    return;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Payments &amp; Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/shared.css" rel="stylesheet">
  <link href="../css/resident-home.css" rel="stylesheet">
  <link href="../css/services.css" rel="stylesheet">
</head>
<body>
  <!-- Main -->
  <div class="r-main" id="rMain">

    <header class="r-topbar">
      <div class="r-topbar-left">
        <button class="r-menu-btn" id="rMenuBtn"><i class="bi bi-list"></i></button>
        <div class="r-topbar-brand">
          <div class="r-tb-logo"><i class="bi bi-buildings-fill"></i></div>
          <span class="r-tb-name">KALASUNGAY</span>
        </div>
      </div>
      <div class="r-topbar-right">
        <button class="r-topbar-btn"><i class="bi bi-bell"></i><span class="r-notif-dot"></span></button>
        <a href="../resident/resident-profile.php" class="r-profile-chip">
          <div class="r-chip-avatar"></div>
          <span class="r-chip-name"></span>
          <i class="bi bi-chevron-down"></i>
        </a>
      </div>
    </header>

    <main class="r-content">

      <!-- Back link -->
      <a href="../resident/resident-home.php" class="svc-back-link" style="display:inline-flex; align-items:center; gap:5px; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-decoration:none; margin-bottom:0.85rem; transition:color 0.15s;">
        <i class="bi bi-arrow-left"></i> Back to Services
      </a>

      <!-- Hero -->
      <div class="svc-page-hero">
        <div class="svc-hero-eyebrow"><i class="bi bi-cart-fill"></i> Review &amp; Submit</div>
        <div class="svc-hero-title">Payments &amp; Cart</div>
        <div class="svc-hero-sub">Review your service requests, choose a payment method, and submit to the LGU office.</div>
      </div>

      <!-- Layout -->
      <div class="pay-layout">

        <!-- Left: cart items -->
        <div>

          <!-- Items card -->
          <div class="pay-card" style="margin-bottom:1.25rem;">
            <div class="pay-card-header">
              <div class="pay-card-title">
                <i class="bi bi-cart-fill"></i> Service Requests in Cart
              </div>
              <a href="../resident/resident-home.php" style="font-size:0.75rem; font-weight:600; color:var(--sky); text-decoration:none;">
                <i class="bi bi-plus-lg"></i> Add More
              </a>
            </div>
            <div class="pay-card-body">

              <!-- Empty state -->
              <div class="pay-empty" id="payEmpty" style="display:none;">
                <div class="pay-empty-icon"><i class="bi bi-cart-x"></i></div>
                <div class="pay-empty-title">Your cart is empty</div>
                <div class="pay-empty-sub">Go back to Services and add the requests you need to process.</div>
                <a href="../resident/resident-home.php" style="font-size:0.82rem; font-weight:700; color:var(--sky); text-decoration:none; display:flex; align-items:center; gap:5px;">
                  <i class="bi bi-arrow-left"></i> Browse Services
                </a>
              </div>

              <!-- Items container -->
              <div id="payItemsContainer"></div>

            </div>
          </div>

          <!-- Important reminders -->
          <div class="pay-card">
            <div class="pay-card-header">
              <div class="pay-card-title">
                <i class="bi bi-info-circle-fill"></i> Important Reminders
              </div>
            </div>
            <div class="pay-card-body" style="padding:1.1rem 1.25rem;">
              <div style="display:flex; flex-direction:column; gap:0.75rem;">

                <div style="display:flex; gap:10px; font-size:0.79rem; color:var(--text-mid); line-height:1.5;">
                  <i class="bi bi-1-circle-fill" style="color:var(--sky); flex-shrink:0; margin-top:1px;"></i>
                  <span>Submitting your request does <strong>not</strong> guarantee immediate processing. The LGU office will review your documents and contact you if additional information is needed.</span>
                </div>

                <div style="display:flex; gap:10px; font-size:0.79rem; color:var(--text-mid); line-height:1.5;">
                  <i class="bi bi-2-circle-fill" style="color:var(--sky); flex-shrink:0; margin-top:1px;"></i>
                  <span>Bring your <strong>original documents</strong> and valid ID on the day of release or appointment. Photocopies are for pre-screening only.</span>
                </div>

                <div style="display:flex; gap:10px; font-size:0.79rem; color:var(--text-mid); line-height:1.5;">
                  <i class="bi bi-3-circle-fill" style="color:var(--sky); flex-shrink:0; margin-top:1px;"></i>
                  <span>For services marked <strong>"Varies"</strong> or <strong>"Computed"</strong>, the exact amount will be assessed by the Treasurer's Office upon review.</span>
                </div>

                <div style="display:flex; gap:10px; font-size:0.79rem; color:var(--text-mid); line-height:1.5;">
                  <i class="bi bi-4-circle-fill" style="color:var(--sky); flex-shrink:0; margin-top:1px;"></i>
                  <span>You will receive a <strong>reference number</strong> after submission. Use this to track the status of your request in <strong>My Requests</strong>.</span>
                </div>

              </div>
            </div>
          </div>

        </div>

        <!-- Right: order summary + payment -->
        <div class="pay-summary-card">

          <div class="pay-summary-hero">
            <div class="pay-summary-hero-title">Order Summary</div>
            <div class="pay-summary-hero-count" id="summaryHeroCount">0 services in your cart</div>
          </div>

          <div class="pay-summary-body">

            <!-- Line items -->
            <div id="summaryLines"></div>

            <!-- Total -->
            <div class="pay-total-row">
              <span class="pay-total-label">Total Amount</span>
              <span class="pay-total-amount" id="summaryTotal">₱0.00</span>
            </div>

            <!-- Payment method -->
            <div class="pay-method-section">
              <div class="pay-method-label">Payment Method</div>
              <div class="pay-methods">

                <label class="pay-method-opt selected">
                  <input type="radio" name="payMethod" value="counter" checked>
                  <div class="pay-method-icon" style="background:#e8f3fc; color:#1a7fd4;">
                    <i class="bi bi-building"></i>
                  </div>
                  <div>
                    <div class="pay-method-name">Pay at Counter</div>
                    <div class="pay-method-sub">Pay upon release at the LGU Cashier</div>
                  </div>
                </label>

                <label class="pay-method-opt">
                  <input type="radio" name="payMethod" value="gcash">
                  <div class="pay-method-icon" style="background:#e6f7ef; color:#1a9e5f;">
                    <i class="bi bi-phone-fill"></i>
                  </div>
                  <div>
                    <div class="pay-method-name">GCash</div>
                    <div class="pay-method-sub">Via GCash QR or reference number</div>
                  </div>
                </label>

                <label class="pay-method-opt">
                  <input type="radio" name="payMethod" value="bank">
                  <div class="pay-method-icon" style="background:#fef3c7; color:#d97706;">
                    <i class="bi bi-bank2"></i>
                  </div>
                  <div>
                    <div class="pay-method-name">Bank Transfer</div>
                    <div class="pay-method-sub">LBP / DBP / UnionBank</div>
                  </div>
                </label>

              </div>
            </div>

            <!-- Submit button -->
            <button class="btn-submit-request" id="submitRequestBtn" onclick="submitRequest()">
              <i class="bi bi-send-fill"></i> Submit Request to LGU
            </button>

            <div class="pay-submit-note">
              <i class="bi bi-shield-check"></i>
              Secured &amp; encrypted — RA 10175
            </div>

          </div>
        </div>

      </div>

    </main>
  </div>

  <!-- Mobile overlay -->
  <div class="r-overlay" id="rOverlay"></div>

  <!-- Submission success overlay -->
  <div class="pay-success-overlay" id="paySuccessOverlay">
    <div class="pay-success-icon"><i class="bi bi-check-lg"></i></div>
    <div class="pay-success-title">Request Submitted!</div>
    <div class="pay-success-sub" id="successCount">Your service requests have been sent to the LGU office for processing. You will be notified of updates.</div>
    <div class="pay-success-ref" id="successRef">Reference No: —</div>
    <div class="pay-success-actions">
      <a href="../resident/resident-home.php" class="btn-success-home">
        <i class="bi bi-house-fill"></i> Go to Home
      </a>
      <a href="#" class="btn-success-track">
        <i class="bi bi-search"></i> Track Request
      </a>
    </div>
  </div>

  <script src="../js/resident-home.js"></script>
  <script src="../js/services.js"></script>

</body>
</html>
