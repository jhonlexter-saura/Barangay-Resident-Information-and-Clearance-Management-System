// ============================================
// lgu-login.js
// Scripts for the LGU Staff Login page
// ============================================

document.addEventListener('DOMContentLoaded', () => {
  
  // ── Password show/hide toggle ──
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');
  const toggleBtn = document.querySelector('.toggle-pw');
  
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
      
      // Basic client-side validation
      if (!email || !password) {
        alert('Please fill in all required fields.');
        return;
      }
      
      if (password.length < 8) {
        alert('Password must be at least 8 characters long.');
        return;
      }
      
      // TODO: Replace with actual authentication API call
      console.log('Staff login submitted for:', email);
    });
  }
  
});