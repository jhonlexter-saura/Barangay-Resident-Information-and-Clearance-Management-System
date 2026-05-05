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

$stmt = $pdo->query("SELECT COUNT(*) FROM service_request WHERE status IN ('Pending','Processing')");
$unprocessedRequestCount = (int) $stmt->fetchColumn();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'mark_read') {
        $notifId = intval($_POST['notif_id'] ?? 0);
        if ($notifId > 0) {
            $update = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE notif_id = ?");
            $update->execute([$notifId]);
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM notification WHERE is_read = 0");
        $unreadNotifs = (int) $stmt->fetchColumn();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'unreadCount' => $unreadNotifs]);
        exit();
    }

    if ($action === 'mark_all_read') {
        $pdo->exec("UPDATE notification SET is_read = 1 WHERE is_read = 0");
        $stmt = $pdo->query("SELECT COUNT(*) FROM notification WHERE is_read = 0");
        $unreadNotifs = (int) $stmt->fetchColumn();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'unreadCount' => $unreadNotifs]);
        exit();
    }
}

$stmt = $pdo->query("SELECT COUNT(*) FROM notification WHERE is_read = 0");
$unreadNotifs = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT * FROM notification ORDER BY created_at DESC");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadTotal = count(array_filter($notifications, fn($n) => !$n['is_read']));
$notificationTotal = count($notifications);
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
  <style>
    .notification-item {
      width: 100%;
      text-align: left;
      cursor: pointer;
      border-color: rgba(0,0,0,0.06);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .notification-item:hover {
      transform: translateY(-1px);
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
    }
    .notification-item.notification-read {
      background: #f8fafc;
    }
    .notification-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 1.5rem;
    }
    .notification-overlay.show {
      display: flex;
    }
    .notification-modal {
      width: min(100%, 680px);
      max-height: 90vh;
      overflow: auto;
      background: #ffffff;
      border-radius: 24px;
      padding: 2rem;
      box-shadow: 0 30px 70px rgba(15, 23, 42, 0.18);
      position: relative;
    }
    .notification-modal-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      border: none;
      background: transparent;
      font-size: 1.3rem;
      color: #475569;
      cursor: pointer;
    }
    .notification-modal-header {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      margin-bottom: 1rem;
    }
    .notification-modal-icon {
      width: 56px;
      height: 56px;
      border-radius: 18px;
      display: grid;
      place-items: center;
      font-size: 1.35rem;
    }
    .notification-modal-meta {
      color: #64748b;
      font-size: 0.96rem;
    }
  </style>
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
        <span class="nav-badge" id="sidebarRequestsBadge"><?= number_format($unprocessedRequestCount) ?></span>
      </a>

      <a href="staff-notifications.php" class="nav-item active" data-tooltip="Notifications">
        <i class="bi bi-bell nav-icon"></i>
        <span class="nav-label">Notifications</span>
        <span class="nav-badge" id="sidebarNotifBadge"><?= number_format($unreadNotifs) ?></span>
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
          <span class="notif-count" id="topbarNotifCount"><?= number_format($unreadNotifs) ?></span>
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
          <button type="button" id="markAllReadBtn" class="btn-outline-nav"><i class="bi bi-check-all"></i> Mark all read</button>
        </div>
      </div>
      <div class="dash-card dash-card-wide">
        <div class="dash-card-header">
          <div class="dash-card-title"><i class="bi bi-bell-fill"></i> Recent Alerts</div>
          <a href="staff-notifications.php" class="dash-card-link">Refresh <i class="bi bi-arrow-clockwise"></i></a>
        </div>
        <div class="dash-card-body">
          <div class="notification-list" id="notificationList">
            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $notif):
                [$iconClass, $bgColor, $textColor] = notifIcon($notif['type'] ?? '');
                $isUnread = !$notif['is_read'];
                $itemClasses = 'notification-item p-3 mb-3 rounded-3 border d-flex align-items-start gap-3 text-start';
                $itemClasses .= $isUnread ? ' notification-unread' : ' notification-read bg-gray-50';
              ?>
                <button type="button"
                        class="<?= $itemClasses ?>"
                        data-notif-id="<?= (int) $notif['notif_id'] ?>"
                        data-notif-title="<?= htmlspecialchars($notif['title'] ?? '', ENT_QUOTES) ?>"
                        data-notif-message="<?= htmlspecialchars($notif['message'] ?? '', ENT_QUOTES) ?>"
                        data-notif-type="<?= htmlspecialchars($notif['type'] ?? '', ENT_QUOTES) ?>"
                        data-notif-time="<?= htmlspecialchars($notif['created_at'] ?? '', ENT_QUOTES) ?>">
                  <div class="notification-icon rounded-3 me-3" style="min-width:52px; width:52px; height:52px; background:<?= $bgColor ?>; color: <?= $textColor ?>; display:grid; place-items:center;">
                    <i class="bi <?= htmlspecialchars($iconClass) ?>"></i>
                  </div>
                  <div class="flex-grow-1 text-start">
                    <div class="h6 mb-1"><?= htmlspecialchars($notif['title'] ?? 'Notification') ?></div>
                    <p class="text-muted mb-1"><?= htmlspecialchars($notif['message'] ?? '') ?></p>
                    <small class="text-muted"><?= htmlspecialchars(timeAgo($notif['created_at'] ?? '')) ?></small>
                  </div>
                  <span class="badge <?= $isUnread ? 'bg-warning text-dark' : 'bg-secondary' ?> align-self-start"><?= $isUnread ? 'Unread' : 'Read' ?></span>
                </button>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center text-muted py-5">No notifications available.</div>
            <?php endif; ?>
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
                <div class="stat-value"><?= number_format($notificationTotal) ?></div>
                <div class="stat-label">Total</div>
              </div>
              <div class="col-6">
                <div class="stat-value" id="summaryUnreadCount"><?= number_format($unreadTotal) ?></div>
                <div class="stat-label">Unread</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    <div class="notification-overlay" id="notificationOverlay" aria-hidden="true">
      <div class="notification-modal" role="dialog" aria-modal="true" aria-labelledby="notifModalTitle">
        <button type="button" class="notification-modal-close" id="notificationOverlayClose" aria-label="Close notification overlay">&times;</button>
        <div class="notification-modal-header">
          <div class="notification-modal-icon" id="notifModalIcon" style="background:#e8f3fc;color:#1a7fd4;"></div>
          <div>
            <div class="h5 mb-1" id="notifModalTitle">Notification title</div>
            <div class="notification-modal-meta" id="notifModalMeta">Type • time ago</div>
          </div>
        </div>
        <div class="mb-4"><p id="notifModalMessage" class="mb-0 text-dark"></p></div>
        <div class="d-flex gap-2 justify-content-end">
          <button type="button" class="btn btn-outline-secondary" id="notificationOverlayCloseBtn">Close</button>
          <button type="button" class="btn btn-primary" id="markAsReadBtn">Mark as Read</button>
        </div>
      </div>
    </div>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <script src="../js/dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const overlay = document.getElementById('notificationOverlay');
      const closeOverlay = document.getElementById('notificationOverlayClose');
      const closeOverlayBtn = document.getElementById('notificationOverlayCloseBtn');
      const markAsReadBtn = document.getElementById('markAsReadBtn');
      const sidebarBadge = document.getElementById('sidebarNotifBadge');
      const topbarCount = document.getElementById('topbarNotifCount');
      const summaryUnread = document.getElementById('summaryUnreadCount');
      const notificationList = document.getElementById('notificationList');
      let currentNotificationId = null;
      let currentNotificationButton = null;

      function updateCountDisplay(count) {
        const formatted = Number(count).toLocaleString();
        if (sidebarBadge) {
          sidebarBadge.textContent = formatted;
          sidebarBadge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
        if (topbarCount) {
          topbarCount.textContent = formatted;
          topbarCount.style.display = count > 0 ? 'inline-flex' : 'none';
        }
        if (summaryUnread) {
          summaryUnread.textContent = formatted;
        }
      }

      function closeModal() {
        overlay.classList.remove('show');
        overlay.setAttribute('aria-hidden', 'true');
      }

      function openModal(button) {
        currentNotificationButton = button;
        currentNotificationId = button.dataset.notifId;
        document.getElementById('notifModalTitle').textContent = button.dataset.notifTitle;
        document.getElementById('notifModalMessage').textContent = button.dataset.notifMessage;
        const type = button.dataset.notifType || 'system';
        const time = button.dataset.notifTime || '';
        document.getElementById('notifModalMeta').textContent = type.charAt(0).toUpperCase() + type.slice(1) + ' • ' + time;
        const iconEl = document.getElementById('notifModalIcon');
        const iconData = {
          request: ['bi-file-earmark-check-fill', '#e8f3fc', '#1a7fd4'],
          announcement: ['bi-megaphone-fill', '#e6f7ef', '#1a9e5f'],
          payment: ['bi-cash-coin', '#fde8e8', '#dc2626'],
          system: ['bi-gear-fill', '#f1f5f9', '#64748b'],
        }[type] || ['bi-bell-fill', '#e8f3fc', '#1a7fd4'];
        iconEl.className = 'notification-modal-icon bi ' + iconData[0];
        iconEl.style.background = iconData[1];
        iconEl.style.color = iconData[2];
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
      }

      function markNotificationRead(notifId, callback) {
        const data = new FormData();
        data.append('action', 'mark_read');
        data.append('notif_id', notifId);

        fetch('staff-notifications.php', {
          method: 'POST',
          body: data,
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            callback(result.unreadCount);
          }
        });
      }

      function markAllRead() {
        const data = new FormData();
        data.append('action', 'mark_all_read');

        fetch('staff-notifications.php', {
          method: 'POST',
          body: data,
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            updateCountDisplay(result.unreadCount);
            document.querySelectorAll('.notification-item.notification-unread').forEach(item => {
              item.classList.remove('notification-unread');
              item.classList.add('notification-read');
              const badge = item.querySelector('.badge');
              if (badge) {
                badge.textContent = 'Read';
                badge.className = 'badge bg-secondary align-self-start';
              }
            });
          }
        });
      }

      if (notificationList) {
        notificationList.addEventListener('click', function(event) {
          const button = event.target.closest('.notification-item');
          if (!button) return;
          openModal(button);
        });
      }

      if (closeOverlay) closeOverlay.addEventListener('click', closeModal);
      if (closeOverlayBtn) closeOverlayBtn.addEventListener('click', closeModal);
      window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') closeModal();
      });

      if (markAsReadBtn) {
        markAsReadBtn.addEventListener('click', function() {
          if (!currentNotificationId || !currentNotificationButton) return;
          markNotificationRead(currentNotificationId, function(newCount) {
            updateCountDisplay(newCount);
            currentNotificationButton.classList.remove('notification-unread');
            currentNotificationButton.classList.add('notification-read');
            const badge = currentNotificationButton.querySelector('.badge');
            if (badge) {
              badge.textContent = 'Read';
              badge.className = 'badge bg-secondary align-self-start';
            }
            closeModal();
          });
        });
      }

      const markAllReadBtn = document.getElementById('markAllReadBtn');
      if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllRead);
      }
    });
  </script>
</body>
</html>


