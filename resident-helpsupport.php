```html
<?php
session_start();
require 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: resident-portal.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$firstname   = htmlspecialchars($user['firstname']);
$lastname    = htmlspecialchars($user['lastname']);
$fullName    = $firstname . ' ' . $lastname;
$initials    = strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1));
$residentId  = htmlspecialchars($user['resident_id'] ?? 'RES-?????');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Help & Support — MySerbisyo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/resident-home.css" rel="stylesheet">
  <style>
    .help-hero { background: var(--sky); color: white; border-radius: 1rem; padding: 2.5rem; text-align: center; margin-bottom: 2rem; position: relative; overflow: hidden; }
    .help-hero h1 { font-weight: 800; font-size: 1.75rem; margin-bottom: 0.5rem; }
    .help-hero p { opacity: 0.9; font-size: 1rem; }
    .help-hero-bg { position: absolute; right: -20px; top: -20px; font-size: 10rem; opacity: 0.1; transform: rotate(-15deg); }
    
    .faq-section { background: white; border-radius: 1rem; border: 1px solid var(--gray-200); padding: 1.5rem; margin-bottom: 2rem; }
    .faq-title { font-weight: 700; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
    .accordion-button:not(.collapsed) { background-color: var(--sky-light); color: var(--sky-dark); }
    .accordion-button:focus { border-color: var(--sky); box-shadow: 0 0 0 0.25rem rgba(26, 127, 212, 0.1); }
    
    .contact-grid { display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; }
    .support-form-card { background: white; border-radius: 1rem; border: 1px solid var(--gray-200); padding: 1.5rem; }
    .contact-info-card { background: var(--gray-50); border-radius: 1rem; padding: 1.5rem; border: 1px solid var(--gray-200); }
    .ci-item { display: flex; gap: 1rem; margin-bottom: 1.25rem; }
    .ci-icon { width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--sky); flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .ci-text h4 { font-size: 0.9rem; font-weight: 700; margin-bottom: 0.2rem; }
    .ci-text p { font-size: 0.85rem; color: var(--text-muted); margin: 0; }

    @media (max-width: 992px) { .contact-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

  <aside class="r-sidebar" id="rSidebar">
    <!-- Brand & Nav same as Settings -->
    <div class="r-sidebar-brand">
      <div class="r-brand-logo"><i class="bi bi-buildings-fill"></i></div>
      <div class="r-brand-text"><span class="r-brand-name">MySerbisyo</span></div>
    </div>
    <nav class="r-sidebar-nav">
      <a href="resident-home.php" class="r-nav-item"><i class="bi bi-house-fill r-nav-icon"></i><span class="r-nav-text">Home</span></a>
      <a href="resident-requests.php" class="r-nav-item"><i class="bi bi-file-earmark-text r-nav-icon"></i><span class="r-nav-text">My Requests</span></a>
      <a href="resident-settings.php" class="r-nav-item"><i class="bi bi-gear r-nav-icon"></i><span class="r-nav-text">Settings</span></a>
      <a href="#" class="r-nav-item active"><i class="bi bi-question-circle r-nav-icon"></i><span class="r-nav-text">Help & Support</span></a>
    </nav>
  </aside>

  <div class="r-main" id="rMain">
    <header class="r-topbar">
      <div class="r-topbar-left">
        <button class="r-menu-btn" id="rMenuBtn"><i class="bi bi-list"></i></button>
        <span class="fw-bold text-dark ms-2">Help & Support</span>
      </div>
    </header>

    <main class="r-content">
      <div class="help-hero">
        <i class="bi bi-chat-dots-fill help-hero-bg"></i>
        <h1>How can we help you, <?= $firstname ?>?</h1>
        <p>Find answers to common questions or reach out to our support team.</p>
      </div>

      <div class="contact-grid">
        <div class="left-col">
          <!-- FAQ Section -->
          <div class="faq-section">
            <h3 class="faq-title"><i class="bi bi-patch-question-fill"></i> Frequently Asked Questions</h3>
            <div class="accordion" id="faqAccordion">
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#f1">
                    How long does it take to process a Barangay Clearance?
                  </button>
                </h2>
                <div id="f1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">
                    Standard processing usually takes 1 to 2 working days. You will receive a notification once it's ready for pickup.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f2">
                    Can I pay my Real Property Tax online?
                  </button>
                </h2>
                <div id="f2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">
                    Yes! You can pay using GCash, Maya, or any supported bank transfer through the Payments menu.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Message Form -->
          <div class="support-form-card">
            <h3 class="faq-title"><i class="bi bi-envelope-fill"></i> Send us a message</h3>
            <form>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Subject</label>
                  <select class="form-select">
                    <option>Technical Issue</option>
                    <option>Request Follow-up</option>
                    <option>Payment Dispute</option>
                    <option>General Inquiry</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Message</label>
                  <textarea class="form-control" rows="4" placeholder="Describe your concern in detail..."></textarea>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary px-4 fw-bold">Submit Ticket</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="right-col">
          <div class="contact-info-card">
            <h3 class="faq-title">Contact Channels</h3>
            <div class="ci-item">
              <div class="ci-icon"><i class="bi bi-telephone-fill"></i></div>
              <div class="ci-text">
                <h4>Hotline</h4>
                <p>(088) 123-4567</p>
              </div>
            </div>
            <div class="ci-item">
              <div class="ci-icon"><i class="bi bi-envelope-at-fill"></i></div>
              <div class="ci-text">
                <h4>Email</h4>
                <p>support@myserbisyo.gov.ph</p>
              </div>
            </div>
            <div class="ci-item">
              <div class="ci-icon"><i class="bi bi-geo-alt-fill"></i></div>
              <div class="ci-text">
                <h4>Main Office</h4>
                <p>Municipal Hall Compound, Brgy. Poblacion</p>
              </div>
            </div>
            <hr>
            <p class="small text-muted text-center mb-0">Office Hours: <br> Mon - Fri, 8:00 AM - 5:00 PM</p>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="r-overlay" id="rOverlay"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/resident-home.js"></script>
</body>
</html>

```
