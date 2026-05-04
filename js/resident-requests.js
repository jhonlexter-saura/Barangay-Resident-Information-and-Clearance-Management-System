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

// Page-specific request actions are implemented in resident-requests.php.

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