<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — Help & Support</title>
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

      <a href="dashboard.php" class="nav-item" data-tooltip="Dashboard">
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
        <span class="nav-badge">2</span>
      </a>

      <div class="nav-divider"></div>
      <div class="nav-section-label">Operations</div>

      <a href="staff-settings.php" class="nav-item" data-tooltip="Settings">
        <i class="bi bi-gear nav-icon"></i>
        <span class="nav-label">Settings</span>
      </a>

      <a href="staff-help.php" class="nav-item active" data-tooltip="Help & Support">
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
            <i class="bi bi-question-circle"></i> Help & Support
          </span>
        </nav>
      </div>
      <div class="topbar-center">
        <div class="topbar-search">
          <i class="bi bi-search search-icon"></i>
          <input type="text" class="search-input" placeholder="Search help topics…">
          <kbd class="search-kbd">⌘K</kbd>
        </div>
      </div>
      <div class="topbar-right">
        <button class="topbar-btn notif-btn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="notif-count">1</span>
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
          <h1 class="page-title">Help & Support</h1>
          <p class="page-subtitle">Find answers, submit inquiries, and contact support from one place.</p>
        </div>
        <div class="content-header-right">
          <button class="btn-outline-nav">
            <i class="bi bi-download"></i> Export
          </button>
          <button class="btn-primary-nav"><i class="bi bi-envelope"></i> Contact Helpdesk</button>
        </div>
      </div>
      <div class="dashboard-grid">
        <div class="dash-card dash-card-wide">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-headset"></i> Support Resources</div>
          </div>
          <div class="dash-card-body">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="help-card p-4 rounded-3 border">
                  <div class="help-icon mb-3"><i class="bi bi-phone-fill"></i></div>
                  <h5>Call the helpdesk</h5>
                  <p class="text-muted">Available Mon–Fri, 8AM–5PM.</p>
                  <p class="mb-0"><strong>(088) 123-4567</strong></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="help-card p-4 rounded-3 border">
                  <div class="help-icon mb-3"><i class="bi bi-envelope-fill"></i></div>
                  <h5>Email support</h5>
                  <p class="text-muted">Send a request for technical or process help.</p>
                  <p class="mb-0"><strong>support@lgu.gov.ph</strong></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="dashboard-grid">
        <div class="dash-card dash-card-wide">
          <div class="dash-card-header">
            <div class="dash-card-title"><i class="bi bi-question-circle-fill"></i> Frequently Asked Questions</div>
          </div>
          <div class="dash-card-body">
            <div class="accordion" id="helpAccordion">
              <div class="accordion-item">
                <h2 class="accordion-header" id="faqOne">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">How do I approve a request?</button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                  <div class="accordion-body">Open the request from the Requests page, review supporting documents, then select Approve or Decline.</div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="faqTwo">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">How do I update my profile?</button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                  <div class="accordion-body">Go to Settings and update your profile information. Save changes after editing.</div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="faqThree">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">Who do I contact for technical issues?</button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                  <div class="accordion-body">Contact the helpdesk at the number or email shown above, or raise a ticket with your unit head.</div>
                </div>
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


