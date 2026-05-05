<?php
require '../aut.php';
require '../config.php';

// ── Handle AJAX actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Cancel a request
    if ($_POST['action'] === 'cancel_request') {
        $requestId = intval($_POST['request_id'] ?? 0);

        // Verify ownership and that it's still cancellable
        $stmt = $pdo->prepare("
            SELECT request_id, status FROM service_request
            WHERE request_id = ? AND resident_id = ?
        ");
        $stmt->execute([$requestId, $_SESSION['user_id']]);
        $req = $stmt->fetch();

        if (!$req) {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit();
        }

        if (!in_array($req['status'], ['Pending', 'Processing'])) {
            echo json_encode(['success' => false, 'message' => 'This request can no longer be cancelled.']);
            exit();
        }

        $stmt = $pdo->prepare("
            UPDATE service_request SET status = 'Cancelled'
            WHERE request_id = ? AND resident_id = ?
        ");
        $ok = $stmt->execute([$requestId, $_SESSION['user_id']]);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Request cancelled successfully.' : 'Database error.'
        ]);
        exit();
    }

    // Get single request detail for modal
    if ($_POST['action'] === 'get_request') {
        $requestId = intval($_POST['request_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT sr.*, p.amount, p.payment_method, p.payment_status AS pay_status,
                   p.or_number, p.payment_date
            FROM service_request sr
            LEFT JOIN payment p ON p.request_id = sr.request_id
            WHERE sr.request_id = ? AND sr.resident_id = ?
        ");
        $stmt->execute([$requestId, $_SESSION['user_id']]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$req) {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit();
        }

        // Get extra fields
        $stmt = $pdo->prepare("
            SELECT field_key, field_value FROM service_request_detail
            WHERE request_id = ?
        ");
        $stmt->execute([$requestId]);
        $details = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Get files
        $stmt = $pdo->prepare("
            SELECT file_id, original_name, file_size, uploaded_at
            FROM service_request_file
            WHERE request_id = ?
        ");
        $stmt->execute([$requestId]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $req['reference_no'] = 'REQ-' . date('Y', strtotime($req['date_requested']))
                             . '-' . str_pad($req['request_id'], 6, '0', STR_PAD_LEFT);
        $req['details'] = $details;
        $req['files']   = $files;

        echo json_encode(['success' => true, 'request' => $req]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}

// ── Fetch all requests for this resident ─────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT sr.request_id, sr.document_type, sr.purpose, sr.status,
           sr.payment_status, sr.date_requested, sr.date_issued, sr.remarks,
           p.amount, p.payment_status AS pay_status
    FROM service_request sr
    LEFT JOIN payment p ON p.request_id = sr.request_id
    WHERE sr.resident_id = ?
    ORDER BY sr.date_requested DESC, sr.request_id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Counts per status ────────────────────────────────────────────────────────
$counts = ['all' => count($requests), 'Pending' => 0, 'Processing' => 0, 'Released' => 0, 'Denied' => 0, 'Cancelled' => 0];
foreach ($requests as $r) {
    if (isset($counts[$r['status']])) $counts[$r['status']]++;
}

// ── Fetch user ────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT first_name, last_name, resident_id FROM resident WHERE resident_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$initials   = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$fullName   = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
$firstname  = htmlspecialchars($user['first_name']);
$residentId = 'RES-' . str_pad($user['resident_id'], 5, '0', STR_PAD_LEFT);

// ── Status display helpers ────────────────────────────────────────────────────
function statusBadgeClass($status) {
    return match($status) {
        'Pending'        => 'pending',
        'Processing'     => 'processing',
        'Ready for Pickup', 'Released' => 'approved',
        'Denied', 'Cancelled' => 'rejected',
        default          => 'pending'
    };
}

function statusIcon($docType) {
    return match($docType) {
        'Barangay Clearance'               => ['bi-file-earmark-text-fill', '#e8f3fc', '#1a7fd4'],
        'Cedula / Community Tax Certificate' => ['bi-card-heading',         '#e6f7ef', '#1a9e5f'],
        'Business Permit'                  => ['bi-house-fill',             '#fef3c7', '#d97706'],
        'Health Certificate'               => ['bi-heart-pulse-fill',       '#fde8e8', '#dc2626'],
        'Certificate of Indigency'         => ['bi-people-fill',            '#f0e8ff', '#7c3aed'],
        'Real Property Tax'                => ['bi-cash-coin',              '#e8f5e8', '#16a34a'],
        'Scholarship Application'          => ['bi-mortarboard-fill',       '#e8f3fc', '#0369a1'],
        'Book an Appointment'              => ['bi-calendar-heart-fill',    '#fef3c7', '#b45309'],
        default                            => ['bi-file-earmark-fill',      '#f1f5f9', '#64748b'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KALASUNGAY — My Requests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/shared.css" rel="stylesheet">
  <link href="../css/resident-home.css" rel="stylesheet">
  <link href="../css/resident-requests.css" rel="stylesheet">
  <style>
  /* ── Modal detail layout ── */
  .rq-detail-section {
    margin-bottom: 1.25rem;
  }
  .rq-detail-label {
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: .6rem;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .rq-detail-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-light, #e5e7eb);
  }
  .rq-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .5rem .85rem;
  }
  .rq-detail-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }
  .rq-detail-key {
    font-size: .68rem;
    color: var(--text-muted);
    font-weight: 600;
  }
  .rq-detail-val {
    font-size: .82rem;
    color: var(--text-dark, #111);
    font-weight: 500;
  }

  /* ── File list ── */
  .rq-file-list {
    display: flex;
    flex-direction: column;
    gap: .5rem;
  }
  .rq-file-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: .6rem .85rem;
    background: var(--gray-50, #f9fafb);
    border: 1px solid var(--border-light, #e5e7eb);
    border-radius: 8px;
  }
  .rq-file-icon {
    width: 34px; height: 34px;
    border-radius: 7px;
    background: #e8f3fc;
    color: #1a7fd4;
    display: grid; place-items: center;
    font-size: .95rem;
    flex-shrink: 0;
  }
  .rq-file-icon.pdf  { background: #fde8e8; color: #dc2626; }
  .rq-file-icon.img  { background: #e6f7ef; color: #1a9e5f; }
  .rq-file-name {
    font-size: .8rem;
    font-weight: 600;
    color: var(--text-dark, #111);
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .rq-file-size {
    font-size: .68rem;
    color: var(--text-muted);
    font-family: 'DM Mono', monospace;
    flex-shrink: 0;
  }
  .rq-file-remove {
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: .8rem;
    padding: 3px 6px;
    border-radius: 5px;
    cursor: pointer;
    transition: color .15s, background .15s;
    flex-shrink: 0;
  }
  .rq-file-remove:hover {
    color: #dc2626;
    background: #fde8e8;
  }

  /* ── No files ── */
  .rq-no-files {
    text-align: center;
    padding: 1rem;
    font-size: .78rem;
    color: var(--text-muted);
    border: 1px dashed var(--border-light, #e5e7eb);
    border-radius: 8px;
  }

  /* ── Modal loading ── */
  .rq-modal-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem;
    gap: 10px;
    color: var(--text-muted);
    font-size: .83rem;
  }
  .rq-spinner {
    width: 18px; height: 18px;
    border: 2px solid var(--border-light, #e5e7eb);
    border-top-color: #1a7fd4;
    border-radius: 50%;
    animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── Status badge inside modal ── */
  .rq-modal-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .72rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 99px;
  }
  .rq-modal-status.pending    { background:#fef3c7; color:#92400e; }
  .rq-modal-status.processing { background:#e8f3fc; color:#1a7fd4; }
  .rq-modal-status.approved   { background:#e6f7ef; color:#166534; }
  .rq-modal-status.rejected   { background:#fde8e8; color:#991b1b; }
</style>
</head>
<body>

  <?php
  $active_nav = 'requests';
  require '../res-sidebar.php';
  ?>

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
        <a href="resident-notifications.php" class="r-topbar-btn" title="Notifications" style="position:relative;">
          <i class="bi bi-bell"></i>
          <?php if ($unread_notifs > 0): ?>
            <span class="r-notif-dot"></span>
          <?php endif; ?>
        </a>>
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
          <h1 class="rq-page-title">My Requests</h1>
          <p class="rq-page-sub">Track and manage all your service applications</p>
        </div>
        <a href="resident-home.php" class="rq-new-btn">
          <i class="bi bi-plus-lg"></i> New Request
        </a>
      </div>

      <!-- Summary strip -->
      <div class="rq-summary-strip">
        <div class="rq-summary-card" data-filter="all">
          <div class="rqs-value"><?= $counts['all'] ?></div>
          <div class="rqs-label">All Requests</div>
        </div>
        <div class="rq-summary-card" data-filter="pending">
          <div class="rqs-dot" style="background:#f59e0b;"></div>
          <div class="rqs-value"><?= $counts['Pending'] ?></div>
          <div class="rqs-label">Pending</div>
        </div>
        <div class="rq-summary-card" data-filter="processing">
          <div class="rqs-dot" style="background:#1a7fd4;"></div>
          <div class="rqs-value"><?= $counts['Processing'] ?></div>
          <div class="rqs-label">Processing</div>
        </div>
        <div class="rq-summary-card" data-filter="approved">
          <div class="rqs-dot" style="background:#1a9e5f;"></div>
          <div class="rqs-value"><?= $counts['Released'] ?></div>
          <div class="rqs-label">Released</div>
        </div>
        <div class="rq-summary-card" data-filter="rejected">
          <div class="rqs-dot" style="background:#dc2626;"></div>
          <div class="rqs-value"><?= $counts['Denied'] ?></div>
          <div class="rqs-label">Denied</div>
        </div>
      </div>

      <!-- Filters & search -->
      <div class="rq-toolbar">
        <div class="rq-search-wrap">
          <i class="bi bi-search rq-search-icon"></i>
          <input type="text" class="rq-search" id="rqSearch" placeholder="Search by reference, service type…">
        </div>
        <div class="rq-filters">
          <button class="rq-filter-btn active" data-filter="all">All</button>
          <button class="rq-filter-btn" data-filter="pending">Pending</button>
          <button class="rq-filter-btn" data-filter="processing">Processing</button>
          <button class="rq-filter-btn" data-filter="approved">Released</button>
          <button class="rq-filter-btn" data-filter="rejected">Denied</button>
        </div>
        <div class="rq-sort-wrap">
          <select class="rq-sort" id="rqSort">
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
            <option value="status">By Status</option>
          </select>
        </div>
      </div>

      <!-- Requests list -->
      <div class="rq-list" id="rqList">

        <?php if (empty($requests)): ?>
          <div class="rq-empty" id="rqEmpty">
            <div class="rq-empty-icon"><i class="bi bi-folder2-open"></i></div>
            <div class="rq-empty-title">No requests yet</div>
            <div class="rq-empty-sub">You haven't filed any service requests yet.</div>
            <a href="resident-home.php" class="rq-empty-link">
              <i class="bi bi-plus-lg"></i> Start a New Request
            </a>
          </div>
        <?php else: ?>
          <?php foreach ($requests as $req):
            $refNo     = 'REQ-' . date('Y', strtotime($req['date_requested'])) . '-' . str_pad($req['request_id'], 6, '0', STR_PAD_LEFT);
            $badgeClass = statusBadgeClass($req['status']);
            [$icon, $iconBg, $iconColor] = statusIcon($req['document_type']);
            $fee = $req['amount'] ? '₱' . number_format($req['amount'], 2) : ($req['payment_status'] === 'Exempted' ? 'Free' : 'Varies');
            $canCancel = in_array($req['status'], ['Pending', 'Processing']);
            $canDownload = $req['status'] === 'Released';
          ?>
          <div class="rq-item" data-id="<?= $req['request_id'] ?>" data-status="<?= strtolower($badgeClass) ?>" data-ref="<?= $refNo ?>">
            <div class="rq-item-icon" style="background:<?= $iconBg ?>; color:<?= $iconColor ?>;">
              <i class="bi <?= $icon ?>"></i>
            </div>
            <div class="rq-item-body">
              <div class="rq-item-top">
                <div class="rq-item-name"><?= htmlspecialchars($req['document_type']) ?></div>
                <span class="rq-status-badge <?= $badgeClass ?>">
                  <span class="rq-status-dot"></span> <?= $req['status'] ?>
                </span>
              </div>
              <div class="rq-item-ref">Ref # <strong><?= $refNo ?></strong></div>
              <div class="rq-item-meta">
                <span><i class="bi bi-calendar3"></i> Filed: <?= date('M j, Y', strtotime($req['date_requested'])) ?></span>
                <span><i class="bi bi-cash"></i> <?= $fee ?></span>
                <?php if ($req['date_issued']): ?>
                  <span><i class="bi bi-check-circle"></i> Released: <?= date('M j, Y', strtotime($req['date_issued'])) ?></span>
                <?php endif; ?>
              </div>
              <?php if ($req['remarks'] && in_array($req['status'], ['Denied', 'Cancelled'])): ?>
                <div class="rq-rejection-note">
                  <i class="bi bi-exclamation-triangle-fill"></i>
                  <span><strong>Reason:</strong> <?= htmlspecialchars($req['remarks']) ?></span>
                </div>
              <?php endif; ?>
            </div>
            <div class="rq-item-actions">
              <button class="rq-action-btn primary" onclick="viewRequest(<?= $req['request_id'] ?>)">
                <i class="bi bi-eye"></i> View
              </button>
              <?php if ($canCancel): ?>
                <button class="rq-action-btn danger" onclick="cancelRequest(<?= $req['request_id'] ?>)">
                  <i class="bi bi-x-lg"></i> Cancel
                </button>
              <?php endif; ?>
              <?php if ($canDownload): ?>
                <button class="rq-action-btn success" onclick="downloadDoc(<?= $req['request_id'] ?>)">
                  <i class="bi bi-download"></i> Download
                </button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <div class="rq-empty" id="rqEmpty" style="display:none;">
        <div class="rq-empty-icon"><i class="bi bi-folder2-open"></i></div>
        <div class="rq-empty-title">No requests found</div>
        <div class="rq-empty-sub">Try adjusting your filters or search term.</div>
        <a href="resident-home.php" class="rq-empty-link">
          <i class="bi bi-plus-lg"></i> Start a New Request
        </a>
      </div>

    </main>
  </div>

  <!-- Request detail modal -->
  <div class="rq-modal-backdrop" id="rqModalBackdrop" onclick="handleBackdropClick(event)">
      <div class="rq-modal" id="rqModal">

        <div class="rq-modal-header">
          <div class="rq-modal-title" id="rqModalTitle">Request Details</div>
          <button class="rq-modal-close" onclick="closeModal()">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="rq-modal-body" id="rqModalBody">
          <!-- Populated by JS -->
        </div>

      </div>
    </div>

  <div class="r-overlay" id="rOverlay"></div>

  <script src="../js/resident-home.js"></script>
  <script src="../js/resident-requests.js"></script>

<script>
// ── View request modal ────────────────────────────────────────────────────────
function viewRequest(requestId) {
  const backdrop = document.getElementById('rqModalBackdrop');
  const body     = document.getElementById('rqModalBody');
  const title    = document.getElementById('rqModalTitle');

  // Show modal with loading state
  backdrop.classList.add('open');
  document.body.style.overflow = 'hidden';
  title.textContent = 'Request Details';
  body.innerHTML = `
    <div class="rq-modal-loading">
      <div class="rq-spinner"></div> Loading request details…
    </div>
  `;

  // Fetch from server
  const fd = new FormData();
  fd.append('action', 'get_request');
  fd.append('request_id', requestId);

  fetch('resident-requests.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        body.innerHTML = `<div class="rq-modal-loading" style="color:#dc2626;">
          <i class="bi bi-exclamation-circle"></i> ${data.message}
        </div>`;
        return;
      }
      renderModal(data.request);
    })
    .catch(() => {
      body.innerHTML = `<div class="rq-modal-loading" style="color:#dc2626;">
        <i class="bi bi-exclamation-circle"></i> Network error. Please try again.
      </div>`;
    });
}

function cancelRequest(requestId) {
  if (!confirm('Are you sure you want to cancel this request?')) return;

  const fd = new FormData();
  fd.append('action', 'cancel_request');
  fd.append('request_id', requestId);

  fetch('resident-requests.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        alert(data.message || 'Unable to cancel request.');
        return;
      }

      const item = document.querySelector(`.rq-item[data-id="${requestId}"]`);
      if (item) {
        const badge = item.querySelector('.rq-status-badge');
        if (badge) {
          badge.className = 'rq-status-badge rejected';
          badge.innerHTML = '<span class="rq-status-dot"></span> Cancelled';
        }
        const cancelBtn = item.querySelector('.rq-action-btn.danger');
        if (cancelBtn) cancelBtn.remove();
      }

      alert(data.message || 'Request cancelled successfully.');
    })
    .catch(() => {
      alert('Network error. Please try again.');
    });
}

function downloadDoc(requestId) {
  const fd = new FormData();
  fd.append('action', 'get_request');
  fd.append('request_id', requestId);

  fetch('resident-requests.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        alert(data.message || 'Unable to download request file.');
        return;
      }

      const files = data.request.files || [];
      if (!files.length) {
        alert('No attached files are available for download.');
        return;
      }

      const firstFile = files[0];
      window.location.href = `../services/service-handler.php?action=download_file&file_id=${firstFile.file_id}`;
    })
    .catch(() => {
      alert('Network error. Please try again.');
    });
}

// ── Render modal content ──────────────────────────────────────────────────────
function renderModal(req) {
  const title = document.getElementById('rqModalTitle');
  const body  = document.getElementById('rqModalBody');

  title.textContent = req.document_type;

  // Status badge class
  const statusMap = {
    'Pending': 'pending', 'Processing': 'processing',
    'Ready for Pickup': 'approved', 'Released': 'approved',
    'Denied': 'rejected', 'Cancelled': 'rejected'
  };
  const badgeCls = statusMap[req.status] ?? 'pending';

  // Fee display
  const fee = req.amount
    ? '₱' + parseFloat(req.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})
    : (req.payment_status === 'Exempted' ? 'Free' : 'Varies');

  // Extra detail fields (from service_request_detail)
  let detailRows = '';
  if (req.details && Object.keys(req.details).length > 0) {
    detailRows = Object.entries(req.details).map(([k, v]) => `
      <div class="rq-detail-row">
        <span class="rq-detail-key">${formatKey(k)}</span>
        <span class="rq-detail-val">${v || '—'}</span>
      </div>
    `).join('');
  }

  // Files (from service_request_file)
  let filesHtml = '';
  if (req.files && req.files.length > 0) {
    filesHtml = req.files.map((f, idx) => {
      const ext     = f.original_name.split('.').pop().toLowerCase();
      const isImg   = ['jpg','jpeg','png','gif','webp'].includes(ext);
      const isPdf   = ext === 'pdf';
      const iconCls = isPdf ? 'pdf' : (isImg ? 'img' : '');
      const icon    = isPdf ? 'bi-file-earmark-pdf-fill'
                    : isImg ? 'bi-file-earmark-image-fill'
                    :         'bi-file-earmark-fill';
      const size    = f.file_size ? formatBytes(f.file_size) : '';

      return `
        <div class="rq-file-item" id="rq-file-${idx}">
          <div class="rq-file-icon ${iconCls}">
            <i class="bi ${icon}"></i>
          </div>
          <div class="rq-file-meta">
            <span class="rq-file-name" title="${escHtml(f.original_name)}">
              ${escHtml(f.original_name)}
            </span>
            <span class="rq-file-size">${size}</span>
          </div>
          <div class="rq-file-actions">
            <a href="../services/service-handler.php?action=download_file&file_id=${f.file_id}"
               class="btn btn-sm btn-outline-primary"
               target="_blank" rel="noopener">
              <i class="bi bi-download"></i> Download
            </a>
          </div>
        </div>
      `;
    }).join('');
  } else {
    filesHtml = '<div class="rq-no-files"><i class="bi bi-paperclip"></i> No files attached</div>';
  }

  body.innerHTML = `
    <!-- Status & ref -->
    <div class="rq-detail-section">
      <div class="rq-detail-label"><i class="bi bi-info-circle"></i> Overview</div>
      <div class="rq-detail-grid">
        <div class="rq-detail-row">
          <span class="rq-detail-key">Reference No.</span>
          <span class="rq-detail-val" style="font-family:'DM Mono',monospace;">
            ${req.reference_no}
          </span>
        </div>
        <div class="rq-detail-row">
          <span class="rq-detail-key">Status</span>
          <span class="rq-detail-val">
            <span class="rq-modal-status ${badgeCls}">${req.status}</span>
          </span>
        </div>
        <div class="rq-detail-row">
          <span class="rq-detail-key">Date Filed</span>
          <span class="rq-detail-val">${formatDate(req.date_requested)}</span>
        </div>
        <div class="rq-detail-row">
          <span class="rq-detail-key">Date Issued</span>
          <span class="rq-detail-val">${req.date_issued ? formatDate(req.date_issued) : '—'}</span>
        </div>
        <div class="rq-detail-row">
          <span class="rq-detail-key">Purpose</span>
          <span class="rq-detail-val">${req.purpose || '—'}</span>
        </div>
        <div class="rq-detail-row">
          <span class="rq-detail-key">Service Fee</span>
          <span class="rq-detail-val">${fee}</span>
        </div>
        ${req.payment_method ? `
        <div class="rq-detail-row">
          <span class="rq-detail-key">Payment Method</span>
          <span class="rq-detail-val">${req.payment_method}</span>
        </div>` : ''}
        ${req.or_number ? `
        <div class="rq-detail-row">
          <span class="rq-detail-key">OR Number</span>
          <span class="rq-detail-val" style="font-family:'DM Mono',monospace;">${req.or_number}</span>
        </div>` : ''}
      </div>
    </div>

    <!-- Extra fields (appointments, etc.) -->
    ${detailRows ? `
    <div class="rq-detail-section">
      <div class="rq-detail-label"><i class="bi bi-pencil-square"></i> Request Details</div>
      <div class="rq-detail-grid">${detailRows}</div>
    </div>` : ''}

    <!-- Remarks -->
    ${req.remarks ? `
    <div class="rq-detail-section">
      <div class="rq-detail-label"><i class="bi bi-chat-left-text"></i> Remarks</div>
      <div style="font-size:.82rem; color:var(--text-mid); background:var(--gray-50,#f9fafb);
                  border:1px solid var(--border-light,#e5e7eb); border-radius:8px; padding:.75rem .9rem;">
        ${escHtml(req.remarks)}
      </div>
    </div>` : ''}

    <!-- Attached files -->
    <div class="rq-detail-section">
      <div class="rq-detail-label"><i class="bi bi-paperclip"></i> Attached Files</div>
      <div class="rq-file-list" id="rqFileList">
        ${filesHtml}
      </div>
    </div>
  `;
}

// ── Remove file from VIEW only (no DB delete) ─────────────────────────────────
function removeFileFromView(idx) {
  const el = document.getElementById(`rq-file-${idx}`);
  if (!el) return;

  // Animate out
  el.style.transition = 'opacity .2s, transform .2s';
  el.style.opacity    = '0';
  el.style.transform  = 'translateX(10px)';

  setTimeout(() => {
    el.remove();

    // If no files left, show empty message
    const list = document.getElementById('rqFileList');
    if (list && list.children.length === 0) {
      list.innerHTML = '<div class="rq-no-files"><i class="bi bi-paperclip"></i> No files attached</div>';
    }
  }, 200);
}

// ── Close modal ───────────────────────────────────────────────────────────────
function closeModal() {
  document.getElementById('rqModalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}

function handleBackdropClick(e) {
  if (e.target === document.getElementById('rqModalBackdrop')) closeModal();
}

// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatBytes(bytes) {
  if (!bytes) return '';
  if (bytes < 1024)        return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function formatKey(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

</body>
</html>

