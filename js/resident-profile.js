// ============================================
// resident-profile.js
// Edit sections, password change, save handler
// ============================================

// ── Track which sections are in edit mode ──
const editingSections = new Set();

// ── Toggle edit mode for a section ──
function toggleEdit(sectionId) {
  const section = document.getElementById(sectionId);
  if (!section) return;
  
  const inputs = section.querySelectorAll('.prof-input');
  const editBtn = section.querySelector('.prof-edit-btn');
  const saveBtn = document.getElementById('saveBtn');
  
  if (editingSections.has(sectionId)) {
    // Cancel edit — restore disabled
    editingSections.delete(sectionId);
    inputs.forEach(input => { input.disabled = true; });
    if (editBtn) {
      editBtn.classList.remove('active');
      editBtn.innerHTML = '<i class="bi bi-pencil"></i> Edit';
    }
  } else {
    // Enter edit mode
    editingSections.add(sectionId);
    inputs.forEach(input => {
      // Don't enable email (verified) from editing here
      if (input.id !== 'email') input.disabled = false;
    });
    if (editBtn) {
      editBtn.classList.add('active');
      editBtn.innerHTML = '<i class="bi bi-x-lg"></i> Cancel';
    }
  }
  
  // Show/hide global save button
  if (saveBtn) {
    saveBtn.style.display = editingSections.size > 0 ? 'flex' : 'none';
  }
}

// ── Save all edited sections ──
function saveProfile() {
  const saveBtn = document.getElementById('saveBtn');
  
  // Collect values (in a real app, POST to API here)
  const data = {};
  editingSections.forEach(sectionId => {
    const section = document.getElementById(sectionId);
    section?.querySelectorAll('.prof-input').forEach(input => {
      if (input.id) data[input.id] = input.value;
    });
  });
  
  console.log('Saving profile data:', data);
  
  // Disable all edited inputs
  editingSections.forEach(sectionId => {
    const section = document.getElementById(sectionId);
    section?.querySelectorAll('.prof-input').forEach(i => { i.disabled = true; });
    const editBtn = section?.querySelector('.prof-edit-btn');
    if (editBtn) {
      editBtn.classList.remove('active');
      editBtn.innerHTML = '<i class="bi bi-pencil"></i> Edit';
    }
  });
  
  editingSections.clear();
  
  // Update avatar initials from name
  updateAvatarInitials();
  
  // Hide save button
  if (saveBtn) saveBtn.style.display = 'none';
  
  // Show success toast
  showToast('Profile updated successfully!', 'success');
}

// ── Update avatar circle initials live ──
function updateAvatarInitials() {
  const first = document.getElementById('firstName')?.value?.trim() || '';
  const last = document.getElementById('lastName')?.value?.trim() || '';
  const initials = (first[0] || '') + (last[0] || '');
  const circle = document.getElementById('avatarCircle');
  const nameEl = document.getElementById('avatarName');
  
  if (circle && initials) circle.textContent = initials.toUpperCase();
  if (nameEl && first && last) nameEl.textContent = `${first} ${last}`;
}

// ── Show/hide change password form ──
function showChangePassword() {
  const form = document.getElementById('changePwForm');
  if (form) form.style.display = 'flex';
}

function hideChangePassword() {
  const form = document.getElementById('changePwForm');
  if (form) {
    form.style.display = 'none';
    ['currentPw', 'newPw', 'confirmPw'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
  }
}

function updatePassword() {
  const current = document.getElementById('currentPw')?.value;
  const newPw = document.getElementById('newPw')?.value;
  const confirm = document.getElementById('confirmPw')?.value;
  
  if (!current || !newPw || !confirm) {
    showToast('Please fill in all password fields.', 'error');
    return;
  }
  
  if (newPw.length < 8) {
    showToast('New password must be at least 8 characters.', 'error');
    return;
  }
  
  if (newPw !== confirm) {
    showToast('New passwords do not match.', 'error');
    return;
  }
  
  // TODO: call API to update password
  hideChangePassword();
  showToast('Password updated successfully!', 'success');
}

// ── Simple toast notification ──
function showToast(message, type = 'success') {
  // Remove existing toast
  document.querySelector('.prof-toast')?.remove();
  
  const toast = document.createElement('div');
  toast.className = 'prof-toast';
  
  const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
  const color = type === 'success' ? '#1a9e5f' : '#dc2626';
  const bg = type === 'success' ? '#e6f7ef' : '#fee2e2';
  const border = type === 'success' ? '#1a9e5f' : '#dc2626';
  
  toast.style.cssText = `
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    background: ${bg};
    border: 1.5px solid ${border};
    border-radius: 10px;
    padding: 0.75rem 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.82rem;
    font-weight: 600;
    color: ${color};
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    z-index: 9999;
    animation: slideInToast 0.25s ease both;
  `;
  
  toast.innerHTML = `<i class="bi ${icon}"></i> ${message}`;
  document.body.appendChild(toast);
  
  // Inject keyframe if not already
  if (!document.getElementById('toastStyle')) {
    const style = document.createElement('style');
    style.id = 'toastStyle';
    style.textContent = `
      @keyframes slideInToast {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
      }
    `;
    document.head.appendChild(style);
  }
  
  setTimeout(() => toast.remove(), 3500);
}