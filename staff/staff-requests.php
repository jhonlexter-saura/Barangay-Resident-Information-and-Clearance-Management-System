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

$pendingRequests = (int) $pdo->query("SELECT COUNT(*) FROM service_request WHERE status = 'Pending'")->fetchColumn();
$completedTodayStmt = $pdo->prepare("SELECT COUNT(*) FROM service_request WHERE status = 'Released' AND DATE(date_issued) = ?");
$completedTodayStmt->execute([date('Y-m-d')]);
$completedToday = (int) $completedTodayStmt->fetchColumn();
$registeredResidents = (int) $pdo->query("SELECT COUNT(*) FROM resident")->fetchColumn();
$awaitingApproval = (int) $pdo->query("SELECT COUNT(*) FROM service_request WHERE status IN ('Pending','Processing')")->fetchColumn();
$unreadNotifs = (int) $pdo->query("SELECT COUNT(*) FROM notification WHERE is_read = 0")->fetchColumn();

$activeRequestsStmt = $pdo->query(
    "SELECT sr.request_id, sr.document_type, sr.status, sr.date_requested, r.first_name, r.last_name
     FROM service_request sr
     JOIN resident r ON sr.resident_id = r.resident_id
     ORDER BY sr.date_requested DESC, sr.request_id DESC
     LIMIT 20"
);
$activeRequests = $activeRequestsStmt->fetchAll(PDO::FETCH_ASSOC);

$breakdownStmt = $pdo->query("SELECT document_type, COUNT(*) AS total FROM service_request GROUP BY document_type ORDER BY total DESC");
$documentBreakdown = $breakdownStmt->fetchAll(PDO::FETCH_ASSOC);

$recentResidentsStmt = $pdo->query(
    "SELECT sr.request_id, sr.document_type, sr.status, sr.date_requested, r.first_name, r.last_name
     FROM service_request sr
     JOIN resident r ON sr.resident_id = r.resident_id
     ORDER BY sr.date_requested DESC, sr.request_id DESC
     LIMIT 3"
);
$recentResidents = $recentResidentsStmt->fetchAll(PDO::FETCH_ASSOC);

function requestPriorityLabel($status) {
    return match($status) {
        'Pending'         => 'High',
        'Processing'      => 'Normal',
        'Ready for Pickup'=> 'High',
        'Released'        => 'Low',
        'Denied'          => 'Low',
        'Cancelled'       => 'Low',
        default           => 'Normal',
    };
}

function requestStatusClass($status) {
    return match($status) {
        'Pending'         => 'warning',
        'Processing'      => 'info',
        'Ready for Pickup'=> 'success',
        'Released'        => 'success',
        'Denied'          => 'danger',
        'Cancelled'       => 'secondary',
        default           => 'secondary',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Staff Requests</title>
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

      <a href="staff-requests.php" class="nav-item active" data-tooltip="Requests">
        <i class="bi bi-file-earmark-text nav-icon"></i>
        <span class="nav-label">Requests</span>
        <span class="nav-badge"><?= number_format($pendingRequests) ?></span>
      </a>

      <a href="staff-notifications.php" class="nav-item" data-tooltip="Notifications">
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
            <i class="bi bi-file-earmark-text"></i> Requests
          </span>
        </nav>
      </div>
      <div class="topbar-center">
        <div class="topbar-search">
          <i class="bi bi-search search-icon"></i>
          <input type="text" class="search-input" placeholder="Search requests, residents, statuses…">
          <kbd class="search-kbd">⌘K</kbd>
        </div>
      </div>
      <div class="topbar-right">
        <button class="topbar-btn notif-btn" aria-label="Notifications" onclick="location.href='staff-notifications.php'">
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
          <h1 class="page-title">Resident Requests</h1>
          <p class="page-subtitle">Review all resident service requests and process them quickly.</p>
        </div>
        <div class="content-header-right">
          <button class="btn-outline-nav">
            <i class="bi bi-download"></i> Export
          </button>
          <button class="btn-outline-nav">
            <i class="bi bi-funnel"></i> Filter
          </button>
          <button class="btn-primary-nav">
            <i class="bi bi-plus-lg"></i> New Request
          </button>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon" style="background:#e8f3fc; color:#1a7fd4;"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-body"><span class="stat-value"><?= number_format($pendingRequests) ?></span><span class="stat-label">Pending Requests</span></div>
          <div class="stat-trend warning"><i class="bi bi-arrow-down-short"></i> 3%</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#e6f7ef; color:#1a9e5f;"><i class="bi bi-check-circle-fill"></i></div>
          <div class="stat-body"><span class="stat-value"><?= number_format($completedToday) ?></span><span class="stat-label">Completed Today</span></div>
          <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> 14%</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-people-fill"></i></div>
          <div class="stat-body"><span class="stat-value"><?= number_format($registeredResidents) ?></span><span class="stat-label">Registered Residents</span></div>
          <div class="stat-trend neutral"><i class="bi bi-dash"></i> 0%</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#f0e8ff; color:#7c3aed;"><i class="bi bi-calendar-check-fill"></i></div>
          <div class="stat-body"><span class="stat-value"><?= number_format($awaitingApproval) ?></span><span class="stat-label">Awaiting Approval</span></div>
          <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> 18%</div>
        </div>
      </div>
      <div class="dash-card dash-card-wide">
        <div class="dash-card-header">
          <div class="dash-card-title"><i class="bi bi-file-earmark-text"></i> Active Requests</div>
          <a href="staff-requests.php" class="dash-card-link">Refresh list <i class="bi bi-arrow-clockwise"></i></a>
        </div>
        <div class="dash-card-body">
          <table class="data-table">
            <thead>
              <tr>
                <th>Resident</th>
                <th>Request Type</th>
                <th>Date Filed</th>
                <th>Priority</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php if (!empty($activeRequests)): ?>
              <?php foreach ($activeRequests as $req):
                $residentName = htmlspecialchars(trim($req['first_name'] . ' ' . $req['last_name']));
                $priority = requestPriorityLabel($req['status']);
                $statusClass = requestStatusClass($req['status']);
                $actionText = in_array($req['status'], ['Released', 'Denied', 'Cancelled'], true) ? 'View' : 'Process';
              ?>
              <tr>
                <td><?= $residentName ?></td>
                <td><?= htmlspecialchars($req['document_type']) ?></td>
                <td><?= htmlspecialchars(date('M j, Y', strtotime($req['date_requested']))) ?></td>
                <td><?= htmlspecialchars($priority) ?></td>
                <td><span class="badge bg-<?= $statusClass ?> <?= $statusClass === 'info' ? 'text-dark' : '' ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                <td><a href="staff-process-request.php?request_id=<?= urlencode($req['request_id']) ?>" class="btn btn-outline-primary btn-sm"><?= $actionText ?></a></td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center">No active requests found.</td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="dashboard-grid">
        <div class="dash-card">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-list-check"></i> Request Breakdown</div>
          </div>
          <div class="dash-card-body">
            <ul class="list-unstyled">
              <?php if (!empty($documentBreakdown)): ?>
                <?php foreach ($documentBreakdown as $break): ?>
                  <li class="d-flex justify-content-between align-items-center py-2<?= $break === end($documentBreakdown) ? '' : ' border-bottom' ?>">
                    <span><?= htmlspecialchars($break['document_type']) ?></span>
                    <strong><?= number_format($break['total']) ?></strong>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="py-2">No request breakdown available.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        <div class="dash-card">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-people-fill"></i> Recent Residents</div>
          </div>
          <div class="dash-card-body">
            <div class="recent-list">
              <?php if (!empty($recentResidents)): ?>
                <?php foreach ($recentResidents as $resident):
                  $statusClass = requestStatusClass($resident['status']);
                ?>
                <div class="recent-item">
                  <div>
                    <div class="recent-title"><?= htmlspecialchars(trim($resident['first_name'] . ' ' . $resident['last_name'])) ?></div>
                    <div class="recent-meta"><?= htmlspecialchars(date('m/d', strtotime($resident['date_requested']))) ?> • <?= htmlspecialchars($resident['document_type']) ?></div>
                  </div>
                  <span class="badge bg-<?= $statusClass ?> <?= $statusClass === 'info' ? 'text-dark' : '' ?>"><?= htmlspecialchars($resident['status']) ?></span>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="recent-item">No recent residents found.</div>
              <?php endif; ?>
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


