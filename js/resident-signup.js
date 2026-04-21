// ============================================
// resident-signup.js
// Form validation, password strength meter,
// password match check, show/hide toggles
// ============================================

document.addEventListener('DOMContentLoaded', () => {
  
  // ── Element references ──
  const form = document.getElementById('signupForm');
  const firstnameInput = document.getElementById('firstname');
  const lastnameInput = document.getElementById('lastname');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('confirmPassword');
  const termsCheckbox = document.getElementById('terms');
  
  const strengthFill = document.getElementById('strengthFill');
  const strengthLabel = document.getElementById('strengthLabel');
  const matchIndicator = document.getElementById('matchIndicator');
  
  // ── Show/hide password toggles ──
  function makeToggle(btnId, inputEl, iconId) {
    const btn = document.getElementById(btnId);
    const icon = document.getElementById(iconId);
    btn?.addEventListener('click', () => {
      const isHidden = inputEl.type === 'password';
      inputEl.type = isHidden ? 'text' : 'password';
      icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
      btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  }
  
  makeToggle('togglePassword', passwordInput, 'pwEyeIcon');
  makeToggle('toggleConfirm', confirmInput, 'confirmEyeIcon');
  
  // ── Password strength meter ──
  function getStrength(pw) {
    let score = 0;
    if (pw.length >= 8) score++;
    if (pw.length >= 12) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return score;
  }
  
  const strengthLevels = [
    { min: 0, cls: '', label: 'Enter a password' },
    { min: 1, cls: 'weak', label: 'Weak' },
    { min: 2, cls: 'fair', label: 'Fair' },
    { min: 4, cls: 'good', label: 'Good' },
    { min: 5, cls: 'strong', label: 'Strong' },
  ];
  
  passwordInput?.addEventListener('input', () => {
    const pw = passwordInput.value;
    const score = pw.length === 0 ? 0 : getStrength(pw);
    
    // Pick level
    let level = strengthLevels[0];
    for (const l of strengthLevels) {
      if (score >= l.min) level = l;
    }
    
    strengthFill.className = `strength-fill ${level.cls}`;
    strengthLabel.className = `strength-label ${level.cls}`;
    strengthLabel.textContent = level.label;
    
    // Also re-check match if confirm has a value
    if (confirmInput.value) checkMatch();
  });
  
  // ── Password match check ──
  function checkMatch() {
    const pw = passwordInput.value;
    const cpw = confirmInput.value;
    
    if (!cpw) {
      matchIndicator.className = 'match-indicator';
      matchIndicator.textContent = '';
      return;
    }
    
    if (pw === cpw) {
      matchIndicator.className = 'match-indicator match';
      matchIndicator.innerHTML = '<i class="bi bi-check-circle-fill"></i> Passwords match';
      confirmInput.classList.add('is-valid');
      confirmInput.classList.remove('is-invalid');
      clearError('confirm');
    } else {
      matchIndicator.className = 'match-indicator no-match';
      matchIndicator.innerHTML = '<i class="bi bi-x-circle-fill"></i> Passwords do not match';
      confirmInput.classList.add('is-invalid');
      confirmInput.classList.remove('is-valid');
    }
  }
  
  confirmInput?.addEventListener('input', checkMatch);
  
  // ── Field helpers ──
  function showError(fieldId, message) {
    const el = document.getElementById(`${fieldId}-error`);
    if (!el) return;
    el.innerHTML = `<i class="bi bi-exclamation-circle-fill"></i> ${message}`;
    el.classList.add('visible');
  }
  
  function clearError(fieldId) {
    const el = document.getElementById(`${fieldId}-error`);
    if (!el) return;
    el.textContent = '';
    el.classList.remove('visible');
  }
  
  function markValid(input) {
    input.classList.add('is-valid');
    input.classList.remove('is-invalid');
  }
  
  function markInvalid(input) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
  }
  
  // Clear errors on input
  [firstnameInput, lastnameInput, emailInput, passwordInput].forEach(input => {
    const id = input?.id;
    input?.addEventListener('input', () => {
      clearError(id);
      input.classList.remove('is-invalid');
    });
  });
  
  // ── Form submission & validation ──
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    
    let valid = true;
    
    // First name
    if (!firstnameInput.value.trim()) {
      showError('firstname', 'First name is required');
      markInvalid(firstnameInput);
      valid = false;
    } else {
      clearError('firstname');
      markValid(firstnameInput);
    }
    
    // Last name
    if (!lastnameInput.value.trim()) {
      showError('lastname', 'Last name is required');
      markInvalid(lastnameInput);
      valid = false;
    } else {
      clearError('lastname');
      markValid(lastnameInput);
    }
    
    // Email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailInput.value.trim()) {
      showError('email', 'Email address is required');
      markInvalid(emailInput);
      valid = false;
    } else if (!emailRegex.test(emailInput.value.trim())) {
      showError('email', 'Please enter a valid email address');
      markInvalid(emailInput);
      valid = false;
    } else {
      clearError('email');
      markValid(emailInput);
    }
    
    // Password
    if (!passwordInput.value) {
      showError('password', 'Password is required');
      markInvalid(passwordInput);
      valid = false;
    } else if (passwordInput.value.length < 8) {
      showError('password', 'Password must be at least 8 characters');
      markInvalid(passwordInput);
      valid = false;
    } else {
      clearError('password');
      markValid(passwordInput);
    }
    
    // Confirm password
    if (!confirmInput.value) {
      showError('confirm', 'Please confirm your password');
      markInvalid(confirmInput);
      valid = false;
    } else if (confirmInput.value !== passwordInput.value) {
      showError('confirm', 'Passwords do not match');
      markInvalid(confirmInput);
      valid = false;
    } else {
      clearError('confirm');
      markValid(confirmInput);
    }
    
    // Terms
    if (!termsCheckbox.checked) {
      showError('terms', 'You must agree to the Terms of Service');
      valid = false;
    } else {
      clearError('terms');
    }
    
    if (!valid) return;
    
    // ── All valid — simulate submission ──
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Creating account…';
    
    // TODO: Replace with actual API call
    setTimeout(() => {
      btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Account Created!';
      btn.style.background = 'linear-gradient(135deg, #1a9e5f, #147a48)';
      // Redirect to login after short delay
      setTimeout(() => {
        window.location.href = 'resident-portal.html';
      }, 1500);
    }, 1800);
  });
  
});