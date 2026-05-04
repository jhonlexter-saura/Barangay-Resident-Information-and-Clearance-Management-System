<?php
session_start();
require 'config.php';
require 'aut.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    header('Location: resident-portal.php');
    exit();
}

$uid = $_SESSION['user_id'];

// ── Fetch user ────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM resident WHERE resident_id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: resident-portal.php');
    exit();
}

// ── Stat card queries ─────────────────────────────────────────────────────────

// Pending requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM service_request
    WHERE resident_id = ? AND status = 'Pending'
");
$stmt->execute([$uid]);
$pending_count = (int) $stmt->fetchColumn();

// Completed (Released) requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM service_request
    WHERE resident_id = ? AND status = 'Released'
");
$stmt->execute([$uid]);
$completed_count = (int) $stmt->fetchColumn();

// Unpaid balance — sum of pending payments
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(p.amount), 0)
    FROM payment p
    JOIN service_request sr ON sr.request_id = p.request_id
    WHERE sr.resident_id = ? AND p.payment_status = 'Pending'
");
$stmt->execute([$uid]);
$unpaid_balance = (float) $stmt->fetchColumn();

// Upcoming appointments — pending 'Book an Appointment' requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM service_request sr
    JOIN service_request_detail srd ON srd.request_id = sr.request_id
    WHERE sr.resident_id = ?
    AND sr.document_type = 'Book an Appointment'
    AND sr.status IN ('Pending', 'Processing')
    AND srd.field_key = 'appt_date'
    AND srd.field_value >= CURDATE()
");
$stmt->execute([$uid]);
$upcoming_appointments = (int) $stmt->fetchColumn();

// ── Recent requests (last 3) ──────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT request_id, document_type, status, date_requested
    FROM service_request
    WHERE resident_id = ?
    ORDER BY date_requested DESC, request_id DESC
    LIMIT 3
");
$stmt->execute([$uid]);
$recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Convenience variables ─────────────────────────────────────────────────────
$firstname  = htmlspecialchars($user['first_name']);
$lastname   = htmlspecialchars($user['last_name']);
$fullName   = $firstname . ' ' . $lastname;
$initials   = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$residentId = 'RES-' . str_pad($user['resident_id'], 5, '0', STR_PAD_LEFT);

$hour = (int) date('G');
if ($hour < 12)     $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else                $greeting = 'Good evening';

require 'res-sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/resident-home.css" rel="stylesheet">
</head>

<body>


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
          <span class="r-tb-name">KALASUNGAY</span>
        </div>
      </div>

      <div class="r-topbar-right">
        <a href="resident-notifications.php">
          <button class="r-topbar-btn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="r-notif-dot"></span>
        </button>
        </a>
        
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
            <span class="sc-value"><?= $pending_count ?></span>
            <span class="sc-label">Pending Requests</span>
          </div>
        </div>

        <div class="status-card">
          <div class="sc-icon" style="background:#e6f7ef; color:#1a9e5f;">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="sc-body">
            <span class="sc-value"><?= $completed_count ?></span>
            <span class="sc-label">Completed</span>
          </div>
        </div>

        <div class="status-card">
          <div class="sc-icon" style="background:#fef3c7; color:#d97706;">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="sc-body">
            <span class="sc-value">
              <?= $unpaid_balance > 0 ? '₱' . number_format($unpaid_balance, 2) : '₱0.00' ?>
            </span>
            <span class="sc-label">Unpaid Balance</span>
          </div>
        </div>

        <div class="status-card">
          <div class="sc-icon" style="background:#f0e8ff; color:#7c3aed;">
            <i class="bi bi-calendar-event"></i>
          </div>
          <div class="sc-body">
            <span class="sc-value"><?= $upcoming_appointments ?></span>
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

            <a href="services/barangay-clearance.php" class="svc-card">
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

            <a href="services/cedula.php" class="svc-card">
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

            <a href="services/health-cert.php" class="svc-card">
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

            <a href="services/indigency.php" class="svc-card">
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

            <a href="services/rpt-tax.php" class="svc-card">
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

            <a href="services/scholarship.php" class="svc-card">
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

            <a href="services/appointments.php" class="svc-card">
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
          <div class="r-card-body">
            <div class="request-list">

              <?php if (empty($recent_requests)): ?>
                <div style="text-align:center; padding:1.5rem 0; font-size:0.82rem; color:var(--text-muted);">
                  <i class="bi bi-folder2-open" style="font-size:1.5rem; display:block; margin-bottom:0.5rem;"></i>
                  No requests yet.
                </div>
              <?php else: ?>
                <?php
                $dotColors = [
                  'Pending'         => '#f59e0b',
                  'Processing'      => '#1a7fd4',
                  'Ready for Pickup'=> '#7c3aed',
                  'Released'        => '#1a9e5f',
                  'Denied'          => '#dc2626',
                  'Cancelled'       => '#9ca3af',
                ];
                $badgeClass = [
                  'Pending'         => 'pending',
                  'Processing'      => 'processing',
                  'Ready for Pickup'=> 'processing',
                  'Released'        => 'done',
                  'Denied'          => 'denied',
                  'Cancelled'       => 'denied',
                ];
                foreach ($recent_requests as $req):
                  $refNo  = 'REQ-' . date('Y', strtotime($req['date_requested']))
                          . '-' . str_pad($req['request_id'], 6, '0', STR_PAD_LEFT);
                  $dot    = $dotColors[$req['status']]  ?? '#9ca3af';
                  $badge  = $badgeClass[$req['status']] ?? 'pending';
                ?>
                <div class="request-item">
                  <div class="req-left">
                    <div class="req-dot" style="background:<?= $dot ?>;"></div>
                    <div class="req-info">
                      <div class="req-name"><?= htmlspecialchars($req['document_type']) ?></div>
                      <div class="req-id">#<?= $refNo ?></div>
                    </div>
                  </div>
                  <span class="req-status <?= $badge ?>"><?= htmlspecialchars($req['status']) ?></span>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>

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