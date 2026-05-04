<?php
require '../aut.php';
require '../config.php';
require '../res-sidebar.php';

// ── Handle AJAX ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'mark_read') {
        $notifId = intval($_POST['notif_id'] ?? 0);
        $stmt = $pdo->prepare("
            UPDATE notification SET is_read = 1
            WHERE notif_id = ? AND resident_id = ?
        ");
        $ok = $stmt->execute([$notifId, $_SESSION['user_id']]);
        echo json_encode(['success' => $ok]);
        exit();
    }

    if ($_POST['action'] === 'mark_all_read') {
        $stmt = $pdo->prepare("
            UPDATE notification SET is_read = 1
            WHERE resident_id = ?
        ");
        $ok = $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => $ok]);
        exit();
    }

    if ($_POST['action'] === 'dismiss') {
        $notifId = intval($_POST['notif_id'] ?? 0);
        $stmt = $pdo->prepare("
            DELETE FROM notification
            WHERE notif_id = ? AND resident_id = ?
        ");
        $ok = $stmt->execute([$notifId, $_SESSION['user_id']]);
        echo json_encode(['success' => $ok]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}

// ── Fetch notifications ───────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT * FROM notification
    WHERE resident_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

// ── Fetch user ────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT first_name, last_name, resident_id FROM resident WHERE resident_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$initials   = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$fullName   = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
$firstname  = htmlspecialchars($user['first_name']);
$residentId = 'RES-' . str_pad($user['resident_id'], 5, '0', STR_PAD_LEFT);

// ── Notification icon helper ──────────────────────────────────────────────────
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
  <title>KALASUNGAY — Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/shared.css" rel="stylesheet">
  <link href="../css/resident-home.css" rel="stylesheet">
  <link href="../css/resident-requests.css" rel="stylesheet">
</head>
<body>
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
        <a href="resident-notifications.php" class="r-topbar-btn" title="Notifications">
          <i class="bi bi-bell-fill"></i>
          <?php if ($unreadCount > 0): ?>
            <span class="r-notif-dot"></span>
          <?php endif; ?>
        </a>
        <a href="resident-profile.php" class="r-profile-chip">
          <div class="r-chip-avatar"><?= $initials ?></div>
          <span class="r-chip-name"><?= $firstname ?></span>
          <i class="bi bi-chevron-down"></i>
        </a>
      </div>
    </header>

    <main class="r-content">

      <div class="rq-page-header">
        <div>
          <h1 class="rq-page-title">Notifications</h1>
          <p class="rq-page-sub">Stay updated on your requests and LGU announcements</p>
        </div>
        <?php if ($unreadCount > 0): ?>
          <button class="rq-mark-all-btn" id="markAllReadBtn" onclick="markAllRead()">
            <i class="bi bi-check2-all"></i> Mark all as read
          </button>
        <?php endif; ?>
      </div>

      <div class="notif-layout">
        <div class="notif-feed-col">

          <!-- Filter tabs -->
          <div class="notif-tabs">
            <button class="notif-tab active" data-tab="all">
              All <span class="notif-tab-count"><?= count($notifications) ?></span>
            </button>
            <button class="notif-tab" data-tab="unread">
              Unread <span class="notif-tab-count unread"><?= $unreadCount ?></span>
            </button>
            <button class="notif-tab" data-tab="request">
              Requests
            </button>
            <button class="notif-tab" data-tab="announcement">
              Announcements
            </button>
            <button class="notif-tab" data-tab="system">
              System
            </button>
          </div>

          <!-- Notification items -->
          <div class="notif-list" id="notifList">

            <?php if (empty($notifications)): ?>
              <div class="notif-empty" id="notifEmpty">
                <div class="notif-empty-icon"><i class="bi bi-bell-slash"></i></div>
                <div class="notif-empty-title">All caught up!</div>
                <div class="notif-empty-sub">You have no notifications yet.</div>
              </div>
            <?php else: ?>
              <?php foreach ($notifications as $notif):
                [$nIcon, $nBg, $nColor] = notifIcon($notif['type']);
                $unreadClass = $notif['is_read'] ? '' : 'unread';
              ?>
              <div class="notif-item <?= $unreadClass ?>"
                   data-type="<?= $notif['type'] ?>"
                   data-id="<?= $notif['notif_id'] ?>"
                   data-read="<?= $notif['is_read'] ?>">
                <div class="notif-icon-wrap" style="background:<?= $nBg ?>;">
                  <i class="bi <?= $nIcon ?>" style="color:<?= $nColor ?>;"></i>
                </div>
                <div class="notif-body">
                  <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                  <div class="notif-text"><?= htmlspecialchars($notif['message']) ?></div>
                  <div class="notif-meta">
                    <span class="notif-time"><i class="bi bi-clock"></i> <?= timeAgo($notif['created_at']) ?></span>
                    <?php if ($notif['link_url']): ?>
                      <a href="<?= htmlspecialchars($notif['link_url']) ?>" class="notif-link">View</a>
                    <?php endif; ?>
                    <span class="notif-tag <?= $notif['type'] ?>"><?= ucfirst($notif['type']) ?></span>
                  </div>
                </div>
                <?php if (!$notif['is_read']): ?>
                  <div class="notif-dot-indicator"></div>
                <?php endif; ?>
                <button class="notif-dismiss"
                        onclick="dismissNotif(<?= $notif['notif_id'] ?>)"
                        title="Dismiss">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>

          </div>

          <div class="notif-empty" id="notifEmpty" style="display:none;">
            <div class="notif-empty-icon"><i class="bi bi-bell-slash"></i></div>
            <div class="notif-empty-title">All caught up!</div>
            <div class="notif-empty-sub">No notifications in this category.</div>
          </div>

        </div>

        <!-- Right: stat + preferences -->
        <div class="notif-right-col">

          <div class="notif-stat-card">
            <div class="notif-stat-icon"><i class="bi bi-bell-fill"></i></div>
            <div>
              <div class="notif-stat-val" id="unreadCount"><?= $unreadCount ?></div>
              <div class="notif-stat-label">Unread Notifications</div>
            </div>
          </div>

          <div class="notif-pref-card">
            <div class="notif-pref-title"><i class="bi bi-sliders"></i> Preferences</div>
            <div class="notif-pref-list">
              <div class="notif-pref-item">
                <div class="notif-pref-info">
                  <div class="notif-pref-name">Request Updates</div>
                  <div class="notif-pref-sub">Status changes on your requests</div>
                </div>
                <label class="notif-toggle">
                  <input type="checkbox" checked id="prefRequests">
                  <span class="notif-toggle-track"></span>
                </label>
              </div>
              <div class="notif-pref-item">
                <div class="notif-pref-info">
                  <div class="notif-pref-name">Announcements</div>
                  <div class="notif-pref-sub">LGU news and events</div>
                </div>
                <label class="notif-toggle">
                  <input type="checkbox" checked id="prefAnnouncements">
                  <span class="notif-toggle-track"></span>
                </label>
              </div>
              <div class="notif-pref-item">
                <div class="notif-pref-info">
                  <div class="notif-pref-name">Payment Reminders</div>
                  <div class="notif-pref-sub">Fees due and payment notices</div>
                </div>
                <label class="notif-toggle">
                  <input type="checkbox" checked id="prefPayments">
                  <span class="notif-toggle-track"></span>
                </label>
              </div>
              <div class="notif-pref-item">
                <div class="notif-pref-info">
                  <div class="notif-pref-name">System Notices</div>
                  <div class="notif-pref-sub">Maintenance and portal updates</div>
                </div>
                <label class="notif-toggle">
                  <input type="checkbox" id="prefSystem">
                  <span class="notif-toggle-track"></span>
                </label>
              </div>
              <div class="notif-pref-item">
                <div class="notif-pref-info">
                  <div class="notif-pref-name">Email Notifications</div>
                  <div class="notif-pref-sub">Receive updates via email</div>
                </div>
                <label class="notif-toggle">
                  <input type="checkbox" checked id="prefEmail">
                  <span class="notif-toggle-track"></span>
                </label>
              </div>
            </div>
          </div>

          <div class="notif-quick-card">
            <div class="notif-pref-title"><i class="bi bi-lightning-charge-fill"></i> Quick Links</div>
            <div class="notif-quick-links">
              <a href="resident-requests.php" class="notif-quick-link">
                <i class="bi bi-file-earmark-text"></i> My Requests
              </a>
              <a href="services/resident-payment.php" class="notif-quick-link">
                <i class="bi bi-cart-fill"></i> Payments
              </a>
              <a href="resident-home.php" class="notif-quick-link">
                <i class="bi bi-grid-fill"></i> Services
              </a>
              <a href="resident-profile.php" class="notif-quick-link">
                <i class="bi bi-person-circle"></i> My Profile
              </a>
            </div>
          </div>

        </div>
      </div>

    </main>
  </div>

  <div class="r-overlay" id="rOverlay"></div>

  <script src="../js/resident-home.js"></script>
  <script src="../js/resident-requests.js"></script>

</body>
</html>

