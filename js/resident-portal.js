// ============================================
// resident-portal.js
// Scripts for the MySerbisyo Resident Portal page
// ============================================

document.addEventListener('DOMContentLoaded', () => {
  
  // ── Password show/hide toggle ──
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');
  const toggleBtn = document.querySelector('.input-end-icon');
  
  if (toggleBtn && passwordInput && eyeIcon) {
    toggleBtn.addEventListener('click', () => {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      eyeIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
      toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  }
  
  // ── Form submission handler ──
  const form = document.querySelector('form');
  
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      
      if (!email || !password) {
        alert('Please fill in all required fields.');
        return;
      }
      
      // TODO: Replace with actual resident authentication API call
      console.log('Resident login submitted for:', email);
    });
  }
  
  // ── PhilSys button handler ──
  const philsysBtn = document.querySelector('.btn-philsys');
  
  if (philsysBtn) {
    philsysBtn.addEventListener('click', () => {
      // TODO: Redirect to PhilSys SSO endpoint
      console.log('PhilSys login initiated');
      alert('PhilSys authentication coming soon.');
    });
  }
  
});