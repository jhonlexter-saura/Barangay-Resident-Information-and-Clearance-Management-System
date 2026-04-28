<?php
session_start();
require 'config.php';

// ── Auth guard: redirect to login if not signed in ──────────────────────────
if (empty($_SESSION['user_id'])) {
    header('Location: resident-portal.php');
    exit();
}

// ── Fetch fresh user row from DB ─────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    // User no longer exists — destroy session and redirect
    session_destroy();
    header('Location: resident-portal.php');
    exit();
}

// ── Convenience variables ────────────────────────────────────────────────────
$firstname   = htmlspecialchars($user['firstname']);
$lastname    = htmlspecialchars($user['lastname']);
$fullName    = $firstname . ' ' . $lastname;
$initials    = strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1));
$residentId  = htmlspecialchars($user['resident_id'] ?? 'RES-?????');

// Time-based greeting
$hour = (int) date('G');
if ($hour < 12)      $greeting = 'Good morning';
elseif ($hour < 17)  $greeting = 'Good afternoon';
else                 $greeting = 'Good evening';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MySerbisyo — Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/resident-home.css" rel="stylesheet">
</head>

<body>

  <!-- ── Sidebar ── -->
  <aside class="r-sidebar" id="rSidebar">

    <div class="r-sidebar-brand">
      <div class="r-brand-logo"><i class="bi bi-buildings-fill"></i></div>
      <div class="r-brand-text">
        <span class="r-brand-name">MySerbisyo</span>
        <span class="r-brand-sub">Resident Portal</span>
      </div>
    </div>

    <nav class="r-sidebar-nav">

      <div class="r-nav-label">Menu</div>

      <a href="resident-home.php" class="r-nav-item active" data-tooltip="Home">
        <i class="bi bi-house-fill r-nav-icon"></i>
        <span class="r-nav-text">Home</span>
      </a>

      <a href="resident-requests.html" class="r-nav-item" data-tooltip="My Requests">
        <i class="bi bi-file-earmark-text r-nav-icon"></i>
        <span class="r-nav-text">My Requests</span>
        <span class="r-nav-badge">2</span>
      </a>

      <a href="services/resident-payment.html" class="r-nav-item" data-tooltip="Payments">
        <i class="bi bi-cash-coin r-nav-icon"></i>
        <span class="r-nav-text">Payments</span>
      </a>

      <a href="services/appointments.html" class="r-nav-item" data-tooltip="Appointments">
        <i class="bi bi-calendar-check r-nav-icon"></i>
        <span class="r-nav-text">Appointments</span>
      </a>

      <a href="resident-notifications.html" class="r-nav-item" data-tooltip="Notifications">
        <i class="bi bi-bell r-nav-icon"></i>
        <span class="r-nav-text">Notifications</span>
        <span class="r-nav-badge">5</span>
      </a>

      <div class="r-nav-divider"></div>
      <div class="r-nav-label">Account</div>

      <a href="resident-profile.html" class="r-nav-item" data-tooltip="My Profile">
        <i class="bi bi-person-circle r-nav-icon"></i>
        <span class="r-nav-text">My Profile</span>
      </a>

      <a href="#" class="r-nav-item" data-tooltip="Settings">
        <i class="bi bi-gear r-nav-icon"></i>
        <span class="r-nav-text">Settings</span>
      </a>

      <a href="#" class="r-nav-item" data-tooltip="Help">
        <i class="bi bi-question-circle r-nav-icon"></i>
        <span class="r-nav-text">Help & Support</span>
      </a>

    </nav>

    <div class="r-sidebar-footer">
      <div class="r-user-row">
        <div class="r-user-avatar"><?= $initials ?></div>
        <div class="r-user-info">
          <span class="r-user-name"><?= $fullName ?></span>
          <span class="r-user-sub">Resident ID: <?= $residentId ?></span>
        </div>
        <a href="resident-logout.php" class="r-logout-btn" title="Sign out">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>

  </aside>

  <!-- ── Main area ── -->
  <div class="r-main" id="rMain">

    <!-- ── Top bar ── -->
    <header class="r-topbar">
      <div class="r-topbar-left">
        <button class="r-menu-btn" id="rMenuBtn" aria-label="Open menu">
          <i class="bi bi-list"></i>
        </button>
        <div class="r-topbar-brand">
          <div class="r-tb-logo"><i class="bi bi-buildings-fill"></i></div>
          <span class="r-tb-name">MySerbisyo</span>
        </div>
      </div>

      <div class="r-topbar-right">
        <button class="r-topbar-btn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="r-notif-dot"></span>
        </button>
        <a href="resident-profile.php" class="r-profile-chip">
          <div class="r-chip-avatar"><?= $initials ?></div>
          <span class="r-chip-name"><?= $firstname ?></span>
          <i class="bi bi-chevron-down"></i>
        </a>
      </div>
    </header>

    <!-- ── Page content ── -->
    <main class="r-content">

      <!-- Welcome banner -->
      <div class="welcome-banner">
        <div class="welcome-text">
          <div class="welcome-greeting"><?= $greeting ?>, <span><?= $firstname ?>!</span> 👋</div>
          <div class="welcome-sub">Here's what's available for you today.</div>
        </div>
        <div class="welcome-meta">
          <div class="welcome-id">
            <i class="bi bi-person-badge"></i>
            Resident ID: <strong><?= $residentId ?></strong>
          </div>
          <div class="welcome-date" id="welcomeDate"></div>
        </div>
      </div>

      <!-- ── Quick status cards ── -->
      <div class="status-strip">

        <div class="status-card">
          <div class="sc-icon" style="background:#e8f3fc; color:#1a7fd4;">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div class="sc-body">
            <span class="sc-value">2</span>
            <span class="sc-label">Pending Requests</span>
          </div>
        </div>

        <div class="status-card">
          <div class="sc-icon" style="background:#e6f7ef; color:#1a9e5f;">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="sc-body">
            <span class="sc-value">8</span>
            <span class="sc-label">Completed</span>
          </div>
        </div>

        <div class="status-card">
          <div class="sc-icon" style="background:#fef3c7; color:#d97706;">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="sc-body">
            <span class="sc-value">₱350</span>
            <span class="sc-label">Unpaid Balance</span>
          </div>
        </div>

        <div class="status-card">
          <div class="sc-icon" style="background:#f0e8ff; color:#7c3aed;">
            <i class="bi bi-calendar-event"></i>
          </div>
          <div class="sc-body">
            <span class="sc-value">1</span>
            <span class="sc-label">Upcoming Appointment</span>
          </div>
        </div>

      </div>

      <!-- ── Main grid ── -->
      <div class="home-grid">

        <!-- Services section -->
        <div class="home-section">
          <div class="section-header">
            <div class="section-title">
              <i class="bi bi-grid-fill"></i> Available Services
            </div>
            <span class="section-sub">Click any service to start a request</span>
          </div>

          <div class="services-grid">

            <a href="services/barangay-clearance.html" class="svc-card">
              <div class="svc-icon" style="background:#e8f3fc; color:#1a7fd4;">
                <i class="bi bi-file-earmark-text-fill"></i>
              </div>
              <div class="svc-info">
                <div class="svc-name">Barangay Clearance</div>
                <div class="svc-desc">For employment, business, or legal purposes</div>
                <div class="svc-meta">
                  <span class="svc-fee">₱50.00</span>
                  <span class="svc-days"><i class="bi bi-clock"></i> 1–2 days</span>
                </div>
              </div>
              <i class="bi bi-arrow-right svc-arrow"></i>
            </a>

            <a href="services/cedula.html" class="svc-card">
              <div class="svc-icon" style="background:#e6f7ef; color:#1a9e5f;">
                <i class="bi bi-card-heading"></i>
              </div>
              <div class="svc-info">
                <div class="svc-name">Cedula / CTC</div>
                <div class="svc-desc">Community Tax Certificate — annual requirement</div>
                <div class="svc-meta">
                  <span class="svc-fee">₱25.00+</span>
                  <span class="svc-days"><i class="bi bi-clock"></i> Same day</span>
                </div>
              </div>
              <i class="bi bi-arrow-right svc-arrow"></i>
            </a>

            <a href="services/business-permit.php" class="svc-card">
              <div class="svc-icon" style="background:#fef3c7; color:#d97706;">
                <i class="bi bi-house-fill"></i>
              </div>
              <div class="svc-info">
                <div class="svc-name">Business Permit</div>
                <div class="svc-desc">New application or annual renewal</div>
                <div class="svc-meta">
                  <span class="svc-fee">Varies</span>
                  <span class="svc-days"><i class="bi bi-clock"></i> 3–5 days</span>
                </div>
              </div>
              <i class="bi bi-arrow-right svc-arrow"></i>
            </a>

            <a href="services/health-cert.html" class="svc-card">
              <div class="svc-icon" style="background:#fde8e8; color:#dc2626;">
                <i class="bi bi-heart-pulse-fill"></i>
              </div>
              <div class="svc-info">
                <div class="svc-name">Health Certificate</div>
                <div class="svc-desc">Required for food handlers and workers</div>
                <div class="svc-meta">
                  <span class="svc-fee">₱100.00</span>
                  <span class="svc-days"><i class="bi bi-clock"></i> 1 day</span>
                </div>
              </div>
              <i class="bi bi-arrow-right svc-arrow"></i>
            </a>

            <a href="services/indigency.html" class="svc-card">
              <div class="svc-icon" style="background:#f0e8ff; color:#7c3aed;">
                <i class="bi bi-people-fill"></i>
              </div>
              <div class="svc-info">
                <div class="svc-name">Indigency Certificate</div>
                <div class="svc-desc">For scholarship, medical, and legal aid</div>
                <div class="svc-meta">
                  <span class="svc-fee">Free</span>
                  <span class="svc-days"><i class="bi bi-clock"></i> Same day</span>
                </div>
              </div>
              <i class="bi bi-arrow-right svc-arrow"></i>
            </a>

            <a href="services/rpt-tax.html" class="svc-card">
              <div class="svc-icon" style="background:#e8f5e8; color:#16a34a;">
                <i class="bi bi-cash-coin"></i>
              </div>
              <div class="svc-info">
                <div class="svc-name">Real Property Tax</div>
                <div class="svc-desc">Pay annual property tax online</div>
                <div class="svc-meta">
                  <span class="svc-fee">Varies</span>
                  <span class="svc-days"><i class="bi bi-clock"></i> Instant</span>
                </div>
              </div>
              <i class="bi bi-arrow-right svc-arrow"></i>
            </a>

            <a href="services/scholarship.html" class="svc-card">
              <div class="svc-icon" style="background:#e8f3fc; color:#0369a1;">
                <i class="bi bi-mortarboard-fill"></i>
              </div>
              <div class="svc-info">
                <div class="svc-name">Scholarship Application</div>
                <div class="svc-desc">LGU-funded education assistance</div>
                <div class="svc-meta">
                  <span class="svc-fee">Free</span>
                  <span class="svc-days"><i class="bi bi-clock"></i> 5–7 days</span>
                </div>
              </div>
              <i class="bi bi-arrow-right svc-arrow"></i>
            </a>

            <a href="services/appointments.html" class="svc-card">
              <div class="svc-icon" style="background:#fef3c7; color:#b45309;">
                <i class="bi bi-calendar-heart-fill"></i>
              </div>
              <div class="svc-info">
                <div class="svc-name">Book Appointment</div>
                <div class="svc-desc">Schedule visits to LGU offices</div>
                <div class="svc-meta">
                  <span class="svc-fee">Free</span>
                  <span class="svc-days"><i class="bi bi-clock"></i> Instant</span>
                </div>
              </div>
              <i class="bi bi-arrow-right svc-arrow"></i>
            </a>

          </div>
        </div>

        <!-- Right column -->
        <div class="home-right">

          <!-- My recent requests -->
          <div class="r-card">
            <div class="r-card-header">
              <div class="r-card-title">
                <i class="bi bi-clock-history"></i> My Recent Requests
              </div>
              <a href="resident-requests.php" class="r-card-link">View all</a>
            </div>
            <div class="r-card-body">
              <div class="request-list">

                <div class="request-item">
                  <div class="req-left">
                    <div class="req-dot" style="background:#f59e0b;"></div>
                    <div class="req-info">
                      <div class="req-name">Barangay Clearance</div>
                      <div class="req-id">#2026-04-0841</div>
                    </div>
                  </div>
                  <span class="req-status pending">Pending</span>
                </div>

                <div class="request-item">
                  <div class="req-left">
                    <div class="req-dot" style="background:#1a7fd4;"></div>
                    <div class="req-info">
                      <div class="req-name">Cedula / CTC</div>
                      <div class="req-id">#2026-03-0712</div>
                    </div>
                  </div>
                  <span class="req-status processing">Processing</span>
                </div>

                <div class="request-item">
                  <div class="req-left">
                    <div class="req-dot" style="background:#1a9e5f;"></div>
                    <div class="req-info">
                      <div class="req-name">Health Certificate</div>
                      <div class="req-id">#2026-02-0589</div>
                    </div>
                  </div>
                  <span class="req-status done">Completed</span>
                </div>

              </div>
            </div>
          </div>

          <!-- Announcements -->
          <div class="r-card">
            <div class="r-card-header">
              <div class="r-card-title">
                <i class="bi bi-megaphone-fill"></i> Announcements
              </div>
            </div>
            <div class="r-card-body">
              <div class="announce-feed">

                <div class="af-item">
                  <div class="af-badge" style="background:#fef3c7; color:#d97706;">
                    <i class="bi bi-clock-fill"></i>
                  </div>
                  <div class="af-body">
                    <div class="af-title">Extended Office Hours</div>
                    <div class="af-text">Civil Registrar open until 7 PM on weekdays starting May 1.</div>
                    <div class="af-date">Apr 21, 2026</div>
                  </div>
                </div>

                <div class="af-item">
                  <div class="af-badge" style="background:#e8f3fc; color:#1a7fd4;">
                    <i class="bi bi-info-circle-fill"></i>
                  </div>
                  <div class="af-body">
                    <div class="af-title">Free Medical Mission</div>
                    <div class="af-text">Barangay Hall, April 28 — free check-up and medicines.</div>
                    <div class="af-date">Apr 18, 2026</div>
                  </div>
                </div>

                <div class="af-item">
                  <div class="af-badge" style="background:#e6f7ef; color:#1a9e5f;">
                    <i class="bi bi-mortarboard-fill"></i>
                  </div>
                  <div class="af-body">
                    <div class="af-title">Scholarship Applications Open</div>
                    <div class="af-text">SY 2026–2027 applications now accepted until May 15.</div>
                    <div class="af-date">Apr 15, 2026</div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- Help card -->
          <div class="r-card help-card">
            <div class="help-icon"><i class="bi bi-headset"></i></div>
            <div class="help-body">
              <div class="help-title">Need help?</div>
              <div class="help-desc">Call <strong>(088) 123-4567</strong> or visit the Municipal Hall, Mon–Fri 8AM–5PM.</div>
            </div>
          </div>

        </div>

      </div>

    </main>

  </div>

  <!-- Mobile overlay -->
  <div class="r-overlay" id="rOverlay"></div>

  <script src="js/resident-home.js"></script>

</body>
</html>