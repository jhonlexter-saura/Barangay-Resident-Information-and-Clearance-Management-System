<?php
require '../config.php';
require '../aut.php';

$user = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare(
        "SELECT bo.*, r.first_name, r.last_name
         FROM barangay_official bo
         JOIN resident r ON bo.resident_id = r.resident_id
         WHERE bo.user_id = ?"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

$now = new DateTimeImmutable();
$currentMonth = (int) $now->format('n');
$currentYear  = (int) $now->format('Y');

$pendingRequests = (int) $pdo->query("SELECT COUNT(*) FROM service_request WHERE status = 'Pending'")->fetchColumn();
$unprocessedRequests = (int) $pdo->query("SELECT COUNT(*) FROM service_request WHERE status IN ('Pending','Processing')")->fetchColumn();
$unreadNotifs = (int) $pdo->query("SELECT COUNT(*) FROM notification WHERE is_read = 0")->fetchColumn();

$processedStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM service_request
     WHERE status <> 'Pending'
       AND MONTH(updated_at) = ?
       AND YEAR(updated_at) = ?"
);
$processedStmt->execute([$currentMonth, $currentYear]);
$processedThisMonth = (int) $processedStmt->fetchColumn();

$registeredResidents = (int) $pdo->query("SELECT COUNT(*) FROM resident")->fetchColumn();

$revenueStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM payment
     WHERE payment_status = 'Paid'
       AND MONTH(payment_date) = ?
       AND YEAR(payment_date) = ?"
);
$revenueStmt->execute([$currentMonth, $currentYear]);
$revenueThisMonth = (float) $revenueStmt->fetchColumn();

$recentRequestsStmt = $pdo->query(
    "SELECT sr.request_id, sr.document_type, sr.status,
            sr.date_requested, r.first_name, r.last_name
     FROM service_request sr
     JOIN resident r ON sr.resident_id = r.resident_id
     ORDER BY sr.created_at DESC
     LIMIT 5"
);
$recentRequests = $recentRequestsStmt->fetchAll();

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function requestStatusClass($status) {
    if ($status === 'Pending') return 'pending';
    if ($status === 'Processing') return 'processing';
    if ($status === 'Ready for Pickup' || $status === 'Released') return 'approved';
    if ($status === 'Denied' || $status === 'Cancelled') return 'rejected';
    return 'pending';
}

function initials($first, $last) {
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/shared.css" rel="stylesheet">
  <link href="../css/dashboard.css" rel="stylesheet">
</head>

<body>

  <!-- ── Sidebar ── -->
  <aside class="sidebar" id="sidebar">

    <!-- Sidebar brand -->
    <div class="sidebar-brand">
      <div class="sidebar-logo">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="18" cy="18" r="16" fill="none" stroke="rgba(201,168,76,0.6)" stroke-width="1.5"/>
          <circle cx="18" cy="18" r="11" fill="rgba(201,168,76,0.1)"/>
          <polygon points="18,7 19.8,13 26,13 21,16.8 23,23 18,19.5 13,23 15,16.8 10,13 16.2,13"
                   fill="rgba(201,168,76,0.9)"/>
        </svg>
      </div>
      <div class="sidebar-brand-text">
        <span class="sidebar-brand-name">KALASUNGAY</span>
        <span class="sidebar-brand-sub">Staff Portal</span>
      </div>
      <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="Collapse sidebar">
        <i class="bi bi-layout-sidebar-reverse"></i>
      </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

      <div class="nav-section-label">Main</div>

      <a href="#" class="nav-item active" data-tooltip="Dashboard">
        <i class="bi bi-grid-fill nav-icon"></i>
        <span class="nav-label">Dashboard</span>
      </a>

      <a href="staff-requests.php" class="nav-item" data-tooltip="Requests">
        <i class="bi bi-file-earmark-text nav-icon"></i>
        <span class="nav-label">Requests</span>
        <span class="nav-badge"><?= number_format($unprocessedRequests) ?></span>
      </a>

      <a href="staff-notifications.php" class="nav-item" data-tooltip="Notifications">
        <i class="bi bi-bell nav-icon"></i>
        <span class="nav-label">Notifications</span>
        <span class="nav-badge"><?= number_format($unreadNotifs) ?></span>
      </a>

      <div class="nav-divider"></div>
      <div class="nav-section-label">Operations</div>

      <a href="staff-manage.php" class="nav-item" data-tooltip="Officials">
        <i class="bi bi-person-badge nav-icon"></i>
        <span class="nav-label">Officials</span>
      </a>

      <a href="staff-settings.php" class="nav-item" data-tooltip="Settings">
        <i class="bi bi-gear nav-icon"></i>
        <span class="nav-label">Settings</span>
      </a>

      <a href="staff-help.php" class="nav-item" data-tooltip="Help & Support">
        <i class="bi bi-question-circle nav-icon"></i>
        <span class="nav-label">Help & Support</span>
      </a>


    </nav>

    <!-- Sidebar footer -->
    <div class="sidebar-footer">
      <div class="user-avatar"><?= htmlspecialchars(initials($user['first_name'] ?? 'S', $user['last_name'] ?? 'T')) ?></div>
      <div class="user-info">
        <span class="user-name"><?= htmlspecialchars(trim(($user['first_name'] ?? 'Staff') . ' ' . ($user['last_name'] ?? 'User'))) ?></span>
        <span class="user-role"><?= htmlspecialchars($user['role_position'] ?? 'Administrator') ?></span>
      </div>
      <a href="staff-logout.php" class="user-logout" title="Sign out">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>

  </aside>

  <!-- ── Main area ── -->
  <div class="main-area" id="mainArea">

    <!-- ── Top bar ── -->
    <header class="topbar">

      <div class="topbar-left">
        <!-- Mobile menu toggle -->
        <button class="topbar-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
          <i class="bi bi-list"></i>
        </button>
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
          <span class="breadcrumb-item">
            <i class="bi bi-grid-fill"></i> Dashboard
          </span>
        </nav>
      </div>

      <div class="topbar-center">
        <div class="topbar-search">
          <i class="bi bi-search search-icon"></i>
          <input type="text" class="search-input" placeholder="Search residents, permits, requests…">
          <kbd class="search-kbd">⌘K</kbd>
        </div>
      </div>

      <div class="topbar-right">
        <!-- Notification bell -->
        <button class="topbar-btn notif-btn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="notif-count"><?= number_format($unreadNotifs) ?></span>
        </button>

        <!-- Profile -->
        <div class="topbar-profile">
          <div class="profile-avatar"><?= htmlspecialchars(initials($user['first_name'] ?? 'S', $user['last_name'] ?? 'T')) ?></div>
          <div class="profile-info">
            <span class="profile-name"><?= htmlspecialchars(trim(($user['first_name'] ?? 'Staff') . ' ' . ($user['last_name'] ?? 'User'))) ?></span>
            <span class="profile-dept"><?= htmlspecialchars($user['role_position'] ?? 'Staff') ?></span>
          </div>
          <i class="bi bi-chevron-down profile-chevron"></i>
        </div>
      </div>

    </header>

    <!-- ── Page content ── -->
    <main class="page-content">

      <!-- Page header -->
      <div class="content-header">
        <div class="content-header-left">
          <h1 class="page-title">Dashboard</h1>
          <p class="page-subtitle"><?= htmlspecialchars($now->format('l, F j, Y')) ?> &nbsp;·&nbsp; Municipal Hall of [Municipality]</p>
        </div>
        <div class="content-header-right">
          <button class="btn-outline-nav">
            <i class="bi bi-download"></i> Export Report
          </button>
          <a href="staff-process-request.php" class="btn-primary-nav">
            <i class="bi bi-plus-lg"></i> New Request
          </a>
        </div>
      </div>

      <!-- ── Stat cards ── -->
      <div class="stats-grid">

        <div class="stat-card">
          <div class="stat-icon" style="background:#e8f3fc; color:#1a7fd4;">
            <i class="bi bi-file-earmark-text-fill"></i>
          </div>
          <div class="stat-body">
            <span class="stat-value"><?= number_format($pendingRequests) ?></span>
            <span class="stat-label">Pending Requests</span>
          </div>
          <div class="stat-trend up">
            <i class="bi bi-arrow-up-short"></i> 12%
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:#e6f7ef; color:#1a9e5f;">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="stat-body">
            <span class="stat-value"><?= number_format($processedThisMonth) ?></span>
            <span class="stat-label">Processed This Month</span>
          </div>
          <div class="stat-trend up">
            <i class="bi bi-arrow-up-short"></i> 8%
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:#fef3c7; color:#d97706;">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="stat-body">
            <span class="stat-value"><?= number_format($registeredResidents) ?></span>
            <span class="stat-label">Registered Residents</span>
          </div>
          <div class="stat-trend neutral">
            <i class="bi bi-dash"></i> 0%
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:#f0e8ff; color:#7c3aed;">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div class="stat-body">
            <span class="stat-value"><?= htmlspecialchars(formatCurrency($revenueThisMonth)) ?></span>
            <span class="stat-label">Revenue This Month</span>
          </div>
          <div class="stat-trend up">
            <i class="bi bi-arrow-up-short"></i> 24%
          </div>
        </div>

      </div>

      <!-- ── Main grid ── -->
      <div class="dashboard-grid">

        <!-- Recent requests table -->
        <div class="dash-card dash-card-wide">
          <div class="dash-card-header">
            <div class="dash-card-title">
              <i class="bi bi-file-earmark-text"></i>
              Recent Requests
            </div>
            <a href="staff-requests.php" class="dash-card-link">View all <i class="bi bi-arrow-right"></i></a>
          </div>
          <div class="dash-card-body">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Resident</th>
                  <th>Type</th>
                  <th>Date Filed</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($recentRequests)): ?>
                  <?php foreach ($recentRequests as $req): ?>
                    <?php
                      $residentName = trim($req['first_name'] . ' ' . $req['last_name']);
                      $avatar = initials($req['first_name'], $req['last_name']);
                      $statusClass = requestStatusClass($req['status']);
                    ?>
                    <tr>
                      <td>
                        <div class="table-resident">
                          <div class="table-avatar"><?= htmlspecialchars($avatar) ?></div>
                          <div>
                            <div class="table-name"><?= htmlspecialchars($residentName) ?></div>
                            <div class="table-id">#<?= htmlspecialchars(str_pad($req['request_id'], 5, '0', STR_PAD_LEFT)) ?></div>
                          </div>
                        </div>
                      </td>
                      <td><span class="doc-type"><?= htmlspecialchars($req['document_type']) ?></span></td>
                      <td class="table-date"><?= htmlspecialchars(date('M j, Y', strtotime($req['date_requested']))) ?></td>
                      <td><span class="status-badge <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                      <td><a href="staff-process-request.php?request_id=<?= urlencode($req['request_id']) ?>" class="action-btn">Review</a></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center">No recent requests found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right column -->
        <div class="dash-right-col">

          <!-- Quick actions -->
          <div class="dash-card">
            <div class="dash-card-header">
              <div class="dash-card-title">
                <i class="bi bi-lightning-charge"></i>
                Quick Actions
              </div>
            </div>
            <div class="dash-card-body">
              <div class="quick-actions">
                <button class="quick-action-btn">
                  <div class="qa-icon" style="background:#e8f3fc; color:#1a7fd4;">
                    <i class="bi bi-file-earmark-plus"></i>
                  </div>
                  <span>New Request</span>
                </button>
                <button class="quick-action-btn">
                  <div class="qa-icon" style="background:#e6f7ef; color:#1a9e5f;">
                    <i class="bi bi-person-plus"></i>
                  </div>
                  <span>Add Resident</span>
                </button>
                <button class="quick-action-btn">
                  <div class="qa-icon" style="background:#fef3c7; color:#d97706;">
                    <i class="bi bi-receipt"></i>
                  </div>
                  <span>Issue Permit</span>
                </button>
                <button class="quick-action-btn">
                  <div class="qa-icon" style="background:#f0e8ff; color:#7c3aed;">
                    <i class="bi bi-printer"></i>
                  </div>
                  <span>Print Report</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Announcements -->
          <div class="dash-card">
            <div class="dash-card-header">
              <div class="dash-card-title">
                <i class="bi bi-megaphone"></i>
                Announcements
              </div>
              <a href="#" class="dash-card-link">Post <i class="bi bi-plus"></i></a>
            </div>
            <div class="dash-card-body">
              <div class="announce-list">
                <div class="announce-item">
                  <div class="announce-dot" style="background:#1a7fd4;"></div>
                  <div class="announce-content">
                    <div class="announce-title">Office Hours Extended</div>
                    <div class="announce-meta">Today · Civil Registrar</div>
                  </div>
                </div>
                <div class="announce-item">
                  <div class="announce-dot" style="background:#d97706;"></div>
                  <div class="announce-content">
                    <div class="announce-title">System Maintenance — Apr 25</div>
                    <div class="announce-meta">2 days ago · ICT Office</div>
                  </div>
                </div>
                <div class="announce-item">
                  <div class="announce-dot" style="background:#1a9e5f;"></div>
                  <div class="announce-content">
                    <div class="announce-title">Q1 Reports Submitted</div>
                    <div class="announce-meta">3 days ago · Finance</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- System status -->
          <div class="dash-card">
            <div class="dash-card-header">
              <div class="dash-card-title">
                <i class="bi bi-activity"></i>
                System Status
              </div>
            </div>
            <div class="dash-card-body">
              <div class="status-list">
                <div class="status-row">
                  <span class="status-name">Portal API</span>
                  <span class="status-pill green"><span class="secure-dot"></span> Online</span>
                </div>
                <div class="status-row">
                  <span class="status-name">Database</span>
                  <span class="status-pill green"><span class="secure-dot"></span> Online</span>
                </div>
                <div class="status-row">
                  <span class="status-name">Payment Gateway</span>
                  <span class="status-pill yellow"><span class="secure-dot" style="background:#d97706; box-shadow:0 0 5px #d97706;"></span> Degraded</span>
                </div>
                <div class="status-row">
                  <span class="status-name">Email Service</span>
                  <span class="status-pill green"><span class="secure-dot"></span> Online</span>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

    </main>

  </div>

  <!-- Mobile sidebar overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <script src="../js/dashboard.js"></script>

</body>
</html> 


