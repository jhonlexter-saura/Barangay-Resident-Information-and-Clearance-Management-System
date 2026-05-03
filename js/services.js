// ============================================
// services.js
// Shared logic for all 8 service pages + payment page
// Cart: stored in localStorage key 'lgu_cart'
// ============================================

// ════════════════════════════════════════════
// CART MANAGEMENT
// ════════════════════════════════════════════

const CART_KEY = 'lgu_cart';

// Stored profile for display in cart
let _profile = {
  full_name:   'Loading...',
  resident_id: '—',
  first_name:  '',
  initials:    '??',
  dob:         '',
  address:     '',
};

function getCart() {
  try {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
  } catch { return []; }
}

function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function addToCart(name, fee, icon, iconBg, iconColor) {
  if (!validateForm()) return;

  const cart = getCart();

  if (cart.find(item => item.name === name)) {
    showServiceToast(`"${name}" is already in your cart.`, 'info');
    return;
  }

  const feeNum = parseFee(fee);

  const item = {
    id:         Date.now(),
    name,
    fee:        feeNum,
    feeLabel:   feeNum === 0 ? 'Free' : (feeNum < 0 ? 'Computed' : `₱${feeNum.toFixed(2)}`),
    icon,
    iconBg,
    iconColor,
    addedAt:    new Date().toISOString(),
    fields:     collectFormData(),
    // files are handled separately at submit time via _uploadedFiles
  };

  cart.push(item);
  saveCart(cart);
  updateCartUI();

  const btn = document.getElementById('addCartBtn');
  if (btn) {
    btn.classList.add('added');
    btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Added to Cart!';
  }

  showServiceToast(`"${name}" added to your cart.`, 'success');
}

function parseFee(feeStr) {
  if (!feeStr || feeStr === 'Free') return 0;
  if (feeStr === 'Varies' || feeStr === 'Computed') return -1;
  const n = parseFloat(feeStr.replace(/[₱,+]/g, ''));
  return isNaN(n) ? -1 : n;
}

function collectFormData() {
  const data = {};
  document.querySelectorAll('.svc-input, .svc-select, .svc-textarea').forEach(el => {
    if (el.id && !el.disabled) data[el.id] = el.value;
  });
  return data;
}

function updateCartUI() {
  const cart  = getCart();
  const count = cart.length;

  const floatBtn   = document.getElementById('cartFloat');
  const floatCount = document.getElementById('cartFloatCount');
  const floatLabel = document.getElementById('cartFloatLabel');

  if (floatBtn) {
    floatBtn.style.display = count > 0 ? 'flex' : 'none';
    if (floatCount) floatCount.textContent = count;
    if (floatLabel) floatLabel.textContent = count === 1 ? 'View Cart (1)' : `View Cart (${count})`;
  }

  const cartDot = document.getElementById('cartDot');
  if (cartDot) cartDot.style.display = count > 0 ? 'block' : 'none';

  const navBadge = document.getElementById('cartNavBadge');
  if (navBadge) {
    navBadge.textContent   = count;
    navBadge.style.display = count > 0 ? 'inline-flex' : 'none';
  }
}

// ════════════════════════════════════════════
// PROFILE AUTO-FILL
// ════════════════════════════════════════════

async function loadProfile() {
  try {
    // Path works from inside services/ folder
    const res  = await fetch('service-handler.php?action=get_profile');
    const data = await res.json();
    if (!data.success) return;

    // Store globally for use in cart display
    _profile = {
      full_name:   data.full_name,
      resident_id: 'RES-' + String(data.resident_id).padStart(5, '0'),
      first_name:  data.first_name,
      initials:    data.initials,
      dob:         data.dob,
      address:     data.address,
    };

    // Auto-fill disabled inputs in service forms
    document.querySelectorAll('.svc-input[disabled]').forEach(input => {
      const label = input.previousElementSibling?.textContent?.trim()
                 || input.closest('.svc-field')
                         ?.querySelector('.svc-label')
                         ?.textContent?.trim();

      if (label === 'Full Name')     input.value = _profile.full_name;
      if (label === 'Resident ID')   input.value = _profile.resident_id;
      if (label === 'Date of Birth') input.value = _profile.dob;
      if (label === 'Address')       input.value = _profile.address;
    });

    // Update topbar chip
    const chip   = document.querySelector('.r-chip-name');
    const avatar = document.querySelector('.r-chip-avatar');
    if (chip)   chip.textContent   = _profile.first_name;
    if (avatar) avatar.textContent = _profile.initials;

  } catch (err) {
    console.error('Profile load error:', err);
  }
}

// ════════════════════════════════════════════
// FORM VALIDATION
// ════════════════════════════════════════════

function validateForm() {
  let valid = true;
  const required = document.querySelectorAll('.svc-input:not([disabled]), .svc-select:not([disabled])');

  required.forEach(el => {
    const errEl = document.getElementById(`${el.id}-err`);
    if (!errEl) return;

    if (el.tagName === 'SELECT' && !el.value) {
      el.classList.add('error');
      errEl.classList.add('show');
      valid = false;
    } else if (el.tagName === 'INPUT' && el.type !== 'file' && !el.value.trim()) {
      el.classList.add('error');
      errEl.classList.add('show');
      valid = false;
    } else {
      el.classList.remove('error');
      errEl.classList.remove('show');
    }
  });

  if (!valid) {
    showServiceToast('Please fill in all required fields.', 'error');
    document.querySelector('.svc-input.error, .svc-select.error')
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  return valid;
}

document.addEventListener('input', e => {
  if (e.target.classList.contains('svc-input') || e.target.classList.contains('svc-select')) {
    e.target.classList.remove('error');
    const errEl = document.getElementById(`${e.target.id}-err`);
    if (errEl) errEl.classList.remove('show');
  }
});

// ════════════════════════════════════════════
// FILE UPLOAD
// Track files per page in module-level variable
// ════════════════════════════════════════════

let _uploadedFiles = [];

function initFileUpload() {
  const zone    = document.getElementById('fileDropZone');
  const input   = document.getElementById('fileInput');
  const preview = document.getElementById('uploadPreview');
  if (!zone || !input || !preview) return;

  _uploadedFiles = [];

  function renderPreview() {
    preview.innerHTML = '';
    _uploadedFiles.forEach((f, i) => {
      const chip = document.createElement('div');
      chip.className = 'upload-file-chip';
      chip.innerHTML = `
        <i class="bi bi-file-earmark"></i> ${f.name}
        <button onclick="removeFile(${i})" type="button">&times;</button>
      `;
      preview.appendChild(chip);
    });
  }

  input.addEventListener('change', () => {
    _uploadedFiles = [..._uploadedFiles, ...Array.from(input.files)];
    renderPreview();
  });

  zone.addEventListener('click', () => input.click());

  zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('drag-over');
  });

  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));

  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    _uploadedFiles = [..._uploadedFiles, ...Array.from(e.dataTransfer.files)];
    renderPreview();
  });

  window.removeFile = (idx) => {
    _uploadedFiles.splice(idx, 1);
    renderPreview();
  };
}

// ════════════════════════════════════════════
// APPOINTMENT CALENDAR & SLOTS
// ════════════════════════════════════════════

function initAppointmentCalendar() {
  const strip       = document.getElementById('calStrip');
  const slotSection = document.getElementById('slotSection');
  const slotGrid    = document.getElementById('slotGrid');
  if (!strip) return;

  const today     = new Date();
  const days      = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  const takenDays = [0, 6];

  for (let i = 1; i <= 14; i++) {
    const d   = new Date(today);
    d.setDate(today.getDate() + i);
    const dow = d.getDay();
    const el  = document.createElement('div');
    el.className = 'cal-day' + (takenDays.includes(dow) ? ' disabled' : '');
    el.innerHTML = `
      <div class="cal-day-name">${days[dow]}</div>
      <div class="cal-day-num">${d.getDate()}</div>
    `;
    el.dataset.date = d.toISOString().split('T')[0];

    if (!takenDays.includes(dow)) {
      el.addEventListener('click', () => {
        document.querySelectorAll('.cal-day').forEach(x => x.classList.remove('selected'));
        el.classList.add('selected');
        renderSlots(slotSection, slotGrid);
      });
    }

    strip.appendChild(el);
  }
}

const ALL_SLOTS   = ['8:00 AM','9:00 AM','10:00 AM','11:00 AM','1:00 PM','2:00 PM','3:00 PM','4:00 PM'];
const TAKEN_SLOTS = ['9:00 AM','2:00 PM'];

function renderSlots(section, grid) {
  section.style.display = 'block';
  grid.innerHTML = '';

  ALL_SLOTS.forEach(t => {
    const btn   = document.createElement('button');
    btn.type    = 'button';
    const taken = TAKEN_SLOTS.includes(t);
    btn.className  = 'slot-btn' + (taken ? ' taken' : '');
    btn.textContent = t;
    btn.disabled   = taken;

    if (!taken) {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.slot-btn:not(.taken)').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
      });
    }

    grid.appendChild(btn);
  });
}

// ════════════════════════════════════════════
// REAL PROPERTY TAX COMPUTATION
// ════════════════════════════════════════════

function initRPTComputation() {
  const assessed  = document.getElementById('assessed_val');
  const propClass = document.getElementById('prop_class');
  if (!assessed || !propClass) return;

  function compute() {
    const av  = parseFloat(assessed.value) || 0;
    const cls = propClass.value;
    if (!av || !cls) {
      document.getElementById('taxComputeSection').style.display = 'none';
      return;
    }

    const rates    = { residential: 0.015, agricultural: 0.01, commercial: 0.02, industrial: 0.02, special: 0.01 };
    const rate     = rates[cls] || 0.015;
    const basicTax = av * rate;
    const sef      = basicTax * 0.01;
    const total    = basicTax + sef;

    const tbody     = document.getElementById('taxTableBody');
    const totalCell = document.getElementById('taxTotalCell');
    if (!tbody || !totalCell) return;

    tbody.innerHTML = `
      <tr><td>Assessed Value</td><td>—</td><td>₱${av.toLocaleString('en-PH', {minimumFractionDigits:2})}</td></tr>
      <tr><td>Basic RPT (${(rate*100).toFixed(1)}%)</td><td>${(rate*100).toFixed(1)}%</td><td>₱${basicTax.toLocaleString('en-PH', {minimumFractionDigits:2})}</td></tr>
      <tr><td>Special Education Fund</td><td>1%</td><td>₱${sef.toLocaleString('en-PH', {minimumFractionDigits:2})}</td></tr>
    `;

    totalCell.textContent = `₱${total.toLocaleString('en-PH', {minimumFractionDigits:2})}`;

    const feeDisplay = document.getElementById('displayFee');
    if (feeDisplay) feeDisplay.textContent = `₱${total.toLocaleString('en-PH', {minimumFractionDigits:2})}`;
  }

  assessed.addEventListener('input', compute);
  propClass.addEventListener('change', compute);
}

// ════════════════════════════════════════════
// TOAST NOTIFICATION
// ════════════════════════════════════════════

function showServiceToast(message, type = 'success') {
  document.querySelector('.svc-toast')?.remove();

  const colors = {
    success: { bg:'#e6f7ef', border:'#1a9e5f', color:'#166534', icon:'bi-check-circle-fill' },
    error:   { bg:'#fee2e2', border:'#dc2626', color:'#991b1b', icon:'bi-x-circle-fill'     },
    info:    { bg:'#e8f3fc', border:'#1a7fd4', color:'#1a7fd4', icon:'bi-info-circle-fill'  },
  };

  const c     = colors[type] || colors.info;
  const toast = document.createElement('div');
  toast.className = 'svc-toast';
  toast.style.cssText = `
    position:fixed; bottom:1.5rem; right:1.5rem;
    background:${c.bg}; border:1.5px solid ${c.border}; color:${c.color};
    border-radius:10px; padding:0.72rem 1.1rem;
    font-family:'Plus Jakarta Sans',sans-serif; font-size:0.82rem; font-weight:600;
    display:flex; align-items:center; gap:8px;
    box-shadow:0 4px 16px rgba(0,0,0,0.1); z-index:9999;
    animation:slideInToast 0.25s ease both;
  `;
  toast.innerHTML = `<i class="bi ${c.icon}"></i> ${message}`;
  document.body.appendChild(toast);

  if (!document.getElementById('toastStyle')) {
    const s = document.createElement('style');
    s.id = 'toastStyle';
    s.textContent = `@keyframes slideInToast{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}`;
    document.head.appendChild(s);
  }

  setTimeout(() => toast.remove(), 3500);
}

// ════════════════════════════════════════════
// PAYMENT PAGE
// ════════════════════════════════════════════

function initPaymentPage() {
  renderPaymentCart();
  initPaymentMethods();
}

function renderPaymentCart() {
  const cart      = getCart();
  const container = document.getElementById('payItemsContainer');
  const emptyEl   = document.getElementById('payEmpty');
  const submitBtn = document.getElementById('submitRequestBtn');
  if (!container) return;

  container.innerHTML = '';

  if (cart.length === 0) {
    if (emptyEl)   emptyEl.style.display   = 'flex';
    if (submitBtn) submitBtn.disabled      = true;
    updatePaymentSummary([], 0, false);
    return;
  }

  if (emptyEl)   emptyEl.style.display   = 'none';
  if (submitBtn) submitBtn.disabled      = false;

  let total     = 0;
  let hasVaried = false;

  cart.forEach(item => {
    if (item.fee > 0) total += item.fee;
    if (item.fee < 0) hasVaried = true;

    const div = document.createElement('div');
    div.className = 'pay-item';
    div.id = `pay-item-${item.id}`;

    // ── Use real profile data instead of hardcoded values ──
    div.innerHTML = `
      <div class="pay-item-icon" style="background:${item.iconBg}; color:${item.iconColor};">
        <i class="bi ${item.icon}"></i>
      </div>
      <div class="pay-item-info">
        <div class="pay-item-name">${item.name}</div>
        <div class="pay-item-sub">Added ${new Date(item.addedAt).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'})}</div>
        <div class="pay-item-tags">
          <span class="pay-tag"><i class="bi bi-person"></i> ${_profile.full_name}</span>
          <span class="pay-tag"><i class="bi bi-card-text"></i> ${_profile.resident_id}</span>
        </div>
      </div>
      <div class="pay-item-right">
        <div class="pay-item-fee">${item.feeLabel}</div>
        <button class="pay-remove-btn" onclick="removeFromCart(${item.id})">
          <i class="bi bi-trash"></i> Remove
        </button>
      </div>
    `;
    container.appendChild(div);
  });

  updatePaymentSummary(cart, total, hasVaried);
  updateCartUI();
}

function updatePaymentSummary(cart, total, hasVaried) {
  const linesEl   = document.getElementById('summaryLines');
  const totalEl   = document.getElementById('summaryTotal');
  const heroCount = document.getElementById('summaryHeroCount');

  if (heroCount) heroCount.textContent = `${cart.length} service${cart.length !== 1 ? 's' : ''} in your cart`;

  if (linesEl) {
    linesEl.innerHTML = cart.map(item => `
      <div class="pay-summary-line">
        <span class="pay-summary-line-key">${item.name}</span>
        <span class="pay-summary-line-val">${item.feeLabel}</span>
      </div>
    `).join('');
  }

  if (totalEl) {
    if (hasVaried) {
      totalEl.innerHTML = `
        <span style="font-size:0.85rem;">₱${total.toFixed(2)}</span>
        <span style="font-size:0.7rem; color:var(--text-muted);">+ computed</span>
      `;
    } else {
      totalEl.textContent = total === 0 ? 'Free' : `₱${total.toFixed(2)}`;
    }
  }
}

function removeFromCart(id) {
  let cart = getCart();
  cart = cart.filter(item => item.id !== id);
  saveCart(cart);
  renderPaymentCart();
  showServiceToast('Item removed from cart.', 'info');
}

function initPaymentMethods() {
  document.querySelectorAll('.pay-method-opt').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.pay-method-opt').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      opt.querySelector('input[type="radio"]').checked = true;
    });
  });
}

// ════════════════════════════════════════════
// SUBMIT REQUEST TO LGU
// ════════════════════════════════════════════

async function submitRequest() {
  const btn  = document.getElementById('submitRequestBtn');
  const cart = getCart();

  if (cart.length === 0) {
    showServiceToast('Your cart is empty.', 'error');
    return;
  }

  const method = document.querySelector('input[name="payMethod"]:checked')?.value || 'counter';

  if (btn) btn.disabled = true;

  try {
    // ── Build FormData ───────────────────────────────────────────────────────
    const formData = new FormData();
    formData.append('action', 'submit_requests');

    // Send cart as JSON string — handler decodes it
    formData.append('cart_json',      JSON.stringify(cart));
    formData.append('payment_method', method);

    // ── Attach files per cart item index ─────────────────────────────────────
    // Files are stored in _uploadedFiles only for the current page session.
    // For the payment page (which has no form), we attach whatever was stored.
    // Note: files added on individual service pages are not persisted across
    // page navigations — this is a known limitation of localStorage-based carts.
    // A future improvement would store files server-side when adding to cart.
    _uploadedFiles.forEach(file => {
      formData.append('files_0[]', file);
    });

    const res  = await fetch('service-handler.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      // Clear cart
      saveCart([]);
      updateCartUI();

      // Show success overlay
      const overlay  = document.getElementById('paySuccessOverlay');
      const refEl    = document.getElementById('successRef');
      const countEl  = document.getElementById('successCount');

      if (refEl)   refEl.textContent   = 'Reference No: ' + data.ref_display;
      if (countEl) countEl.textContent = `${cart.length} service request${cart.length !== 1 ? 's' : ''} submitted successfully.`;
      if (overlay) overlay.classList.add('show');

    } else {
      showServiceToast(data.message || 'Submission failed. Please try again.', 'error');
      if (btn) btn.disabled = false;
    }

  } catch (err) {
    console.error('Submit error:', err);
    showServiceToast('Network error. Please try again.', 'error');
    if (btn) btn.disabled = false;
  }
}

// ════════════════════════════════════════════
// INIT ON DOM READY
// ════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', async () => {
  await loadProfile();   // load profile first so cart display has real names
  updateCartUI();
  initFileUpload();
  initAppointmentCalendar();
  initRPTComputation();

  if (document.getElementById('payItemsContainer')) {
    initPaymentPage();
  }
});