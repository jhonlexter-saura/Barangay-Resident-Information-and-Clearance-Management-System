// ============================================
// dashboard.js
// LGU eGov Dashboard — Sidebar, Topbar, UI interactions
// ============================================

document.addEventListener('DOMContentLoaded', () => {
  
  const sidebar = document.getElementById('sidebar');
  const mainArea = document.getElementById('mainArea');
  const collapseBtn = document.getElementById('sidebarCollapseBtn');
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const overlay = document.getElementById('sidebarOverlay');
  const searchInput = document.querySelector('.search-input');
  
  // ── Sidebar collapse (desktop) ──
  let isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  
  function applySidebarState() {
    if (isCollapsed) {
      sidebar.classList.add('collapsed');
      mainArea.classList.add('collapsed');
      collapseBtn.setAttribute('aria-label', 'Expand sidebar');
    } else {
      sidebar.classList.remove('collapsed');
      mainArea.classList.remove('collapsed');
      collapseBtn.setAttribute('aria-label', 'Collapse sidebar');
    }
  }
  
  applySidebarState();
  
  collapseBtn?.addEventListener('click', () => {
    // Only collapse on desktop
    if (window.innerWidth > 768) {
      isCollapsed = !isCollapsed;
      localStorage.setItem('sidebarCollapsed', isCollapsed);
      applySidebarState();
    }
  });
  
  // ── Mobile sidebar toggle ──
  function openMobileSidebar() {
    sidebar.classList.add('mobile-open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  
  function closeMobileSidebar() {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }
  
  mobileMenuBtn?.addEventListener('click', openMobileSidebar);
  overlay?.addEventListener('click', closeMobileSidebar);
  
  // Close sidebar on nav item click (mobile)
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 768) closeMobileSidebar();
    });
  });
  
  // ── Keyboard shortcut: ⌘K / Ctrl+K for search ──
  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      searchInput?.focus();
      searchInput?.select();
    }
    if (e.key === 'Escape') {
      searchInput?.blur();
      if (window.innerWidth <= 768) closeMobileSidebar();
    }
  });
  
  // ── Active nav item ──
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
      this.classList.add('active');
      
      // Update breadcrumb
      const label = this.querySelector('.nav-label')?.textContent?.trim();
      const breadcrumb = document.querySelector('.breadcrumb-item');
      if (label && breadcrumb) {
        const icon = this.querySelector('.nav-icon')?.className || '';
        breadcrumb.innerHTML = `<i class="${icon}"></i> ${label}`;
      }
    });
  });
  
  // ── Responsive: reset sidebar on window resize ──
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
      closeMobileSidebar();
      applySidebarState();
    }
  });
  
});