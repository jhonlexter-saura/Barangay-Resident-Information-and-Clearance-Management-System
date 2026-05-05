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
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

function initials($first, $last) {
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

function statusClass($status) {
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

function statusLabel($status) {
    return match($status) {
        'Pending'         => 'Pending Review',
        'Processing'      => 'Need More Documents',
        'Ready for Pickup'=> 'Ready for Pickup',
        'Released'        => 'Released',
        'Denied'          => 'Denied',
        'Cancelled'       => 'Cancelled',
        default           => $status,
    };
}

function formatBytes($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $power = (int) floor(log((float) $bytes, 1024));
    return number_format((float) $bytes / (1024 ** $power), 2) . ' ' . $units[$power];
}

function buildFileUrl($storedName) {
    $pathParts = array_map('rawurlencode', explode('/', str_replace('\\', '/', $storedName)));
    return '../files/' . implode('/', $pathParts);
}

$pendingRequests = (int) $pdo->query("SELECT COUNT(*) FROM service_request WHERE status = 'Pending'")->fetchColumn();
$unprocessedRequests = (int) $pdo->query("SELECT COUNT(*) FROM service_request WHERE status IN ('Pending','Processing')")->fetchColumn();
$unreadNotifs = (int) $pdo->query("SELECT COUNT(*) FROM notification WHERE is_read = 0")->fetchColumn();

$requestId = intval($_GET['request_id'] ?? 0);
if ($requestId <= 0) {
    header('Location: staff-requests.php');
    exit();
}

$flashMessage = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $remarks = trim($_POST['remarks'] ?? '');
    $newStatus = null;
    $dateIssued = null;

    $stmt = $pdo->prepare(
        "SELECT sr.request_id, sr.status, r.first_name, r.last_name
         FROM service_request sr
         JOIN resident r ON sr.resident_id = r.resident_id
         WHERE sr.request_id = ?"
    );
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        header('Location: staff-requests.php');
        exit();
    }

    $closedStatuses = ['Released', 'Denied', 'Cancelled'];
    if (in_array($request['status'], $closedStatuses, true)) {
        $flashMessage = 'This request has already been finalized and can no longer be changed.';
        $flashType = 'danger';
    } else {
        if ($action === 'approve') {
            $newStatus = 'Ready for Pickup';
            $remarks   = $remarks ?: 'Request approved and ready for pickup.';
            $dateIssued = date('Y-m-d');
        } elseif ($action === 'deny') {
            $newStatus = 'Denied';
            $remarks   = $remarks ?: 'Request denied. See remarks for details.';
            $dateIssued = null;
        } elseif ($action === 'request_more_documents') {
            $newStatus = 'Processing';
            $remarks   = $remarks ?: 'Additional documents requested from the resident.';
            $dateIssued = null;
        } else {
            $flashMessage = 'Invalid action.';
            $flashType = 'danger';
        }

        if ($flashMessage === null && $newStatus !== null) {
            if ($newStatus === 'Ready for Pickup') {
                $updateStmt = $pdo->prepare(
                    "UPDATE service_request
                     SET status = ?, remarks = ?, date_issued = ?, updated_at = NOW()
                     WHERE request_id = ?"
                );
                $ok = $updateStmt->execute([$newStatus, $remarks, $dateIssued, $requestId]);
            } else {
                $updateStmt = $pdo->prepare(
                    "UPDATE service_request
                     SET status = ?, remarks = ?, updated_at = NOW()
                     WHERE request_id = ?"
                );
                $ok = $updateStmt->execute([$newStatus, $remarks, $requestId]);
            }

            if ($ok) {
                $flashMessage = match ($action) {
                    'approve'              => 'Request approved successfully.',
                    'deny'                 => 'Request denied successfully.',
                    'request_more_documents' => 'Requested more documents from the resident.',
                    default                => 'Request updated successfully.',
                };
                $flashType = 'success';
            } else {
                $flashMessage = 'Unable to update request. Please try again.';
                $flashType = 'danger';
            }
        }
    }

    if ($flashMessage !== null) {
        header('Location: staff-process-request.php?request_id=' . $requestId . '&message=' . urlencode($flashMessage) . '&type=' . urlencode($flashType));
        exit();
    }
}

if (!empty($_GET['message'])) {
    $flashMessage = trim($_GET['message']);
    $flashType = in_array($_GET['type'] ?? '', ['success', 'danger', 'warning', 'info'], true)
        ? $_GET['type'] : 'success';
}

$stmt = $pdo->prepare(
    "SELECT sr.*, r.first_name, r.middle_name, r.last_name,
            r.mobile_number, r.email,
            CONCAT_WS(' ', h.house_number, h.street) AS street_address,
            h.barangay, h.municipality, h.province
     FROM service_request sr
     JOIN resident r ON sr.resident_id = r.resident_id
     LEFT JOIN household_member hm ON hm.resident_id = r.resident_id
     LEFT JOIN household h ON h.household_id = hm.household_id
     WHERE sr.request_id = ?
     LIMIT 1"
);
$stmt->execute([$requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: staff-requests.php');
    exit();
}

$detailStmt = $pdo->prepare("SELECT field_key, field_value FROM service_request_detail WHERE request_id = ? ORDER BY field_key ASC");
$detailStmt->execute([$requestId]);
$requestDetails = $detailStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$fileStmt = $pdo->prepare("SELECT file_id, original_name, stored_name, file_size, mime_type, uploaded_at FROM service_request_file WHERE request_id = ? ORDER BY uploaded_at ASC");
$fileStmt->execute([$requestId]);
$requestFiles = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

$fullName = htmlspecialchars(trim($request['first_name'] . ' ' . $request['last_name']));
$residentLocation = trim(($request['street_address'] ? $request['street_address'] . ', ' : '') . ($request['barangay'] ?? '') . ', ' . ($request['municipality'] ?? ''));
$residentLocation = htmlspecialchars(trim($residentLocation, ', '));
$statusBadge = statusClass($request['status']);
$statusLabel = htmlspecialchars(statusLabel($request['status']));
$referenceNo = 'REQ-' . date('Y', strtotime($request['date_requested'])) . '-' . str_pad($request['request_id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Process Request</title>
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
        <span class="nav-badge"><?= number_format($unprocessedRequests) ?></span>
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
            <i class="bi bi-file-earmark-text"></i> Process Request
          </span>
        </nav>
      </div>
      <div class="topbar-center">
        <div class="topbar-search">
          <i class="bi bi-search search-icon"></i>
          <input type="text" class="search-input" placeholder="Search request details…">
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
            <span class="profile-dept"><?= htmlspecialchars($user['role_position'] ?? 'Records Section') ?></span>
          </div>
          <i class="bi bi-chevron-down profile-chevron"></i>
        </div>
      </div>
    </header>
    <main class="page-content">
      <div class="content-header">
        <div class="content-header-left">
          <h1 class="page-title">Process Request</h1>
          <p class="page-subtitle">Review the resident's request details and update the status.</p>
        </div>
        <div class="content-header-right">
          <a href="staff-requests.php" class="btn-outline-nav"><i class="bi bi-arrow-left"></i> Back to Requests</a>
        </div>
      </div>

      <?php if ($flashMessage): ?>
      <div class="alert alert-<?= htmlspecialchars($flashType === 'danger' ? 'danger' : ($flashType === 'warning' ? 'warning' : 'success')) ?> mb-4" role="alert">
        <?= htmlspecialchars($flashMessage) ?>
      </div>
      <?php endif; ?>

      <div class="dashboard-grid">
        <div class="dash-card dash-card-wide">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-person-lines-fill"></i> Request Overview</div>
            <div class="dash-card-actions">
              <span class="badge bg-<?= $statusBadge ?> <?= $statusBadge === 'warning' ? 'text-dark' : '' ?>"><?= $statusLabel ?></span>
            </div>
          </div>
          <div class="dash-card-body">
            <div class="row g-4">
              <div class="col-lg-8">
                <div class="mb-4">
                  <div class="h5">Resident</div>
                  <p class="mb-1"><?= $fullName ?></p>
                  <p class="text-muted mb-0"><?= $residentLocation ?></p>
                </div>

                <div class="mb-4">
                  <div class="h5">Request Details</div>
                  <p><strong>Type:</strong> <?= htmlspecialchars($request['document_type'] ?? 'Unknown') ?></p>
                  <p><strong>Reference:</strong> <?= htmlspecialchars($referenceNo) ?></p>
                  <p><strong>Date Filed:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($request['date_requested']))) ?></p>
                  <?php if (!empty($request['purpose'])): ?>
                  <p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose']) ?></p>
                  <?php endif; ?>
                  <?php if (!empty($request['remarks'])): ?>
                  <p><strong>Remarks:</strong> <?= htmlspecialchars($request['remarks']) ?></p>
                  <?php endif; ?>
                </div>

                <div class="mb-4">
                  <div class="h5">Attachments</div>
                  <?php if (!empty($requestFiles)): ?>
                  <ul class="list-group">
                    <?php foreach ($requestFiles as $file): ?>
                      <?php $fileUrl = '../services/service-handler.php?action=download_file&file_id=' . $file['file_id']; ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          <strong><?= htmlspecialchars($file['original_name']) ?></strong>
                          <div class="text-muted small"><?= htmlspecialchars(formatBytes((int) $file['file_size'])) ?> &middot; <?= htmlspecialchars(date('M j, Y', strtotime($file['uploaded_at']))) ?></div>
                        </div>
                        <a href="<?= $fileUrl ?>" class="badge bg-secondary text-decoration-none" target="_blank" rel="noopener">Download</a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <?php else: ?>
                  <div class="text-muted">No files attached to this request.</div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="col-lg-4">
                <div class="info-panel p-4 rounded-3 border">
                  <div class="mb-3">
                    <div class="h6">Contact</div>
                    <p class="mb-1"><?= htmlspecialchars($request['mobile_number'] ?? 'N/A') ?></p>
                    <p class="text-muted mb-0"><?= htmlspecialchars($request['email'] ?? 'No email address') ?></p>
                  </div>
                  <div class="mb-3">
                    <div class="h6">Request Status</div>
                    <p class="mb-1"><?= htmlspecialchars($request['status']) ?></p>
                    <p class="text-muted mb-0">Last updated: <?= htmlspecialchars(date('F j, Y', strtotime($request['updated_at'] ?? $request['date_requested']))) ?></p>
                  </div>
                  <div>
                    <div class="h6">Processing Notes</div>
                    <p class="text-muted mb-0">Use the action buttons below to approve, deny, or request missing documentation.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="dash-card">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-tools"></i> Process Actions</div>
          </div>
          <div class="dash-card-body">
            <form method="post">
              <input type="hidden" name="request_id" value="<?= htmlspecialchars($requestId) ?>">
              <div class="mb-3">
                <label for="remarks" class="form-label">Staff Note</label>
                <textarea id="remarks" name="remarks" class="form-control" rows="4" placeholder="Enter a note or instructions for the resident."><?= htmlspecialchars($request['remarks'] ?? '') ?></textarea>
              </div>
              <div class="d-grid gap-3">
                <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">Approve Request</button>
                <button type="submit" name="action" value="deny" class="btn btn-danger btn-lg">Decline Request</button>
                <button type="submit" name="action" value="request_more_documents" class="btn btn-outline-secondary btn-lg">Request More Documents</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="dashboard-grid">
        <div class="dash-card dash-card-wide">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-chat-left-text"></i> Request History</div>
          </div>
          <div class="dash-card-body">
            <div class="timeline">
              <div class="timeline-item">
                <div class="timeline-dot bg-primary"></div>
                <div>
                  <strong><?= htmlspecialchars(date('F j, Y', strtotime($request['date_requested']))) ?></strong>
                  <p class="mb-0 text-muted">Request submitted by resident.</p>
                </div>
              </div>
              <?php if (!empty($request['remarks'])): ?>
              <div class="timeline-item">
                <div class="timeline-dot bg-info"></div>
                <div>
                  <strong><?= htmlspecialchars(date('F j, Y', strtotime($request['updated_at'] ?? $request['date_requested']))) ?></strong>
                  <p class="mb-0 text-muted"><?= htmlspecialchars($request['remarks']) ?></p>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/dashboard.js"></script>
</body>
</html>


