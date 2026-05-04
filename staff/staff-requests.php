<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LGU eGov — Staff Requests</title>
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
        <span class="sidebar-brand-name">LGU eGov</span>
        <span class="sidebar-brand-sub">Municipal Portal</span>
      </div>
      <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="Collapse sidebar">
        <i class="bi bi-layout-sidebar-reverse"></i>
      </button>
    </div>
    <nav class="sidebar-nav">

      <div class="nav-section-label">Main</div>

      <a href="dashboard.php" class="nav-item" data-tooltip="Dashboard">
        <i class="bi bi-grid-fill nav-icon"></i>
        <span class="nav-label">Dashboard</span>
      </a>

      <a href="staff-requests.php" class="nav-item active" data-tooltip="Requests">
        <i class="bi bi-file-earmark-text nav-icon"></i>
        <span class="nav-label">Requests</span>
        <span class="nav-badge">12</span>
      </a>

      <a href="staff-notifications.php" class="nav-item" data-tooltip="Notifications">
        <i class="bi bi-bell nav-icon"></i>
        <span class="nav-label">Notifications</span>
        <span class="nav-badge">2</span>
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
        <div class="user-avatar">AC</div>
        <div class="user-info">
          <span class="user-name">Ana Cruz</span>
          <span class="user-role">Records Officer</span>
        </div>
        <button class="user-logout" title="Sign out">
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
        <button class="topbar-btn notif-btn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="notif-count">5</span>
        </button>
        <div class="topbar-profile">
          <div class="profile-avatar">AC</div>
          <div class="profile-info">
            <span class="profile-name">Ana Cruz</span>
            <span class="profile-dept">Records Section</span>
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
          <div class="stat-body"><span class="stat-value">14</span><span class="stat-label">Pending Requests</span></div>
          <div class="stat-trend warning"><i class="bi bi-arrow-down-short"></i> 3%</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#e6f7ef; color:#1a9e5f;"><i class="bi bi-check-circle-fill"></i></div>
          <div class="stat-body"><span class="stat-value">38</span><span class="stat-label">Completed Today</span></div>
          <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> 14%</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-people-fill"></i></div>
          <div class="stat-body"><span class="stat-value">9,120</span><span class="stat-label">Registered Residents</span></div>
          <div class="stat-trend neutral"><i class="bi bi-dash"></i> 0%</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#f0e8ff; color:#7c3aed;"><i class="bi bi-calendar-check-fill"></i></div>
          <div class="stat-body"><span class="stat-value">4</span><span class="stat-label">Awaiting Approval</span></div>
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
              <tr>
                <td>Marites Santos</td>
                <td>Barangay Clearance</td>
                <td>Apr 26, 2026</td>
                <td>High</td>
                <td><span class="badge bg-warning text-dark">Pending</span></td>
                <td><a href="staff-process-request.php" class="btn btn-outline-primary btn-sm">Process</a></td>
              </tr>
              <tr>
                <td>Jonathan Reyes</td>
                <td>Health Certificate</td>
                <td>Apr 26, 2026</td>
                <td>Normal</td>
                <td><span class="badge bg-info text-dark">In Review</span></td>
                <td><a href="staff-process-request.php" class="btn btn-outline-primary btn-sm">Process</a></td>
              </tr>
              <tr>
                <td>Rosa Villanueva</td>
                <td>Indigency Clearance</td>
                <td>Apr 25, 2026</td>
                <td>Medium</td>
                <td><span class="badge bg-danger">Urgent</span></td>
                <td><a href="staff-process-request.php" class="btn btn-outline-primary btn-sm">Process</a></td>
              </tr>
              <tr>
                <td>Carlos Mendoza</td>
                <td>Cedula</td>
                <td>Apr 24, 2026</td>
                <td>Normal</td>
                <td><span class="badge bg-success">Completed</span></td>
                <td><a href="staff-process-request.php" class="btn btn-outline-primary btn-sm">View</a></td>
              </tr>
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
              <li class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>Barangay Clearance</span><strong>6</strong></li>
              <li class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>Health Certificate</span><strong>3</strong></li>
              <li class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>Business Permit</span><strong>2</strong></li>
              <li class="d-flex justify-content-between align-items-center py-2"><span>Indigency</span><strong>3</strong></li>
            </ul>
          </div>
        </div>
        <div class="dash-card">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-people-fill"></i> Recent Residents</div>
          </div>
          <div class="dash-card-body">
            <div class="recent-list">
              <div class="recent-item">
                <div>
                  <div class="recent-title">Diwa Herrera</div>
                  <div class="recent-meta">04/26 • Barangay Clearance</div>
                </div>
                <span class="badge bg-success">Active</span>
              </div>
              <div class="recent-item">
                <div>
                  <div class="recent-title">Myra Lopez</div>
                  <div class="recent-meta">04/26 • Health Certificate</div>
                </div>
                <span class="badge bg-info text-dark">Review</span>
              </div>
              <div class="recent-item">
                <div>
                  <div class="recent-title">Antonio Diaz</div>
                  <div class="recent-meta">04/25 • Indigency</div>
                </div>
                <span class="badge bg-warning text-dark">Pending</span>
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


