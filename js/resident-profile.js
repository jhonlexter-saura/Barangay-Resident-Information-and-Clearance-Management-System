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

  const inputs  = section.querySelectorAll('.prof-input');
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
      if (input.id !== 'email') input.disabled = false;
    });
    if (editBtn) {
      editBtn.classList.add('active');
      editBtn.innerHTML = '<i class="bi bi-x-lg"></i> Cancel';
    }
  }

  if (saveBtn) {
    saveBtn.style.display = editingSections.size > 0 ? 'flex' : 'none';
  }
}

// ── Save all edited sections ──
async function saveProfile() {
  const saveBtn = document.getElementById('saveBtn');
  if (saveBtn) saveBtn.disabled = true;

  // ── Separate address section from profile sections ──
  const profileSections = ['personal', 'contact'];
  const addressSection  = 'address';

  const hasProfile = [...editingSections].some(s => profileSections.includes(s));
  const hasAddress = editingSections.has(addressSection);

  try {

    // ── 1. Save personal/contact to `resident` table ──────────────────────
    if (hasProfile) {
      const profileData = new FormData();
      profileData.append('action', 'save_profile');

      profileSections.forEach(sectionId => {
        if (!editingSections.has(sectionId)) return;
        const section = document.getElementById(sectionId);
        section?.querySelectorAll('.prof-input').forEach(input => {
          if (input.name) profileData.append(input.name, input.value);
        });
      });

      const res  = await fetch('resident-profile.php', { method: 'POST', body: profileData });
      const data = await res.json();

      if (!data.success) {
        showToast(data.message, 'error');
        if (saveBtn) saveBtn.disabled = false;
        return;
      }
    }

    // ── 2. Save address to `household` table ──────────────────────────────
    if (hasAddress) {
      const addressData = new FormData();
      addressData.append('action', 'save_address');

      const section = document.getElementById(addressSection);
      section?.querySelectorAll('.prof-input').forEach(input => {
        if (input.name) addressData.append(input.name, input.value);
      });

      const res  = await fetch('resident-profile.php', { method: 'POST', body: addressData });
      const data = await res.json();

      if (!data.success) {
        showToast(data.message, 'error');
        if (saveBtn) saveBtn.disabled = false;
        return;
      }
    }

    // ── 3. All saved — lock inputs and reset UI ───────────────────────────
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
    updateAvatarInitials();
    if (saveBtn) saveBtn.style.display = 'none';
    showToast('Profile updated successfully!', 'success');

  } catch (err) {
    console.error(err);
    showToast('Network error. Please try again.', 'error');
  }

  if (saveBtn) saveBtn.disabled = false;
}

// ── Update avatar circle initials live ──
function updateAvatarInitials() {
  const first    = document.getElementById('firstName')?.value?.trim() || '';
  const last     = document.getElementById('lastName')?.value?.trim()  || '';
  const initials = (first[0] || '') + (last[0] || '');
  const circle   = document.getElementById('avatarCircle');
  const nameEl   = document.getElementById('avatarName');

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

// ── Update password via AJAX ──
async function updatePassword() {
  const current = document.getElementById('currentPw')?.value;
  const newPw   = document.getElementById('newPw')?.value;
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

  try {
    const formData = new FormData();
    formData.append('action',           'change_password');
    formData.append('current_password', current);
    formData.append('new_password',     newPw);
    formData.append('confirm_password', confirm);

    const res  = await fetch('resident-profile.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      hideChangePassword();
      showToast('Password updated successfully!', 'success');
    } else {
      showToast(data.message, 'error');
    }

  } catch (err) {
    console.error(err);
    showToast('Network error. Please try again.', 'error');
  }
}

// ── Simple toast notification ──
function showToast(message, type = 'success') {
  document.querySelector('.prof-toast')?.remove();

  const toast  = document.createElement('div');
  toast.className = 'prof-toast';

  const icon   = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
  const color  = type === 'success' ? '#1a9e5f' : '#dc2626';
  const bg     = type === 'success' ? '#e6f7ef'  : '#fee2e2';
  const border = type === 'success' ? '#1a9e5f' : '#dc2626';

  toast.style.cssText = `
    position: fixed; bottom: 1.5rem; right: 1.5rem;
    background: ${bg}; border: 1.5px solid ${border};
    border-radius: 10px; padding: 0.75rem 1.1rem;
    display: flex; align-items: center; gap: 8px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.82rem; font-weight: 600; color: ${color};
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    z-index: 9999; animation: slideInToast 0.25s ease both;
  `;

  toast.innerHTML = `<i class="bi ${icon}"></i> ${message}`;
  document.body.appendChild(toast);

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