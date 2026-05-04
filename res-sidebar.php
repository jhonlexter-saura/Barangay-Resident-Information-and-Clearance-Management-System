<?php
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM notification
    WHERE resident_id = ? AND is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$unread_notifs = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM service_request
    WHERE resident_id = ? AND status IN ('Pending','Processing','Ready for Pickup')
");
$stmt->execute([$_SESSION['user_id']]);
$active_requests = (int) $stmt->fetchColumn();

/**
 * resident-sidebar.php
 * Reusable sidebar component for the KALASUNGAY Resident Portal.
 * Include this file in any resident-facing PHP page.
 *
 * Usage:
 *   <?php include 'resident-sidebar.php'; ?>
 *
 * Optional variables you can define BEFORE including this file:
 *   $active_nav  — which nav item to mark active
 *                  Values: 'home' | 'requests' | 'payments' | 'appointments' | 'notifications' | 'profile' | 'settings'
 *   $show_cart_badge — (bool) show the cart badge on Payments nav item
 *   $cart_count      — (int)  number shown in the cart badge
 *
 * Example:
 *   <?php
 *     $active_nav       = 'payments';
 *     $show_cart_badge  = true;
 *     $cart_count       = 3;
 *     include 'resident-sidebar.php';
 *   ?>
 */

// --- defaults -----------------------------------------------------------
$active_nav      = $active_nav      ?? '';
$show_cart_badge = $show_cart_badge ?? false;
$cart_count      = $cart_count      ?? 0;

// Helper: returns " active" when the nav key matches the current page
// Helper: returns " active" when the nav key matches the current page
if (!function_exists('nav_active')) {
    function nav_active(string $key): string {
        global $active_nav;
        return ($active_nav === $key) ? ' active' : '';
    }
}
?>

<!-- ===== SIDEBAR ===================================================== -->
<aside class="r-sidebar" id="rSidebar">

  <!-- Brand -->
  <div class="r-sidebar-brand">
    <div class="r-brand-logo"><i class="bi bi-buildings-fill"></i></div>
    <div class="r-brand-text">
      <span class="r-brand-name">KALASUNGAY</span>
      <span class="r-brand-sub">Resident Portal</span>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="r-sidebar-nav">
    <div class="r-nav-label">Menu</div>

    <a href="resident-home.php"
       class="r-nav-item<?= nav_active('home') ?>"
       data-tooltip="Home">
      <i class="bi bi-house-fill r-nav-icon"></i>
      <span class="r-nav-text">Home</span>
    </a>

    <a href="resident-requests.php" class="r-nav-item <?= $active_nav === 'requests' ? 'active' : '' ?>" data-tooltip="My Requests">
      <i class="bi bi-file-earmark-text r-nav-icon"></i>
      <span class="r-nav-text">My Requests</span>
      <?php if ($active_requests > 0): ?>
        <span class="r-nav-badge"><?= $active_requests ?></span>
      <?php endif; ?>
    </a>

    <a href="services/resident-payment.php"
       class="r-nav-item<?= nav_active('payments') ?>"
       data-tooltip="Payments">
      <i class="bi bi-cash-coin r-nav-icon"></i>
      <span class="r-nav-text">Payments</span>
      <?php if ($show_cart_badge && $cart_count > 0): ?>
        <span class="r-nav-badge" id="cartNavBadge"><?= (int) $cart_count ?></span>
      <?php else: ?>
        <span class="r-nav-badge" id="cartNavBadge" style="display:none;"></span>
      <?php endif; ?>
    </a>

    <a href="services/appointments.php"
       class="r-nav-item<?= nav_active('appointments') ?>"
       data-tooltip="Appointments">
      <i class="bi bi-calendar-check r-nav-icon"></i>
      <span class="r-nav-text">Appointments</span>
    </a>

    <a href="resident-notifications.php" class="r-nav-item <?= $active_nav === 'notifications' ? 'active' : '' ?>" data-tooltip="Notifications">
      <i class="bi bi-bell r-nav-icon"></i>
      <span class="r-nav-text">Notifications</span>
      <?php if ($unread_notifs > 0): ?>
        <span class="r-nav-badge"><?= $unread_notifs ?></span>
      <?php endif; ?>
    </a>

    <div class="r-nav-divider"></div>
    <div class="r-nav-label">Account</div>

    <a href="resident-profile.php"
       class="r-nav-item<?= nav_active('profile') ?>"
       data-tooltip="My Profile">
      <i class="bi bi-person-circle r-nav-icon"></i>
      <span class="r-nav-text">My Profile</span>
    </a>

    <a href="resident-settings.php"
       class="r-nav-item<?= nav_active('settings') ?>"
       data-tooltip="Settings">
      <i class="bi bi-gear r-nav-icon"></i>
      <span class="r-nav-text">Settings</span>
      
      <a href="resident-helpsupport.php" class="r-nav-item" data-tooltip="Help">
        <i class="bi bi-question-circle r-nav-icon"></i><span class="r-nav-text">Help & Support</span>
    </a>

  </nav>

    <!-- REPLACE the entire footer user row with: -->
    <div class="r-sidebar-footer">
      <div class="r-user-row">
        <div class="r-user-avatar"><?= $initials ?? 'JD' ?></div>
        <div class="r-user-info">
          <span class="r-user-name"><?= $fullName ?? 'Resident' ?></span>
          <span class="r-user-sub">Resident ID: <?= $residentId ?? '—' ?></span>
        </div>
        <a href="resident-logout.php" class="r-logout-btn" title="Sign out">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>

</aside>
<!-- ===== END SIDEBAR ================================================= -->