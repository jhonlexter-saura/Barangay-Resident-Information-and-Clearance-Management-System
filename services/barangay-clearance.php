<?php
$active_nav = 'payments';
include 'resident-sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MySerbisyo — Barangay Clearance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/shared.css" rel="stylesheet">
  <link href="../css/resident-home.css" rel="stylesheet">
  <link href="../css/services.css" rel="stylesheet">
</head>
<body>

  <?php include 'resident-sidebar.php'; ?>

  <!-- Main -->
  <div class="r-main" id="rMain">
    <header class="r-topbar">
      <div class="r-topbar-left">
        <button class="r-menu-btn" id="rMenuBtn"><i class="bi bi-list"></i></button>
        <div class="r-topbar-brand">
          <div class="r-tb-logo"><i class="bi bi-buildings-fill"></i></div>
          <span class="r-tb-name">MySerbisyo</span>
        </div>
      </div>
      <div class="r-topbar-right">
        <a href="resident-payment.php" class="r-topbar-btn" style="position:relative;" title="View cart">
          <i class="bi bi-cart3"></i>
          <span class="r-notif-dot" id="cartDot" style="display:none;"></span>
        </a>
        <button class="r-topbar-btn"><i class="bi bi-bell"></i><span class="r-notif-dot"></span></button>
        <a href="../resident-profile.php" class="r-profile-chip">
          <div class="r-chip-avatar">JD</div>
          <span class="r-chip-name">Juan</span>
          <i class="bi bi-chevron-down"></i>
        </a>
      </div>
    </header>

    <main class="r-content">

      <!-- Back link -->
      <a href="../resident-home.php" class="svc-back-link" style="display:inline-flex; align-items:center; gap:5px; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-decoration:none; margin-bottom:0.85rem; transition:color 0.15s;">
        <i class="bi bi-arrow-left"></i> Back to Services
      </a>

      <!-- Hero -->
      <div class="svc-page-hero">
        <div class="svc-hero-eyebrow"><i class="bi bi-file-earmark-text-fill"></i> Service Request</div>
        <div class="svc-hero-title">Barangay Clearance</div>
        <div class="svc-hero-sub">For employment, business, travel, and legal purposes</div>
        <div class="svc-hero-meta">
          <div class="svc-hero-fee">₱50.00</div>
          <div class="svc-hero-fee-label">Processing Fee</div>
          <div class="svc-hero-time"><i class="bi bi-clock"></i> 1–2 business days</div>
        </div>
      </div>

      <!-- Steps -->
      <div class="svc-steps">
        <div class="svc-step active" id="step-indicator-0">
          <div class="step-num">1</div>
          <span class="step-label">Personal Info</span>
        </div>
        <div class="svc-step" id="step-indicator-1">
          <div class="step-num">2</div>
          <span class="step-label">Purpose &amp; Details</span>
        </div>
        <div class="svc-step" id="step-indicator-2">
          <div class="step-num">3</div>
          <span class="step-label">Upload Docs</span>
        </div>
        <div class="svc-step" id="step-indicator-3">
          <div class="step-num">4</div>
          <span class="step-label">Review &amp; Cart</span>
        </div>
      </div>

      <!-- Layout -->
      <div class="svc-layout">

        <!-- Form card -->
        <div class="svc-form-card">
          <div class="svc-form-header">
            <div class="svc-form-header-icon" style="background:#e8f3fc; color:#1a7fd4;">
              <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <div class="svc-form-header-text">
              <div class="svc-form-title">Barangay Clearance</div>
              <div class="svc-form-subtitle">Fill in all required fields marked with *</div>
            </div>
          </div>
          <div class="svc-form-body">
            <form id="svcForm" novalidate>

              <!-- Personal information (auto-filled) -->
              <div class="svc-field-section">
                <div class="svc-field-section-label">
                  <i class="bi bi-person-fill"></i> Your Information
                  <span style="font-size:0.6rem; color:var(--green); font-weight:600; text-transform:none; letter-spacing:0; background:var(--green-light); padding:2px 6px; border-radius:4px; margin-left:4px;">Auto-filled from profile</span>
                </div>
                <div class="svc-field-row">
                  <div class="svc-field">
                    <label class="svc-label">Full Name</label>
                    <input type="text" class="svc-input" value="Juan Santos Dela Cruz Jr." disabled>
                  </div>
                  <div class="svc-field">
                    <label class="svc-label">Resident ID</label>
                    <input type="text" class="svc-input" value="RES-00412" disabled>
                  </div>
                </div>
                <div class="svc-field-row">
                  <div class="svc-field">
                    <label class="svc-label">Date of Birth</label>
                    <input type="text" class="svc-input" value="June 15, 1990" disabled>
                  </div>
                  <div class="svc-field">
                    <label class="svc-label">Address</label>
                    <input type="text" class="svc-input" value="123 Rizal St., Brgy. San Isidro" disabled>
                  </div>
                </div>
              </div>

              <!-- Service-specific fields -->
              <div class="svc-field-section">
                <div class="svc-field-section-label"><i class="bi bi-pencil-square"></i> Service Details</div>

                <div class="svc-field">
                  <label class="svc-label" for="purpose">Purpose of Clearance<span class="req">*</span></label>
                  <select class="svc-select" id="purpose" name="purpose">
                    <option value="">— Select —</option>
                    <option value="employment">Employment</option>
                    <option value="travel_abroad">Travel Abroad</option>
                    <option value="business">Business</option>
                    <option value="legal_proceedings">Legal Proceedings</option>
                    <option value="scholarship">Scholarship</option>
                    <option value="bank/loan">Bank/Loan</option>
                    <option value="others">Others</option>
                  </select>
                  <span class="svc-error-msg" id="purpose-err"><i class="bi bi-exclamation-circle"></i> This field is required</span>
                </div>

                <div class="svc-field">
                  <label class="svc-label" for="employer">Name of Employer/Institution (if applicable)<span class="req">*</span></label>
                  <input type="text" class="svc-input" id="employer" name="employer" placeholder="e.g. ABC Corporation">
                  <span class="svc-error-msg" id="employer-err"><i class="bi bi-exclamation-circle"></i> This field is required</span>
                </div>

              </div>

              <!-- Document uploads -->
              <div class="svc-field-section">
                <div class="svc-field-section-label"><i class="bi bi-paperclip"></i> Supporting Documents</div>
                <div class="svc-file-upload" id="fileDropZone">
                  <input type="file" id="fileInput" multiple accept=".jpg,.jpeg,.png,.pdf">
                  <div class="upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                  <div class="upload-text">Click to upload or drag &amp; drop files here</div>
                  <div class="upload-sub">Accepted: JPG, PNG, PDF &mdash; Max 5MB per file</div>
                  <div class="upload-preview" id="uploadPreview"></div>
                </div>
              </div>

            </form>
          </div>
        </div>

        <!-- Right sidebar -->
        <div class="svc-sidebar">

          <!-- Summary -->
          <div class="svc-summary-card">
            <div class="svc-summary-header">
              <div class="svc-summary-title"><i class="bi bi-receipt"></i> Request Summary</div>
            </div>
            <div class="svc-summary-body">
              <div class="svc-summary-row">
                <span class="svc-summary-key">Service</span>
                <span class="svc-summary-val">Barangay Clearance</span>
              </div>
              <div class="svc-summary-row">
                <span class="svc-summary-key">Applicant</span>
                <span class="svc-summary-val">Juan Dela Cruz</span>
              </div>
              <div class="svc-summary-row">
                <span class="svc-summary-key">Processing</span>
                <span class="svc-summary-val">1–2 business days</span>
              </div>
              <div class="svc-summary-row">
                <span class="svc-summary-key">Fee</span>
                <span class="svc-summary-val fee">₱50.00</span>
              </div>
            </div>
          </div>

          <!-- Requirements -->
          <div class="svc-info-card">
            <div class="svc-info-title"><i class="bi bi-clipboard-check"></i> Requirements</div>
            <div class="req-checklist">
              <div class="req-item"><i class="bi bi-check-circle-fill"></i> Valid government-issued ID</div>
              <div class="req-item"><i class="bi bi-check-circle-fill"></i> Proof of residency (utility bill or lease agreement)</div>
              <div class="req-item"><i class="bi bi-check-circle-fill"></i> Barangay ID or voter's ID (if available)</div>
            </div>
          </div>

          <!-- Notice -->
          <div class="svc-notice">
            <i class="bi bi-info-circle-fill"></i>
            <span>Adding to cart does not yet submit your request. Proceed to <strong>Payments</strong> to finalize and send your request to the LGU office.</span>
          </div>

          <!-- Cart section -->
          <div class="svc-cart-section">
            <div class="svc-cart-fee-row">
              <span class="svc-cart-fee-label">Service Fee</span>
              <span class="svc-cart-fee-value" id="displayFee">₱50.00</span>
            </div>
            <button class="btn-add-cart" id="addCartBtn"
              onclick="addToCart('Barangay Clearance', '₱50.00', 'bi-file-earmark-text-fill', '#e8f3fc', '#1a7fd4')">
              <i class="bi bi-cart-plus-fill"></i> Add to Cart
            </button>
            <div class="svc-cart-note">
              <i class="bi bi-cart3"></i>
              View cart in <a href="resident-payment.php">Payments</a>
            </div>
          </div>

        </div>
      </div>

    </main>
  </div>

  <div class="r-overlay" id="rOverlay"></div>

  <!-- Floating cart button -->
  <a href="resident-payment.php" class="cart-float" id="cartFloat" style="display:none;">
    <i class="bi bi-cart-fill"></i>
    <span id="cartFloatLabel">View Cart</span>
    <span class="cart-float-count" id="cartFloatCount">0</span>
  </a>

  <script src="../js/resident-home.js"></script>
  <script src="../js/services.js"></script>
</body>
</html>