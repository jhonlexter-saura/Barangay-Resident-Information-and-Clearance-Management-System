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

function initials($first, $last) {
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

$stmt = $pdo->query("SELECT COUNT(*) FROM notification WHERE is_read = 0");
$unreadNotifs = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM service_request WHERE status IN ('Pending','Processing','Ready for Pickup')");
$pendingRequests = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT * FROM notification ORDER BY created_at DESC");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

function notifIcon($type) {
    return match($type) {
        'request'      => ['bi-file-earmark-check-fill', '#e8f3fc', '#1a7fd4'],
        'announcement' => ['bi-megaphone-fill',           '#e6f7ef', '#1a9e5f'],
        'payment'      => ['bi-cash-coin',                '#fde8e8', '#dc2626'],
        'system'       => ['bi-gear-fill',                '#f1f5f9', '#64748b'],
        default        => ['bi-bell-fill',                '#e8f3fc', '#1a7fd4'],
    };
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 3600)  return round($diff / 60) . ' minutes ago';
    if ($diff < 86400) return round($diff / 3600) . ' hours ago';
    if ($diff < 604800) return round($diff / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Staff Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/shared.css" rel="stylesheet">
  <link href="../css/dashboard.css" rel="stylesheet">
</head>
<body>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-logo">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="18" cy="18" r="16" fill="none" stroke="rgba(201,168,76,0.6)" stroke-width="1.5"/>
          <circle cx="18" cy="18" r="11" fill="rgba(201,168,76,0.1)"/>
          <polygon points="18,7 19.8,13 26,13 21,16.8 23,23 18,19.5 13,23 15,16.8 10,13 16.2,13" fill="rgba(201,168,76,0.9)"/>
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
    <nav class="sidebar-nav">

      <div class="nav-section-label">Main</div>

      <a href="staff-dashboard.php" class="nav-item" data-tooltip="Dashboard">
        <i class="bi bi-grid-fill nav-icon"></i>
        <span class="nav-label">Dashboard</span>
      </a>

      <a href="staff-requests.php" class="nav-item" data-tooltip="Requests">
        <i class="bi bi-file-earmark-text nav-icon"></i>
        <span class="nav-label">Requests</span>
        <span class="nav-badge"><?= number_format($pendingRequests) ?></span>
      </a>

      <a href="staff-notifications.php" class="nav-item active" data-tooltip="Notifications">
        <i class="bi bi-bell nav-icon"></i>
        <span class="nav-label">Notifications</span>
        <span class="nav-badge"><?= number_format($unreadNotifs) ?></span>
      </a>

      <div class="nav-divider"></div>
      <div class="nav-section-label">Operations</div>

      <a href="staff-settings.php" class="nav-item" data-tooltip="Settings">
        <i class="bi bi-gear nav-icon"></i>
        <span class="nav-label">Settings</span>
      </a>

      <a href="staff-help.php" class="nav-item" data-tooltip="Help & Support">
        <i class="bi bi-question-circle nav-icon"></i>
        <span class="nav-label">Help & Support</span>
      </a>

    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar"><?= htmlspecialchars(initials($user['first_name'] ?? 'S', $user['last_name'] ?? 'T')) ?></div>
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars(trim(($user['first_name'] ?? 'Staff') . ' ' . ($user['last_name'] ?? 'User'))) ?></span>
          <span class="user-role"><?= htmlspecialchars($user['role_position'] ?? 'Administrator') ?></span>
        </div>
        <button class="user-logout" title="Sign out" onclick="location.href='staff-logout.php'">
          <i class="bi bi-box-arrow-right"></i>
        </button>
      </div>
    </div>
  </aside>
  <div class="main-area" id="mainArea">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
          <i class="bi bi-list"></i>
        </button>
        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
          <span class="breadcrumb-item">
            <i class="bi bi-bell"></i> Notifications
          </span>
        </nav>
      </div>
      <div class="topbar-center">
        <div class="topbar-search">
          <i class="bi bi-search search-icon"></i>
          <input type="text" class="search-input" placeholder="Search notifications…">
          <kbd class="search-kbd">⌘K</kbd>
        </div>
      </div>
      <div class="topbar-right">
        <button class="topbar-btn notif-btn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="notif-count"><?= number_format($unreadNotifs) ?></span>
        </button>
        <div class="topbar-profile">
          <div class="profile-avatar"><?= htmlspecialchars(initials($user['first_name'] ?? 'S', $user['last_name'] ?? 'T')) ?></div>
          <div class="profile-info">
            <span class="profile-name"><?= htmlspecialchars(trim(($user['first_name'] ?? 'Staff') . ' ' . ($user['last_name'] ?? 'User'))) ?></span>
            <span class="profile-dept"><?= htmlspecialchars($user['role_position'] ?? 'Administrator') ?></span>
          </div>
          <i class="bi bi-chevron-down profile-chevron"></i>
        </div>
      </div>
    </header>
    <main class="page-content">
      <div class="content-header">
        <div class="content-header-left">
          <h1 class="page-title">Notifications</h1>
          <p class="page-subtitle">Track system alerts, resident messages, and request updates.</p>
        </div>
        <div class="content-header-right">
          <button class="btn-outline-nav">
            <i class="bi bi-download"></i> Export
          </button>
          <button class="btn-outline-nav"><i class="bi bi-check-all"></i> Mark all read</button>
        </div>
      </div>
      <div class="dash-card dash-card-wide">
        <div class="dash-card-header">
          <div class="dash-card-title"><i class="bi bi-bell-fill"></i> Recent Alerts</div>
          <a href="staff-notifications.php" class="dash-card-link">Refresh <i class="bi bi-arrow-clockwise"></i></a>
        </div>
        <div class="dash-card-body">
          <div class="notification-list">
            <div class="notification-item p-3 mb-3 rounded-3 border">
              <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                  <div class="h6 mb-1">New request submitted</div>
                  <p class="text-muted mb-1">A Barangay Clearance request was filed by Marites Santos.</p>
                  <small class="text-muted">5 minutes ago</small>
                </div>
                <span class="badge bg-warning text-dark">Unread</span>
              </div>
            </div>
            <div class="notification-item p-3 mb-3 rounded-3 border bg-gray-50">
              <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                  <div class="h6 mb-1">Document checklist updated</div>
                  <p class="text-muted mb-1">Health Certificate requirements were revised for new applicants.</p>
                  <small class="text-muted">35 minutes ago</small>
                </div>
                <span class="badge bg-secondary">Read</span>
              </div>
            </div>
            <div class="notification-item p-3 mb-3 rounded-3 border">
              <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                  <div class="h6 mb-1">System maintenance scheduled</div>
                  <p class="text-muted mb-1">The portal will be unavailable on Apr 30, 10PM–12AM.</p>
                  <small class="text-muted">1 hour ago</small>
                </div>
                <span class="badge bg-warning text-dark">Unread</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="dashboard-grid">
        <div class="dash-card">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-exclamation-circle"></i> System Alerts</div>
          </div>
          <div class="dash-card-body">
            <ul class="list-unstyled">
              <li class="py-2 border-bottom"><strong>Reminder:</strong> Submit monthly clearance report by April 30.</li>
              <li class="py-2 border-bottom"><strong>Update:</strong> New resident approval flow is live.</li>
              <li class="py-2"><strong>Note:</strong> Verify documents before final approval.</li>
            </ul>
          </div>
        </div>
        <div class="dash-card">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-graph-up-arrow"></i> Notification Summary</div>
          </div>
          <div class="dash-card-body">
            <div class="row text-center">
              <div class="col-6">
                <div class="stat-value">8</div>
                <div class="stat-label">Total</div>
              </div>
              <div class="col-6">
                <div class="stat-value">3</div>
                <div class="stat-label">Unread</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <script src="../js/dashboard.js"></script>
</body>
</html>


