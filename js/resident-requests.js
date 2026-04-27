// ============================================
// resident-requests.js
// Shared logic for My Requests and Notifications pages
// ============================================

// ════════════════════════════════════════════
// MY REQUESTS PAGE
// ════════════════════════════════════════════

// ── Filter & search ──
const filterBtns  = document.querySelectorAll('.rq-filter-btn');
const summaryCrds = document.querySelectorAll('.rq-summary-card');
const searchInput = document.getElementById('rqSearch');
const sortSelect  = document.getElementById('rqSort');
const rqList      = document.getElementById('rqList');
const rqEmpty     = document.getElementById('rqEmpty');

let activeFilter = 'all';
let searchTerm   = '';

function getItems() {
  return rqList ? Array.from(rqList.querySelectorAll('.rq-item')) : [];
}

function applyFiltersAndSearch() {
  if (!rqList) return;

  const items   = getItems();
  let   visible = 0;

  items.forEach(item => {
    const status = item.dataset.status || '';
    const ref    = (item.dataset.ref || '').toLowerCase();
    const name   = (item.querySelector('.rq-item-name')?.textContent || '').toLowerCase();

    const matchesFilter = activeFilter === 'all' || status === activeFilter;
    const matchesSearch = !searchTerm || ref.includes(searchTerm) || name.includes(searchTerm);

    if (matchesFilter && matchesSearch) {
      item.style.display = '';
      visible++;
    } else {
      item.style.display = 'none';
    }
  });

  if (rqEmpty) rqEmpty.style.display = visible === 0 ? 'flex' : 'none';
}

function applySort() {
  if (!rqList || !sortSelect) return;
  const val   = sortSelect.value;
  const items = getItems();

  items.sort((a, b) => {
    if (val === 'status') {
      const order = { pending: 0, processing: 1, approved: 2, rejected: 3 };
      return (order[a.dataset.status] || 9) - (order[b.dataset.status] || 9);
    }
    const refA = a.dataset.ref || '';
    const refB = b.dataset.ref || '';
    return val === 'newest' ? refB.localeCompare(refA) : refA.localeCompare(refB);
  });

  items.forEach(item => rqList.appendChild(item));
}

// Filter buttons
filterBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeFilter = btn.dataset.filter;
    applyFiltersAndSearch();
  });
});

// Summary cards (also act as filters)
summaryCrds.forEach(card => {
  card.addEventListener('click', () => {
    const filter = card.dataset.filter;
    activeFilter = filter;

    summaryCrds.forEach(c => c.classList.remove('active'));
    card.classList.add('active');

    filterBtns.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.filter === filter);
    });

    applyFiltersAndSearch();
  });
});

// Search
searchInput?.addEventListener('input', e => {
  searchTerm = e.target.value.toLowerCase().trim();
  applyFiltersAndSearch();
});

// Sort
sortSelect?.addEventListener('change', () => { applySort(); applyFiltersAndSearch(); });

// ── View request modal ──
const MOCK_REQUESTS = {
  '2026-04-0841': {
    name: 'Barangay Clearance', status: 'pending',
    filed: 'April 21, 2026', fee: '₱50.00',
    purpose: 'Employment', office: 'Barangay Hall',
    release: 'April 23, 2026', payMethod: 'Pay at Counter',
  },
  '2026-03-0712': {
    name: 'Cedula / Community Tax Certificate', status: 'processing',
    filed: 'March 15, 2026', fee: '₱30.00',
    purpose: 'Annual Requirement', office: 'Treasurer\'s Office',
    release: 'Today', payMethod: 'Pay at Counter',
  },
  '2026-02-0589': {
    name: 'Health Certificate', status: 'approved',
    filed: 'February 20, 2026', fee: '₱100.00',
    purpose: 'Employment — Food Handler', office: 'Municipal Health Office',
    release: 'February 21, 2026', payMethod: 'Paid at Counter',
  },
  '2026-01-0441': {
    name: 'Indigency Certificate', status: 'approved',
    filed: 'January 10, 2026', fee: 'Free',
    purpose: 'Scholarship Application', office: 'MSWD Office',
    release: 'January 10, 2026', payMethod: 'N/A — Free',
  },
  '2025-12-0388': {
    name: 'Business Permit', status: 'rejected',
    filed: 'December 5, 2025', fee: 'Varies',
    purpose: 'New Application', office: 'Business Permits & Licensing',
    release: 'N/A', payMethod: 'N/A',
  },
  '2025-11-0301': {
    name: 'Real Property Tax Payment', status: 'approved',
    filed: 'November 3, 2025', fee: '₱3,750.00',
    purpose: 'Annual Tax Payment', office: 'Treasurer\'s Office',
    release: 'November 3, 2025', payMethod: 'Paid at Counter',
  },
  '2025-09-0244': {
    name: 'Scholarship Application', status: 'approved',
    filed: 'September 12, 2025', fee: 'Free',
    purpose: 'SY 2025–2026 Application', office: 'MSWD Office',
    release: 'September 20, 2025', payMethod: 'N/A — Free',
  },
  '2026-04-0862': {
    name: 'Book Appointment — Civil Registrar', status: 'pending',
    filed: 'April 22, 2026', fee: 'Free',
    purpose: 'Document follow-up', office: 'Civil Registrar',
    release: 'April 25, 2026 10:00 AM', payMethod: 'N/A — Free',
  },
};

function viewRequest(ref) {
  const data     = MOCK_REQUESTS[ref];
  const backdrop = document.getElementById('rqModalBackdrop');
  const title    = document.getElementById('rqModalTitle');
  const body     = document.getElementById('rqModalBody');
  if (!data || !backdrop) return;

  const statusColors = {
    pending:    { bg: '#fef3c7', color: '#92400e' },
    processing: { bg: '#e8f3fc', color: '#1366b0' },
    approved:   { bg: '#e6f7ef', color: '#166534' },
    rejected:   { bg: '#fee2e2', color: '#991b1b' },
  };

  const sc = statusColors[data.status] || statusColors.pending;

  title.textContent = data.name;
  body.innerHTML = `
    <div class="rq-modal-row">
      <span class="rq-modal-key">Reference Number</span>
      <span class="rq-modal-val" style="font-family:'DM Mono',monospace;">#${ref}</span>
    </div>
    <div class="rq-modal-row">
      <span class="rq-modal-key">Status</span>
      <span class="rq-modal-val">
        <span style="background:${sc.bg}; color:${sc.color}; font-size:0.72rem; font-weight:700; padding:3px 10px; border-radius:999px; text-transform:capitalize;">
          ${data.status}
        </span>
      </span>
    </div>
    <div class="rq-modal-row">
      <span class="rq-modal-key">Service</span>
      <span class="rq-modal-val">${data.name}</span>
    </div>
    <div class="rq-modal-row">
      <span class="rq-modal-key">Purpose</span>
      <span class="rq-modal-val">${data.purpose}</span>
    </div>
    <div class="rq-modal-row">
      <span class="rq-modal-key">Processing Office</span>
      <span class="rq-modal-val">${data.office}</span>
    </div>
    <div class="rq-modal-row">
      <span class="rq-modal-key">Date Filed</span>
      <span class="rq-modal-val">${data.filed}</span>
    </div>
    <div class="rq-modal-row">
      <span class="rq-modal-key">Est. Release / Date</span>
      <span class="rq-modal-val">${data.release}</span>
    </div>
    <div class="rq-modal-row">
      <span class="rq-modal-key">Fee</span>
      <span class="rq-modal-val" style="color:var(--sky);">${data.fee}</span>
    </div>
    <div class="rq-modal-row">
      <span class="rq-modal-key">Payment Method</span>
      <span class="rq-modal-val">${data.payMethod}</span>
    </div>
    ${data.status === 'approved' ? `
    <div style="margin-top:1rem;">
      <button onclick="downloadDoc('${ref}')" style="width:100%; background:var(--green); color:white; border:none; border-radius:8px; font-family:'Plus Jakarta Sans',sans-serif; font-size:0.85rem; font-weight:700; padding:0.68rem 1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:7px; transition:opacity 0.2s;">
        <i class="bi bi-download"></i> Download Document
      </button>
    </div>` : ''}
    ${data.status === 'rejected' ? `
    <div style="margin-top:1rem; background:var(--red-light); border-left:3px solid var(--red); border-radius:0 8px 8px 0; padding:0.75rem 1rem; font-size:0.78rem; color:#7f1d1d; line-height:1.5;">
      <strong>Rejection Reason:</strong> Incomplete documents. Please re-apply with complete requirements.
    </div>` : ''}
  `;

  backdrop.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  const backdrop = document.getElementById('rqModalBackdrop');
  if (backdrop) backdrop.classList.remove('open');
  document.body.style.overflow = '';
}

// Close modal on backdrop click
document.getElementById('rqModalBackdrop')?.addEventListener('click', e => {
  if (e.target.id === 'rqModalBackdrop') closeModal();
});

// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
});

function cancelRequest(ref) {
  if (!confirm(`Are you sure you want to cancel request #${ref}?`)) return;
  const item = document.querySelector(`[data-ref="${ref}"]`);
  if (item) {
    item.style.opacity = '0.4';
    item.style.pointerEvents = 'none';
    item.querySelector('.rq-status-badge').className = 'rq-status-badge rejected';
    item.querySelector('.rq-status-badge').innerHTML = '<span class="rq-status-dot"></span> Cancelled';
    showRqToast(`Request #${ref} has been cancelled.`, 'info');
  }
}

function reapply(ref) {
  window.location.href = 'resident-home.html';
}

function downloadDoc(ref) {
  showRqToast(`Preparing document for Ref #${ref}…`, 'success');
}

// ── Toast ──
function showRqToast(msg, type = 'success') {
  document.querySelector('.rq-toast-el')?.remove();
  const colors = {
    success: { bg:'#e6f7ef', border:'#1a9e5f', color:'#166534', icon:'bi-check-circle-fill' },
    error:   { bg:'#fee2e2', border:'#dc2626', color:'#991b1b', icon:'bi-x-circle-fill' },
    info:    { bg:'#e8f3fc', border:'#1a7fd4', color:'#1a7fd4', icon:'bi-info-circle-fill' },
  };
  const c = colors[type] || colors.info;
  const t = document.createElement('div');
  t.className = 'rq-toast-el';
  t.style.cssText = `position:fixed;bottom:1.5rem;right:1.5rem;background:${c.bg};border:1.5px solid ${c.border};color:${c.color};border-radius:10px;padding:0.72rem 1.1rem;font-family:'Plus Jakarta Sans',sans-serif;font-size:0.82rem;font-weight:600;display:flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(0,0,0,0.1);z-index:9999;animation:slideInT 0.25s ease both;`;
  t.innerHTML = `<i class="bi ${c.icon}"></i> ${msg}`;
  document.body.appendChild(t);
  if (!document.getElementById('rqToastStyle')) {
    const s = document.createElement('style');
    s.id = 'rqToastStyle';
    s.textContent = `@keyframes slideInT{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}`;
    document.head.appendChild(s);
  }
  setTimeout(() => t.remove(), 3500);
}

// ════════════════════════════════════════════
// NOTIFICATIONS PAGE
// ════════════════════════════════════════════

const notifTabs   = document.querySelectorAll('.notif-tab');
const notifList   = document.getElementById('notifList');
const notifEmpty  = document.getElementById('notifEmpty');
let   activeTab   = 'all';

notifTabs.forEach(tab => {
  tab.addEventListener('click', () => {
    notifTabs.forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    activeTab = tab.dataset.tab;
    filterNotifs();
  });
});

function filterNotifs() {
  if (!notifList) return;
  const items   = notifList.querySelectorAll('.notif-item');
  let   visible = 0;

  items.forEach(item => {
    const type   = item.dataset.type || '';
    const unread = item.classList.contains('unread');

    const show = activeTab === 'all'
      || (activeTab === 'unread' && unread)
      || type === activeTab;

    item.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  if (notifEmpty) notifEmpty.style.display = visible === 0 ? 'flex' : 'none';
}

function dismissNotif(id) {
  const item = document.querySelector(`[data-id="${id}"]`);
  if (!item) return;
  item.style.transition = 'opacity 0.25s, transform 0.25s';
  item.style.opacity    = '0';
  item.style.transform  = 'translateX(20px)';
  setTimeout(() => {
    item.remove();
    updateUnreadCount();
    filterNotifs();
  }, 260);
}

function markAllRead() {
  const unread = notifList?.querySelectorAll('.notif-item.unread') || [];
  unread.forEach(item => {
    item.classList.remove('unread');
    item.querySelector('.notif-dot-indicator')?.remove();
  });
  updateUnreadCount();
  showRqToast('All notifications marked as read.', 'success');
}

function updateUnreadCount() {
  const count = notifList?.querySelectorAll('.notif-item.unread').length || 0;
  const el    = document.getElementById('unreadCount');
  if (el) el.textContent = count;

  // Update tab count
  const unreadTabCount = document.querySelector('[data-tab="unread"] .notif-tab-count');
  if (unreadTabCount) unreadTabCount.textContent = count;

  // Update sidebar badge
  const sidebarBadge = document.querySelector('.r-nav-item.active .r-nav-badge');
  if (sidebarBadge) sidebarBadge.textContent = count;
}