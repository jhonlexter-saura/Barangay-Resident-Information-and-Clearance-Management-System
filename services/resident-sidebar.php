<?php
/**
 * resident-sidebar.php
 * Reusable sidebar component for the MySerbisyo Resident Portal.
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
      <span class="r-brand-name">MySerbisyo</span>
      <span class="r-brand-sub">Resident Portal</span>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="r-sidebar-nav">
    <div class="r-nav-label">Menu</div>

    <a href="../resident-home.php"
       class="r-nav-item<?= nav_active('home') ?>"
       data-tooltip="Home">
      <i class="bi bi-house-fill r-nav-icon"></i>
      <span class="r-nav-text">Home</span>
    </a>

    <a href="../resident-requests.php"
       class="r-nav-item<?= nav_active('requests') ?>"
       data-tooltip="My Requests">
      <i class="bi bi-file-earmark-text r-nav-icon"></i>
      <span class="r-nav-text">My Requests</span>
      <span class="r-nav-badge">2</span>
    </a>

    <a href="resident-payment.php"
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

    <a href="appointments.php"
       class="r-nav-item<?= nav_active('appointments') ?>"
       data-tooltip="Appointments">
      <i class="bi bi-calendar-check r-nav-icon"></i>
      <span class="r-nav-text">Appointments</span>
    </a>

    <a href="../resident-notifications.php"
       class="r-nav-item<?= nav_active('notifications') ?>"
       data-tooltip="Notifications">
      <i class="bi bi-bell r-nav-icon"></i>
      <span class="r-nav-text">Notifications</span>
      <span class="r-nav-badge">5</span>
    </a>

    <div class="r-nav-divider"></div>
    <div class="r-nav-label">Account</div>

    <a href="../resident-profile.php"
       class="r-nav-item<?= nav_active('profile') ?>"
       data-tooltip="My Profile">
      <i class="bi bi-person-circle r-nav-icon"></i>
      <span class="r-nav-text">My Profile</span>
    </a>

    <a href="../resident-settings.php"
       class="r-nav-item<?= nav_active('settings') ?>"
       data-tooltip="Settings">
      <i class="bi bi-gear r-nav-icon"></i>
      <span class="r-nav-text">Settings</span>
    </a>

  </nav>

  <!-- Footer / user row -->
  <div class="r-sidebar-footer">
    <div class="r-user-row">
      <div class="r-user-avatar">JD</div>
      <div class="r-user-info">
        <span class="r-user-name">Juan Dela Cruz</span>
        <span class="r-user-sub">Resident ID: RES-00412</span>
      </div>
      <a href="../resident-portal.php" class="r-logout-btn" title="Sign out">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>
  </div>

</aside>
<!-- ===== END SIDEBAR ================================================= -->