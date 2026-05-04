<?php
session_start();
require '../config.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (empty($_SESSION['loggedin'])) {
    header('Location: staff-portal.php');
    exit();
}

// ── Admin-only guard ─────────────────────────────────────────────────────────
if ($_SESSION['role'] !== 'Admin') {
    $_SESSION['error'] = 'You do not have permission to access that page.';
    header('Location: staff-dashboard.php');
    exit();
}

// ── Flash messages ───────────────────────────────────────────────────────────
$flash_error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$flash_success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// ── Fetch all officials with joined resident name ────────────────────────────
$officials = $pdo->query("
    SELECT
        bo.user_id,
        bo.username,
        bo.role_position,
        bo.access_level,
        bo.is_active,
        bo.last_login,
        bo.created_at,
        r.resident_id,
        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) AS full_name,
        r.email,
        r.mobile_number
    FROM barangay_official bo
    JOIN resident r ON bo.resident_id = r.resident_id
    ORDER BY bo.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Summary counts ───────────────────────────────────────────────────────────
$total    = count($officials);
$active   = count(array_filter($officials, fn($o) => $o['is_active']));
$inactive = $total - $active;
$admins   = count(array_filter($officials, fn($o) => $o['access_level'] === 'Admin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LGU eGov — Manage Officials</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/shared.css" rel="stylesheet">
  <link href="../css/dashboard.css" rel="stylesheet">

  <style>
    /* ══ Page-specific overrides ══════════════════════════════════════════════ */

    /* Filter bar */
    .filter-bar {
      display: flex;
      align-items: center;
      gap: .65rem;
      flex-wrap: wrap;
      margin-bottom: 1.25rem;
    }

    .filter-search {
      display: flex;
      align-items: center;
      gap: 7px;
      background: var(--white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: .42rem .85rem;
      flex: 1;
      min-width: 200px;
      max-width: 340px;
      transition: border-color var(--transition-base), box-shadow var(--transition-base);
    }
    .filter-search:focus-within {
      border-color: var(--sky);
      box-shadow: 0 0 0 3px rgba(26,127,212,.1);
    }
    .filter-search i    { color: var(--gray-400); font-size: .82rem; flex-shrink: 0; }
    .filter-search input {
      border: none; outline: none; background: none;
      font-size: .82rem; color: var(--text-dark); width: 100%;
    }
    .filter-search input::placeholder { color: var(--gray-400); }

    .filter-select {
      background: var(--white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: .42rem .75rem;
      font-size: .8rem;
      color: var(--text-mid);
      font-family: 'Plus Jakarta Sans', sans-serif;
      outline: none;
      cursor: pointer;
      transition: border-color var(--transition-fast);
    }
    .filter-select:focus { border-color: var(--sky); }

    .filter-spacer { flex: 1; }

    /* Officials table */
    .officials-table { width: 100%; border-collapse: collapse; }

    .officials-table th {
      font-size: .68rem;
      font-weight: 700;
      letter-spacing: .07em;
      text-transform: uppercase;
      color: var(--text-muted);
      padding: 0 .9rem .75rem;
      text-align: left;
      border-bottom: 1px solid var(--border-light);
      white-space: nowrap;
    }

    .officials-table td {
      padding: .9rem .9rem;
      font-size: .82rem;
      color: var(--text-mid);
      border-bottom: 1px solid var(--gray-100);
      vertical-align: middle;
    }

    .officials-table tr:last-child td { border-bottom: none; }
    .officials-table tbody tr:hover td { background: var(--gray-50); }

    /* Official identity cell */
    .official-identity { display: flex; align-items: center; gap: 10px; }

    .official-avatar {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--navy), var(--navy-mid));
      color: var(--gold);
      font-size: .68rem;
      font-weight: 700;
      display: grid;
      place-items: center;
      flex-shrink: 0;
      letter-spacing: .02em;
    }

    .official-name  { font-size: .83rem; font-weight: 600; color: var(--text-dark); line-height: 1.2; }
    .official-uname {
      font-family: 'DM Mono', monospace;
      font-size: .65rem;
      color: var(--text-muted);
    }

    /* Role badge */
    .role-badge {
      font-size: .68rem;
      font-weight: 700;
      padding: 2px 9px;
      border-radius: var(--radius-full);
      white-space: nowrap;
      display: inline-block;
    }
    .role-Admin     { background:#f0e8ff; color:#5b21b6; }
    .role-Editor    { background:#e8f3fc; color:#1a7fd4; }
    .role-Viewer    { background:var(--gray-100); color:var(--text-mid); }

    /* Position badge */
    .pos-badge {
      font-size: .68rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: var(--radius-full);
      background: var(--gray-100);
      color: var(--text-mid);
      white-space: nowrap;
    }
    .pos-badge.Chairman  { background:#fef3c7; color:#92400e; }
    .pos-badge.Secretary { background:#e8f3fc; color:#1a7fd4; }
    .pos-badge.Treasurer { background:#e6f7ef; color:#166534; }
    .pos-badge.Councilor { background:#f0e8ff; color:#5b21b6; }
    .pos-badge.Tanod     { background:#fde8e8; color:#991b1b; }
    .pos-badge.Admin     { background:#fef3c7; color:#92400e; }

    /* Active pill */
    .active-pill {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: .68rem; font-weight: 700;
      padding: 2px 8px;
      border-radius: var(--radius-full);
    }
    .active-pill.on  { background: var(--success-light); color: #166534; }
    .active-pill.off { background: var(--danger-light);  color: #991b1b; }
    .active-pill .dot {
      width: 6px; height: 6px; border-radius: 50%;
    }
    .active-pill.on  .dot { background: #16a34a; }
    .active-pill.off .dot { background: #dc2626; }

    /* Login timestamp */
    .login-time { font-family: 'DM Mono', monospace; font-size: .7rem; color: var(--text-muted); }

    /* Row actions */
    .row-actions { display: flex; align-items: center; gap: 5px; }

    .row-btn {
      background: none;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      color: var(--text-muted);
      font-size: .72rem;
      padding: 3px 9px;
      display: flex; align-items: center; gap: 4px;
      transition: all var(--transition-fast);
      white-space: nowrap;
    }
    .row-btn:hover           { background: var(--gray-100); color: var(--text-dark); }
    .row-btn.btn-edit:hover  { background: var(--sky-light); border-color: var(--sky); color: var(--sky); }
    .row-btn.btn-toggle-off:hover { background: var(--danger-light); border-color: #dc2626; color: #dc2626; }
    .row-btn.btn-toggle-on:hover  { background: var(--success-light); border-color: #16a34a; color: #16a34a; }
    .row-btn.btn-reset:hover { background: #fef3c7; border-color: #d97706; color: #d97706; }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 3.5rem 1rem;
      color: var(--text-muted);
    }
    .empty-state i   { font-size: 2.5rem; color: var(--gray-300); margin-bottom: .75rem; display: block; }
    .empty-state p   { font-size: .85rem; margin: 0; }

    /* ══ Modal styles ══════════════════════════════════════════════════════════ */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,.55);
      backdrop-filter: blur(3px);
      z-index: 1050;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      animation: fadeIn .15s ease;
    }
    .modal-overlay.show { display: flex; }

    @keyframes fadeIn  { from { opacity:0; } to { opacity:1; } }
    @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

    .modal-box {
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: 0 25px 60px rgba(0,0,0,.25);
      width: 100%;
      max-width: 620px;
      max-height: 90vh;
      overflow-y: auto;
      animation: slideUp .2s ease;
    }

    .modal-header {
      display: flex; align-items: center;
      padding: 1.25rem 1.5rem 1rem;
      border-bottom: 1px solid var(--border-light);
      gap: 10px;
      position: sticky; top: 0;
      background: var(--white);
      z-index: 1;
    }
    .modal-header-icon {
      width: 36px; height: 36px;
      border-radius: var(--radius-md);
      background: rgba(201,168,76,.12);
      color: var(--gold);
      display: grid; place-items: center;
      font-size: 1rem;
      flex-shrink: 0;
    }
    .modal-title     { font-family: 'Playfair Display', serif; font-size: 1.05rem; font-weight: 700; color: var(--navy); margin: 0; flex: 1; }
    .modal-close-btn {
      background: none; border: none;
      color: var(--text-muted); font-size: 1.1rem;
      width: 30px; height: 30px; border-radius: var(--radius-sm);
      display: grid; place-items: center;
      transition: background var(--transition-fast), color var(--transition-fast);
    }
    .modal-close-btn:hover { background: var(--gray-100); color: var(--navy); }

    .modal-body   { padding: 1.35rem 1.5rem; }
    .modal-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--border-light);
      display: flex; align-items: center; justify-content: flex-end; gap: .6rem;
    }

    /* Resident search inside modal */
    .resident-search-wrap { position: relative; margin-bottom: 1.1rem; }
    .resident-search-wrap input {
      width: 100%;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: .55rem .85rem .55rem 2.1rem;
      font-size: .83rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      outline: none;
      transition: border-color var(--transition-base), box-shadow var(--transition-base);
    }
    .resident-search-wrap input:focus {
      border-color: var(--sky);
      box-shadow: 0 0 0 3px rgba(26,127,212,.1);
    }
    .resident-search-wrap .search-ico {
      position: absolute; left: .7rem; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: .82rem; pointer-events: none;
    }

    .resident-results {
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      overflow: hidden;
      max-height: 200px;
      overflow-y: auto;
      display: none;
      background: var(--white);
      box-shadow: var(--shadow-md);
      position: absolute;
      width: 100%;
      z-index: 10;
      top: calc(100% + 4px);
    }
    .resident-results.show { display: block; }

    .resident-result-item {
      display: flex; align-items: center; gap: 10px;
      padding: .65rem .9rem;
      cursor: pointer;
      border-bottom: 1px solid var(--gray-100);
      transition: background var(--transition-fast);
    }
    .resident-result-item:last-child { border-bottom: none; }
    .resident-result-item:hover { background: var(--sky-light); }
    .res-avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: var(--gray-100); color: var(--text-mid);
      font-size: .65rem; font-weight: 700;
      display: grid; place-items: center; flex-shrink: 0;
    }
    .res-name  { font-size: .82rem; font-weight: 600; color: var(--text-dark); }
    .res-email { font-size: .7rem; color: var(--text-muted); }

    /* Selected resident card */
    .selected-resident {
      display: none;
      align-items: center;
      gap: 10px;
      padding: .65rem .9rem;
      background: var(--sky-light);
      border: 1px solid var(--sky);
      border-radius: var(--radius-md);
      margin-bottom: 1.1rem;
    }
    .selected-resident.show { display: flex; }
    .selected-resident .sr-name  { font-size: .83rem; font-weight: 600; color: var(--navy); flex: 1; }
    .selected-resident .sr-clear {
      background: none; border: none; color: var(--text-muted);
      font-size: .8rem; padding: 2px 4px;
      transition: color var(--transition-fast);
    }
    .selected-resident .sr-clear:hover { color: var(--danger); }

    /* Form fields */
    .form-group { margin-bottom: 1rem; }
    .form-label-sm {
      font-size: .75rem; font-weight: 700;
      color: var(--text-mid);
      letter-spacing: .03em;
      display: block; margin-bottom: .35rem;
    }
    .form-control-sm-custom {
      width: 100%;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: .52rem .85rem;
      font-size: .83rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--text-dark);
      outline: none;
      transition: border-color var(--transition-base), box-shadow var(--transition-base);
      background: var(--white);
    }
    .form-control-sm-custom:focus {
      border-color: var(--sky);
      box-shadow: 0 0 0 3px rgba(26,127,212,.1);
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }

    /* Password toggle inside modal */
    .pw-wrap { position: relative; }
    .pw-wrap input { padding-right: 2.5rem; }
    .pw-toggle {
      position: absolute; right: .7rem; top: 50%; transform: translateY(-50%);
      background: none; border: none;
      color: var(--gray-400); font-size: .85rem;
      transition: color var(--transition-fast);
    }
    .pw-toggle:hover { color: var(--navy); }

    /* Section divider */
    .modal-section-label {
      font-size: .65rem;
      font-weight: 800;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin: 1.25rem 0 .75rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .modal-section-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border-light);
    }

    /* Modal buttons */
    .btn-modal-cancel {
      background: var(--white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      color: var(--text-mid);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .8rem; font-weight: 600;
      padding: .5rem 1.1rem;
      transition: all var(--transition-fast);
    }
    .btn-modal-cancel:hover { background: var(--gray-100); }

    .btn-modal-submit {
      background: var(--navy);
      border: none;
      border-radius: var(--radius-md);
      color: var(--white);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .8rem; font-weight: 600;
      padding: .5rem 1.3rem;
      display: flex; align-items: center; gap: 6px;
      box-shadow: 0 2px 8px rgba(13,31,66,.2);
      transition: all var(--transition-fast);
    }
    .btn-modal-submit:hover { background: var(--navy-mid); }

    /* Confirm modal */
    .confirm-icon {
      width: 52px; height: 52px; border-radius: 50%;
      display: grid; place-items: center;
      font-size: 1.4rem; margin: 0 auto 1rem;
    }
    .confirm-icon.danger  { background: var(--danger-light);  color: #dc2626; }
    .confirm-icon.warning { background: var(--warning-light); color: #d97706; }
    .confirm-icon.success { background: var(--success-light); color: #16a34a; }

    /* Strength meter */
    .strength-bar-wrap { display:flex; gap:4px; margin-top:5px; }
    .strength-seg { flex:1; height:3px; border-radius:2px; background:rgba(0,0,0,.08); transition:background .3s; }
    .strength-seg.active-weak   { background:#e74c3c; }
    .strength-seg.active-fair   { background:#e67e22; }
    .strength-seg.active-good   { background:#f1c40f; }
    .strength-seg.active-strong { background:#2ecc71; }
    .strength-label { font-size:.68rem; margin-top:3px; color:var(--text-muted); font-family:'DM Mono',monospace; }

    /* Hidden rows (filtered out) */
    tr.hidden-row { display: none; }

    /* Pagination */
    .table-footer {
      display: flex; align-items: center; justify-content: space-between;
      padding: .85rem 1.25rem;
      border-top: 1px solid var(--border-light);
      font-size: .75rem; color: var(--text-muted);
    }
    .pager { display:flex; gap:4px; }
    .pager-btn {
      background: var(--white); border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      padding: 3px 9px; font-size: .75rem; color: var(--text-mid);
      cursor: pointer; transition: all var(--transition-fast);
    }
    .pager-btn:hover, .pager-btn.active { background: var(--navy); color: var(--white); border-color: var(--navy); }

    /* Responsive */
    @media (max-width: 768px) {
      .officials-table th:nth-child(4),
      .officials-table td:nth-child(4),
      .officials-table th:nth-child(6),
      .officials-table td:nth-child(6) { display: none; }
      .filter-bar { flex-direction: column; align-items: stretch; }
      .filter-search { max-width: 100%; }
      .form-row { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
      .officials-table th:nth-child(3),
      .officials-table td:nth-child(3) { display: none; }
      .row-btn span { display: none; }
    }
  </style>
</head>

<body>

<!-- ══ Sidebar ══════════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
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
      <span class="sidebar-brand-name">LGU eGov</span>
      <span class="sidebar-brand-sub">Municipal Portal</span>
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
      <span class="nav-badge">12</span>
    </a>

    <a href="staff-notifications.php" class="nav-item" data-tooltip="Notifications">
      <i class="bi bi-bell nav-icon"></i>
      <span class="nav-label">Notifications</span>
    </a>

    <div class="nav-divider"></div>
    <div class="nav-section-label">Operations</div>

    <a href="staff-manage.php" class="nav-item active" data-tooltip="Officials">
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

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">
        <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 2)) ?>
      </div>
      <div class="user-info">
        <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
        <span class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? 'Admin') ?></span>
      </div>
      <a href="staff-logout.php" class="user-logout" title="Sign out">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>
  </div>
</aside>

<!-- ══ Main area ════════════════════════════════════════════════════════════ -->
<div class="main-area" id="mainArea">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="topbar-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
        <i class="bi bi-list"></i>
      </button>
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <span class="breadcrumb-item">
          <i class="bi bi-person-badge"></i> Manage Officials
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
      <button class="topbar-btn notif-btn" aria-label="Notifications">
        <i class="bi bi-bell"></i>
      </button>
      <div class="topbar-profile">
        <div class="profile-avatar">
          <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 2)) ?>
        </div>
        <div class="profile-info">
          <span class="profile-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
          <span class="profile-dept">Administrator</span>
        </div>
        <i class="bi bi-chevron-down profile-chevron"></i>
      </div>
    </div>
  </header>

  <!-- Page content -->
  <main class="page-content">

    <!-- Flash messages -->
    <?php if ($flash_error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($flash_error) ?>
      </div>
    <?php endif; ?>
    <?php if ($flash_success): ?>
      <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <?= htmlspecialchars($flash_success) ?>
      </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="content-header">
      <div class="content-header-left">
        <h1 class="page-title">Manage Officials</h1>
        <p class="page-subtitle">Barangay officials and system user accounts</p>
      </div>
      <div class="content-header-right">
        <button class="btn-primary-nav" onclick="openAddModal()">
          <i class="bi bi-person-plus"></i> Add Official
        </button>
      </div>
    </div>

    <!-- Summary stat cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(4,1fr); margin-bottom:1.5rem;">
      <div class="stat-card">
        <div class="stat-icon" style="background:#e8f3fc;color:#1a7fd4;">
          <i class="bi bi-people-fill"></i>
        </div>
        <div class="stat-body">
          <span class="stat-value"><?= $total ?></span>
          <span class="stat-label">Total Officials</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#e6f7ef;color:#1a9e5f;">
          <i class="bi bi-person-check-fill"></i>
        </div>
        <div class="stat-body">
          <span class="stat-value"><?= $active ?></span>
          <span class="stat-label">Active Accounts</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fde8e8;color:#dc2626;">
          <i class="bi bi-person-x-fill"></i>
        </div>
        <div class="stat-body">
          <span class="stat-value"><?= $inactive ?></span>
          <span class="stat-label">Inactive Accounts</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0e8ff;color:#7c3aed;">
          <i class="bi bi-shield-fill"></i>
        </div>
        <div class="stat-body">
          <span class="stat-value"><?= $admins ?></span>
          <span class="stat-label">Admin Users</span>
        </div>
      </div>
    </div>

    <!-- Officials table card -->
    <div class="dash-card">
      <div class="dash-card-header">
        <div class="dash-card-title">
          <i class="bi bi-person-badge"></i>
          Registered Officials
        </div>
        <span style="font-size:.72rem; color:var(--text-muted); font-family:'DM Mono',monospace;">
          <?= $total ?> record<?= $total !== 1 ? 's' : '' ?>
        </span>
      </div>

      <!-- Filter bar -->
      <div class="dash-card-body" style="padding-bottom:0;">
        <div class="filter-bar">
          <div class="filter-search">
            <i class="bi bi-search"></i>
            <input type="text" id="filterInput" placeholder="Search by name or username…">
          </div>
          <select class="filter-select" id="filterRole">
            <option value="">All Access Levels</option>
            <option value="Admin">Admin</option>
            <option value="Editor">Editor</option>
            <option value="Viewer">Viewer</option>
          </select>
          <select class="filter-select" id="filterPosition">
            <option value="">All Positions</option>
            <option value="Chairman">Chairman</option>
            <option value="Secretary">Secretary</option>
            <option value="Treasurer">Treasurer</option>
            <option value="Councilor">Councilor</option>
            <option value="Tanod">Tanod</option>
            <option value="Clerk">Clerk</option>
            <option value="Admin">Admin</option>
          </select>
          <select class="filter-select" id="filterStatus">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
          <div class="filter-spacer"></div>
          <button class="btn-outline-nav" onclick="clearFilters()">
            <i class="bi bi-x-circle"></i> Clear
          </button>
        </div>
      </div>

      <!-- Table -->
      <div style="overflow-x:auto;">
        <table class="officials-table" id="officialsTable">
          <thead>
            <tr>
              <th>Official</th>
              <th>Position</th>
              <th>Access Level</th>
              <th>Last Login</th>
              <th>Status</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php if (empty($officials)): ?>
              <tr>
                <td colspan="6">
                  <div class="empty-state">
                    <i class="bi bi-person-x"></i>
                    <p>No officials found. Add your first official to get started.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($officials as $off):
                $initials = strtoupper(
                  substr($off['full_name'], 0, 1) .
                  (strpos($off['full_name'], ' ') !== false
                    ? substr($off['full_name'], strpos($off['full_name'], ' ') + 1, 1)
                    : '')
                );
                $loginStr = $off['last_login']
                  ? date('M j, Y · g:i A', strtotime($off['last_login']))
                  : 'Never';
              ?>
              <tr
                data-name="<?= htmlspecialchars(strtolower($off['full_name'] . ' ' . $off['username'])) ?>"
                data-role="<?= htmlspecialchars($off['access_level']) ?>"
                data-position="<?= htmlspecialchars($off['role_position']) ?>"
                data-status="<?= $off['is_active'] ?>"
              >
                <td>
                  <div class="official-identity">
                    <div class="official-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div>
                      <div class="official-name"><?= htmlspecialchars($off['full_name']) ?></div>
                      <div class="official-uname">@<?= htmlspecialchars($off['username']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="pos-badge <?= htmlspecialchars($off['role_position']) ?>">
                    <?= htmlspecialchars($off['role_position']) ?>
                  </span>
                </td>
                <td>
                  <span class="role-badge role-<?= htmlspecialchars($off['access_level']) ?>">
                    <?= htmlspecialchars($off['access_level']) ?>
                  </span>
                </td>
                <td>
                  <span class="login-time"><?= $loginStr ?></span>
                </td>
                <td>
                  <?php if ($off['is_active']): ?>
                    <span class="active-pill on"><span class="dot"></span>Active</span>
                  <?php else: ?>
                    <span class="active-pill off"><span class="dot"></span>Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="row-actions" style="justify-content:flex-end;">
                    <button class="row-btn btn-edit" onclick='openEditModal(<?= json_encode($off) ?>)'>
                      <i class="bi bi-pencil"></i><span> Edit</span>
                    </button>
                    <button class="row-btn btn-reset"
                      onclick="openResetModal(<?= $off['user_id'] ?>, '<?= htmlspecialchars($off['username']) ?>')">
                      <i class="bi bi-key"></i><span> Reset</span>
                    </button>
                    <?php if ($off['is_active']): ?>
                      <button class="row-btn btn-toggle-off"
                        onclick="openToggleModal(<?= $off['user_id'] ?>, '<?= htmlspecialchars($off['username']) ?>', 0)">
                        <i class="bi bi-lock"></i><span> Deactivate</span>
                      </button>
                    <?php else: ?>
                      <button class="row-btn btn-toggle-on"
                        onclick="openToggleModal(<?= $off['user_id'] ?>, '<?= htmlspecialchars($off['username']) ?>', 1)">
                        <i class="bi bi-unlock"></i><span> Activate</span>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Table footer / count -->
      <div class="table-footer">
        <span id="rowCount">Showing <?= $total ?> official<?= $total !== 1 ? 's' : '' ?></span>
        <div class="pager" id="pager"></div>
      </div>
    </div>

  </main>
</div>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>


<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Add Official
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-header-icon"><i class="bi bi-person-plus"></i></div>
      <h2 class="modal-title">Add New Official</h2>
      <button class="modal-close-btn" onclick="closeModal('addModal')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <form action="staff-manage_action.php" method="post">
      <input type="hidden" name="action" value="add">

      <div class="modal-body">

        <!-- Resident search -->
        <div class="modal-section-label">Link to Resident Record</div>

        <div class="selected-resident" id="selectedResident">
          <div class="res-avatar" id="selAvatar"></div>
          <div>
            <div class="res-name" id="selName"></div>
            <div class="res-email" id="selEmail"></div>
          </div>
          <span style="flex:1;"></span>
          <button type="button" class="sr-clear" onclick="clearResident()">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="resident-search-wrap" id="residentSearchArea">
          <i class="bi bi-search search-ico"></i>
          <input
            type="text"
            id="residentSearchInput"
            placeholder="Search resident by name or email…"
            autocomplete="off"
          >
          <div class="resident-results" id="residentResults"></div>
        </div>

        <!-- Hidden field for chosen resident_id -->
        <input type="hidden" name="resident_id" id="selectedResidentId" required>

        <!-- Credentials -->
        <div class="modal-section-label">Account Credentials</div>

        <div class="form-group">
          <label class="form-label-sm">Username</label>
          <input type="text" name="username" class="form-control-sm-custom"
                 placeholder="e.g. jdelacruz" required
                 pattern="[a-zA-Z0-9._\-]{3,60}"
                 title="3–60 characters: letters, numbers, dots, underscores, hyphens">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label-sm">Password</label>
            <div class="pw-wrap">
              <input type="password" name="password" id="addPw" class="form-control-sm-custom"
                     placeholder="••••••••" required minlength="8">
              <button type="button" class="pw-toggle" onclick="togglePw('addPw','addPwEye')">
                <i class="bi bi-eye" id="addPwEye"></i>
              </button>
            </div>
            <div class="strength-bar-wrap" id="addStrengthBar">
              <div class="strength-seg" id="as1"></div>
              <div class="strength-seg" id="as2"></div>
              <div class="strength-seg" id="as3"></div>
              <div class="strength-seg" id="as4"></div>
            </div>
            <div class="strength-label" id="addStrengthLabel">Enter a password</div>
          </div>
          <div class="form-group">
            <label class="form-label-sm">Confirm Password</label>
            <div class="pw-wrap">
              <input type="password" name="confirm_password" id="addPwC" class="form-control-sm-custom"
                     placeholder="••••••••" required>
              <button type="button" class="pw-toggle" onclick="togglePw('addPwC','addPwCEye')">
                <i class="bi bi-eye" id="addPwCEye"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Role & Access -->
        <div class="modal-section-label">Role & Permissions</div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label-sm">Position / Role</label>
            <select name="role_position" class="form-control-sm-custom" required>
              <option value="">— Select position —</option>
              <option value="Chairman">Chairman</option>
              <option value="Secretary">Secretary</option>
              <option value="Treasurer">Treasurer</option>
              <option value="Councilor">Councilor</option>
              <option value="Tanod">Tanod</option>
              <option value="Clerk">Clerk</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label-sm">Access Level</label>
            <select name="access_level" class="form-control-sm-custom" required>
              <option value="Viewer">Viewer</option>
              <option value="Editor">Editor</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
        </div>

      </div><!-- /.modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn-modal-submit">
          <i class="bi bi-person-plus"></i> Create Account
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Edit Official
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-header-icon"><i class="bi bi-pencil-square"></i></div>
      <h2 class="modal-title">Edit Official</h2>
      <button class="modal-close-btn" onclick="closeModal('editModal')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <form action="staff-manage_action.php" method="post">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="user_id" id="editUserId">

      <div class="modal-body">
        <div class="modal-section-label">Account Details</div>

        <div class="form-group">
          <label class="form-label-sm">Username</label>
          <input type="text" name="username" id="editUsername" class="form-control-sm-custom" required
                 pattern="[a-zA-Z0-9._\-]{3,60}">
        </div>

        <div class="modal-section-label">Role & Permissions</div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label-sm">Position / Role</label>
            <select name="role_position" id="editRolePosition" class="form-control-sm-custom" required>
              <option value="Chairman">Chairman</option>
              <option value="Secretary">Secretary</option>
              <option value="Treasurer">Treasurer</option>
              <option value="Councilor">Councilor</option>
              <option value="Tanod">Tanod</option>
              <option value="Clerk">Clerk</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label-sm">Access Level</label>
            <select name="access_level" id="editAccessLevel" class="form-control-sm-custom" required>
              <option value="Viewer">Viewer</option>
              <option value="Editor">Editor</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn-modal-submit">
          <i class="bi bi-check-lg"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Reset Password
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="resetModal">
  <div class="modal-box" style="max-width:440px;">
    <div class="modal-header">
      <div class="modal-header-icon"><i class="bi bi-key"></i></div>
      <h2 class="modal-title">Reset Password</h2>
      <button class="modal-close-btn" onclick="closeModal('resetModal')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <form action="staff-manage_action.php" method="post">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="user_id" id="resetUserId">

      <div class="modal-body">
        <p style="font-size:.83rem; color:var(--text-mid); margin-bottom:1rem;">
          Set a new password for <strong id="resetUsername"></strong>.
        </p>

        <div class="form-group">
          <label class="form-label-sm">New Password</label>
          <div class="pw-wrap">
            <input type="password" name="new_password" id="resetPw" class="form-control-sm-custom"
                   placeholder="••••••••" required minlength="8">
            <button type="button" class="pw-toggle" onclick="togglePw('resetPw','resetPwEye')">
              <i class="bi bi-eye" id="resetPwEye"></i>
            </button>
          </div>
          <div class="strength-bar-wrap" id="resetStrengthBar">
            <div class="strength-seg" id="rs1"></div>
            <div class="strength-seg" id="rs2"></div>
            <div class="strength-seg" id="rs3"></div>
            <div class="strength-seg" id="rs4"></div>
          </div>
          <div class="strength-label" id="resetStrengthLabel">Enter a password</div>
        </div>

        <div class="form-group">
          <label class="form-label-sm">Confirm New Password</label>
          <div class="pw-wrap">
            <input type="password" name="confirm_new_password" id="resetPwC" class="form-control-sm-custom"
                   placeholder="••••••••" required>
            <button type="button" class="pw-toggle" onclick="togglePw('resetPwC','resetPwCEye')">
              <i class="bi bi-eye" id="resetPwCEye"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeModal('resetModal')">Cancel</button>
        <button type="submit" class="btn-modal-submit">
          <i class="bi bi-key"></i> Reset Password
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Activate / Deactivate confirm
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="toggleModal">
  <div class="modal-box" style="max-width:400px;">
    <div class="modal-header">
      <div class="modal-header-icon" id="toggleIcon"><i class="bi bi-lock"></i></div>
      <h2 class="modal-title" id="toggleTitle">Deactivate Account</h2>
      <button class="modal-close-btn" onclick="closeModal('toggleModal')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <form action="staff-manage_action.php" method="post">
      <input type="hidden" name="action" value="toggle_status">
      <input type="hidden" name="user_id" id="toggleUserId">
      <input type="hidden" name="new_status" id="toggleNewStatus">

      <div class="modal-body" style="text-align:center;">
        <div class="confirm-icon" id="toggleConfirmIcon">
          <i class="bi bi-lock" id="toggleConfirmIconInner"></i>
        </div>
        <p style="font-size:.85rem; color:var(--text-mid);" id="toggleMessage"></p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeModal('toggleModal')">Cancel</button>
        <button type="submit" class="btn-modal-submit" id="toggleSubmitBtn">
          Confirm
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ══ Scripts ══════════════════════════════════════════════════════════════ -->
<script src="../js/dashboard.js"></script>

<script>
// ── Modal open/close ──────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('show'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('show'); document.body.style.overflow=''; }

// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(overlay.id); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.show').forEach(m => closeModal(m.id));
});

// ── Add modal ────────────────────────────────────────────────────────────────
function openAddModal() { openModal('addModal'); }

// ── Edit modal ───────────────────────────────────────────────────────────────
function openEditModal(data) {
  document.getElementById('editUserId').value      = data.user_id;
  document.getElementById('editUsername').value    = data.username;
  document.getElementById('editRolePosition').value = data.role_position;
  document.getElementById('editAccessLevel').value  = data.access_level;
  openModal('editModal');
}

// ── Reset modal ──────────────────────────────────────────────────────────────
function openResetModal(userId, username) {
  document.getElementById('resetUserId').value  = userId;
  document.getElementById('resetUsername').textContent = username;
  openModal('resetModal');
}

// ── Toggle modal ─────────────────────────────────────────────────────────────
function openToggleModal(userId, username, newStatus) {
  document.getElementById('toggleUserId').value    = userId;
  document.getElementById('toggleNewStatus').value = newStatus;

  const isDeactivate = newStatus === 0;
  document.getElementById('toggleTitle').textContent =
    isDeactivate ? 'Deactivate Account' : 'Activate Account';
  document.getElementById('toggleMessage').innerHTML =
    isDeactivate
      ? `Are you sure you want to <strong>deactivate</strong> <strong>${username}</strong>? They will no longer be able to sign in.`
      : `Are you sure you want to <strong>activate</strong> <strong>${username}</strong>? They will regain system access.`;

  const iconEl   = document.getElementById('toggleConfirmIcon');
  const innerEl  = document.getElementById('toggleConfirmIconInner');
  const submitEl = document.getElementById('toggleSubmitBtn');

  if (isDeactivate) {
    iconEl.className   = 'confirm-icon danger';
    innerEl.className  = 'bi bi-lock-fill';
    submitEl.style.background = '#dc2626';
  } else {
    iconEl.className   = 'confirm-icon success';
    innerEl.className  = 'bi bi-unlock-fill';
    submitEl.style.background = '#16a34a';
  }

  openModal('toggleModal');
}

// ── Password toggles ─────────────────────────────────────────────────────────
function togglePw(fieldId, iconId) {
  const f = document.getElementById(fieldId);
  const i = document.getElementById(iconId);
  f.type = f.type === 'password' ? 'text' : 'password';
  i.classList.toggle('bi-eye'); i.classList.toggle('bi-eye-slash');
}

// ── Strength meter helper ────────────────────────────────────────────────────
function scorePassword(pw) {
  let s = 0;
  if (pw.length >= 8)           s++;
  if (pw.length >= 12)          s++;
  if (/[A-Z]/.test(pw))         s++;
  if (/[0-9]/.test(pw))         s++;
  if (/[^A-Za-z0-9]/.test(pw))  s++;
  return Math.min(s, 4);
}
const levels = [
  { cls:'active-weak',   text:'Weak'   },
  { cls:'active-fair',   text:'Fair'   },
  { cls:'active-good',   text:'Good'   },
  { cls:'active-strong', text:'Strong' },
];
function bindStrength(fieldId, segIds, labelId) {
  const field = document.getElementById(fieldId);
  const segs  = segIds.map(id => document.getElementById(id));
  const lbl   = document.getElementById(labelId);
  field.addEventListener('input', () => {
    const pw = field.value;
    const sc = pw.length ? scorePassword(pw) : 0;
    segs.forEach((s, i) => {
      s.className = 'strength-seg';
      if (pw.length && i < sc) s.classList.add(levels[sc - 1].cls);
    });
    lbl.textContent = pw.length ? levels[sc - 1].text : 'Enter a password';
  });
}
bindStrength('addPw',   ['as1','as2','as3','as4'], 'addStrengthLabel');
bindStrength('resetPw', ['rs1','rs2','rs3','rs4'], 'resetStrengthLabel');

// ── Resident live search (Add modal) ─────────────────────────────────────────
const resInput   = document.getElementById('residentSearchInput');
const resResults = document.getElementById('residentResults');
let searchTimer;

resInput?.addEventListener('input', () => {
  clearTimeout(searchTimer);
  const q = resInput.value.trim();
  if (q.length < 2) { resResults.classList.remove('show'); resResults.innerHTML = ''; return; }
  searchTimer = setTimeout(() => fetchResidents(q), 280);
});

async function fetchResidents(q) {
  try {
    const res  = await fetch(`staff-manage_search.php?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    renderResults(data);
  } catch { resResults.innerHTML = '<div style="padding:.6rem .9rem;font-size:.8rem;color:var(--text-muted);">Search error.</div>'; resResults.classList.add('show'); }
}

function renderResults(data) {
  if (!data.length) {
    resResults.innerHTML = '<div style="padding:.6rem .9rem;font-size:.8rem;color:var(--text-muted);">No residents found.</div>';
    resResults.classList.add('show'); return;
  }
  resResults.innerHTML = data.map(r => {
    const initials = (r.first_name[0] + (r.last_name[0] || '')).toUpperCase();
    return `<div class="resident-result-item" onclick="selectResident(${r.resident_id},'${escHtml(r.first_name+' '+r.last_name)}','${escHtml(r.email??'')}','${initials}')">
      <div class="res-avatar">${initials}</div>
      <div>
        <div class="res-name">${escHtml(r.first_name+' '+r.last_name)}</div>
        <div class="res-email">${escHtml(r.email??'No email')}</div>
      </div>
    </div>`;
  }).join('');
  resResults.classList.add('show');
}

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function selectResident(id, name, email, initials) {
  document.getElementById('selectedResidentId').value = id;
  document.getElementById('selAvatar').textContent    = initials;
  document.getElementById('selName').textContent      = name;
  document.getElementById('selEmail').textContent     = email || 'No email';
  document.getElementById('selectedResident').classList.add('show');
  document.getElementById('residentSearchArea').style.display = 'none';
  resResults.classList.remove('show');
}

function clearResident() {
  document.getElementById('selectedResidentId').value = '';
  document.getElementById('selectedResident').classList.remove('show');
  document.getElementById('residentSearchArea').style.display = '';
  resInput.value = '';
}

// Close results on outside click
document.addEventListener('click', e => {
  if (!resInput?.contains(e.target) && !resResults?.contains(e.target))
    resResults?.classList.remove('show');
});

// ── Table filter ─────────────────────────────────────────────────────────────
function applyFilters() {
  const text  = document.getElementById('filterInput').value.toLowerCase();
  const role  = document.getElementById('filterRole').value;
  const pos   = document.getElementById('filterPosition').value;
  const stat  = document.getElementById('filterStatus').value;
  const rows  = document.querySelectorAll('#tableBody tr[data-name]');
  let visible = 0;

  rows.forEach(row => {
    const nameMatch  = !text || row.dataset.name.includes(text);
    const roleMatch  = !role || row.dataset.role === role;
    const posMatch   = !pos  || row.dataset.position === pos;
    const statMatch  = stat === '' || row.dataset.status === stat;
    const show = nameMatch && roleMatch && posMatch && statMatch;
    row.classList.toggle('hidden-row', !show);
    if (show) visible++;
  });

  document.getElementById('rowCount').textContent =
    `Showing ${visible} official${visible !== 1 ? 's' : ''}`;
}

function clearFilters() {
  document.getElementById('filterInput').value  = '';
  document.getElementById('filterRole').value   = '';
  document.getElementById('filterPosition').value = '';
  document.getElementById('filterStatus').value = '';
  applyFilters();
}

['filterInput','filterRole','filterPosition','filterStatus'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', applyFilters);
  document.getElementById(id)?.addEventListener('change', applyFilters);
});
</script>

</body>
</html>

