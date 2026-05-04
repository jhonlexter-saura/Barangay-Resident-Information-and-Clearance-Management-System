<?php
session_start();
require '../config.php';
require '../res-sidebar.php';

if (empty($_SESSION['user_id'])) {
    header('Location: resident-portal.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM resident WHERE resident_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: resident-portal.php');
    exit();
}

$firstname   = htmlspecialchars($user['first_name']);
$lastname    = htmlspecialchars($user['last_name']);
$fullName    = $firstname . ' ' . $lastname;
$initials    = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$residentId  = htmlspecialchars($user['resident_id'] ?? 'RES-?????');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings — KALASUNGAY</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/shared.css" rel="stylesheet">
  <link href="../css/resident-home.css" rel="stylesheet">
  <style>
    .settings-container { max-width: 900px; margin: 0 auto; }
    .settings-card { background: white; border-radius: 1rem; border: 1px solid var(--gray-200); margin-bottom: 1.5rem; overflow: hidden; }
    .settings-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; gap: 0.75rem; }
    .settings-header i { font-size: 1.25rem; color: var(--sky); }
    .settings-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--text-dark); }
    .settings-body { padding: 1.5rem; }
    .setting-item { display: flex; align-items: center; justify-content: space-between; padding-bottom: 1.25rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--gray-50); }
    .setting-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .setting-info { flex: 1; }
    .setting-label { display: block; font-weight: 600; color: var(--text-dark); margin-bottom: 0.15rem; }
    .setting-desc { display: block; font-size: 0.85rem; color: var(--text-muted); }
    .form-switch .form-check-input { width: 2.5em; height: 1.25em; cursor: pointer; }
    .btn-save-settings { background: var(--sky); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 0.5rem; font-weight: 600; transition: 0.2s; }
    .btn-save-settings:hover { background: var(--sky-dark); }
  </style>
</head>
<body>

  <div class="r-main" id="rMain">
    <header class="r-topbar">
      <div class="r-topbar-left">
        <button class="r-menu-btn" id="rMenuBtn"><i class="bi bi-list"></i></button>
        <span class="fw-bold text-dark ms-2">Account Settings</span>
      </div>
    </header>

    <main class="r-content">
      <div class="settings-container">
        
        <!-- Account Security -->
        <div class="settings-card">
          <div class="settings-header">
            <i class="bi bi-shield-lock-fill"></i>
            <h2>Security & Password</h2>
          </div>
          <div class="settings-body">
            <div class="setting-item">
              <div class="setting-info">
                <span class="setting-label">Change Password</span>
                <span class="setting-desc">Last changed 3 months ago.</span>
              </div>
              <button class="btn btn-outline-primary btn-sm rounded-pill px-3">Update</button>
            </div>
            <div class="setting-item">
              <div class="setting-info">
                <span class="setting-label">Two-Factor Authentication</span>
                <span class="setting-desc">Add an extra layer of security to your account.</span>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="twoFactor">
              </div>
            </div>
          </div>
        </div>

        <!-- Notifications -->
        <div class="settings-card">
          <div class="settings-header">
            <i class="bi bi-bell-fill"></i>
            <h2>Notification Preferences</h2>
          </div>
          <div class="settings-body">
            <div class="setting-item">
              <div class="setting-info">
                <span class="setting-label">Email Notifications</span>
                <span class="setting-desc">Receive updates about your requests via email.</span>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" checked>
              </div>
            </div>
            <div class="setting-item">
              <div class="setting-info">
                <span class="setting-label">SMS Alerts</span>
                <span class="setting-desc">Get text messages for urgent community announcements.</span>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox">
              </div>
            </div>
          </div>
        </div>

        <!-- Privacy -->
        <div class="settings-card">
          <div class="settings-header">
            <i class="bi bi-eye-fill"></i>
            <h2>Display & Privacy</h2>
          </div>
          <div class="settings-body">
            <div class="setting-item">
              <div class="setting-info">
                <span class="setting-label">System Language</span>
                <span class="setting-desc">Choose your preferred language.</span>
              </div>
              <select class="form-select form-select-sm w-auto">
                <option selected>English</option>
                <option>Filipino (Tagalog)</option>
              </select>
            </div>
            <div class="setting-item">
              <div class="setting-info">
                <span class="setting-label">Public Profile</span>
                <span class="setting-desc">Allow other residents to see your name in community boards.</span>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" checked>
              </div>
            </div>
          </div>
        </div>

        <div class="text-end mb-5">
            <button class="btn-save-settings">Save All Changes</button>
        </div>

      </div>
    </main>
  </div>

  <div class="r-overlay" id="rOverlay"></div>
  <script src="../js/resident-home.js"></script>
</body>
</html>


