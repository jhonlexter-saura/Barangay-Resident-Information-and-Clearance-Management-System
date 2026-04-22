// ============================================
// resident-home.js
// Shared sidebar, mobile menu, and UI interactions
// for resident-home.html and resident-profile.html
// ============================================

document.addEventListener('DOMContentLoaded', () => {

  const sidebar  = document.getElementById('rSidebar');
  const overlay  = document.getElementById('rOverlay');
  const menuBtn  = document.getElementById('rMenuBtn');

  // ── Mobile sidebar open/close ──
  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
  }

  menuBtn?.addEventListener('click', openSidebar);
  overlay?.addEventListener('click', closeSidebar);

  // Close on nav item click (mobile)
  document.querySelectorAll('.r-nav-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 768) closeSidebar();
    });
  });

  // Reset on resize
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeSidebar();
  });

  // ── Set today's date in welcome banner ──
  const dateEl = document.getElementById('welcomeDate');
  if (dateEl) {
    const now = new Date();
    dateEl.textContent = now.toLocaleDateString('en-PH', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
  }

  // ── Profile quick link smooth scroll + active ──
  document.querySelectorAll('.pql-item').forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      document.querySelectorAll('.pql-item').forEach(l => l.classList.remove('active'));
      this.classList.add('active');
      const targetId = this.getAttribute('data-section');
      const target   = document.getElementById(targetId);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

});