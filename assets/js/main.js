// Auto-detect environment: localhost XAMPP (/Gadgetzone or /gadget) vs VPS root (/)
const _BASE = (function () {
  const m = window.location.pathname.match(/^(\/[^\/]*gadget[^\/]*)/i);
  return m ? m[1] : '';
})();

document.addEventListener('DOMContentLoaded', function () {
  initThemeToggle();
  initMobileMenu();
  initAddToCart();
  initBuyNow();
  initCartRemove();
  initCartQty();
  initFadeInOnScroll();
  initDealTimer();
});

// ---------------------------------------------------------------
// Password Show / Hide Toggle
// ---------------------------------------------------------------
const EYE_OPEN = '<svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
const EYE_CLOSED = '<svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.innerHTML = show ? EYE_CLOSED : EYE_OPEN;
  btn.classList.toggle('active', show);
  btn.title = show ? 'Hide Password' : 'Show Password';
}


// ---------------------------------------------------------------
// Theme Switcher (Light / Dark)
// ---------------------------------------------------------------
function initThemeToggle() {
  const btn = document.getElementById('themeToggleBtn');
  if (!btn) return;
  btn.addEventListener('click', function () {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('gz_theme', next);
  });
}

// ---------------------------------------------------------------
// Toast Notification
// ---------------------------------------------------------------
function showToast(message, actionUrl, actionText) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'toast-msg';
  toast.innerHTML = `
    <span class="toast-icon">✓</span>
    <div class="toast-body">${message}</div>
    ${actionUrl ? `<a href="${actionUrl}" class="toast-action">${actionText || 'View Cart'}</a>` : ''}
  `;
  container.appendChild(toast);

  // Trigger animation
  requestAnimationFrame(() => {
    toast.classList.add('show');
  });

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}

// ---------------------------------------------------------------
// Buy Now (1-Click Direct Checkout)
// ---------------------------------------------------------------
function initBuyNow() {
  const btn = document.getElementById('buyNowBtn');
  if (!btn) return;

  btn.addEventListener('click', async function (e) {
    e.preventDefault();
    const productId = this.dataset.id;
    const qtyInput = document.getElementById('qtyInput');
    const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

    const orig = this.innerHTML;
    this.innerHTML = '⚡ Processing...';
    this.disabled = true;

    try {
      const res = await fetch(_BASE + '/pages/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=buy_now&product_id=${encodeURIComponent(productId)}&qty=${encodeURIComponent(qty)}`
      });
      const data = await res.json();
      if (data.success && data.redirect) {
        window.location.href = data.redirect;
      } else {
        this.innerHTML = orig;
        this.disabled = false;
        alert(data.message || 'Could not process instant checkout.');
      }
    } catch (err) {
      console.error('Buy Now failed', err);
      window.location.href = _BASE + '/pages/checkout.php';
    }
  });
}

// ---------------------------------------------------------------
// Mobile nav toggle
// ---------------------------------------------------------------
function initMobileMenu() {
  const toggle = document.getElementById('mobileToggle');
  const nav = document.querySelector('.main-nav');
  if (!toggle || !nav) return;
  toggle.addEventListener('click', () => nav.classList.toggle('open'));
}

// ---------------------------------------------------------------
// Cart badge update helper
// ---------------------------------------------------------------
function updateCartBadge(count) {
  const badge = document.querySelector('.cart-badge');
  if (!badge) return;
  badge.textContent = count;
  badge.style.display = count > 0 ? '' : 'none';
}

// ---------------------------------------------------------------
// Add to cart (product cards / detail page)
// ---------------------------------------------------------------
function initAddToCart() {
  document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', async function (e) {
      e.preventDefault();
      const productId = this.dataset.id;
      const qtyInput = document.getElementById('qtyInput_' + productId) || document.getElementById('qtyInput');
      const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

      const originalText = this.textContent;
      this.textContent = 'Adding...';
      this.disabled = true;

      try {
        const res = await fetch(_BASE + '/pages/cart_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=add&product_id=${encodeURIComponent(productId)}&qty=${encodeURIComponent(qty)}`
        });
        const data = await res.json();
        if (data.success) {
          updateCartBadge(data.cart_count);
          this.textContent = 'Added ✓';
          
          // Trigger floating toast notification
          showToast(
            `Added <strong>${data.product_name || 'item'}</strong> (${qty}x) to your cart!`,
            _BASE + '/pages/cart.php',
            'View Cart →'
          );

          setTimeout(() => { this.textContent = originalText; this.disabled = false; }, 1200);
        } else {
          this.textContent = originalText;
          this.disabled = false;
          alert(data.message || 'Could not add item to cart.');
        }
      } catch (err) {
        this.textContent = originalText;
        this.disabled = false;
        console.error('Add to cart failed', err);
      }
    });
  });
}

// ---------------------------------------------------------------
// Cart remove — intercepts the server-side <form> fallback with AJAX.
// ---------------------------------------------------------------
function initCartRemove() {
  document.querySelectorAll('.remove-form').forEach(form => {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const productId = this.dataset.id;
      const row = this.closest('tr');

      try {
        const res = await fetch(_BASE + '/pages/cart_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=remove&product_id=${encodeURIComponent(productId)}`
        });
        const data = await res.json();
        if (data.success) {
          updateCartBadge(data.cart_count);
          if (row) {
            row.style.transition = 'opacity .3s';
            row.style.opacity = '0';
            setTimeout(() => {
              row.remove();
              refreshOrderSummary(data);
              if (data.cart_count === 0) window.location.reload();
            }, 300);
          }
        } else {
          this.submit();
        }
      } catch (err) {
        console.error('AJAX remove failed, falling back to server-side form submit', err);
        this.submit();
      }
    });
  });
}

// ---------------------------------------------------------------
// Cart quantity +/- and direct input change
// ---------------------------------------------------------------
function initCartQty() {
  document.querySelectorAll('.qty-controls').forEach(control => {
    const productId = control.dataset.id;
    if (!productId) return;
    const input = control.querySelector('input[type="number"], input.qty-input');
    const minusBtn = control.querySelector('.qty-minus');
    const plusBtn = control.querySelector('.qty-plus');

    const sendUpdate = async (qty) => {
      qty = Math.max(1, Math.min(99, qty));
      if (input) input.value = qty;
      try {
        const res = await fetch(_BASE + '/pages/cart_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=update&product_id=${encodeURIComponent(productId)}&qty=${encodeURIComponent(qty)}`
        });
        const data = await res.json();
        if (data.success) {
          updateCartBadge(data.cart_count);
          const subtotalEl = control.closest('tr')?.querySelector('.line-subtotal');
          if (subtotalEl && data.line_subtotal_formatted) subtotalEl.textContent = data.line_subtotal_formatted;
          refreshOrderSummary(data);
        }
      } catch (err) {
        console.error('Qty update failed', err);
      }
    };

    if (minusBtn) minusBtn.addEventListener('click', () => sendUpdate((parseInt(input.value, 10) || 1) - 1));
    if (plusBtn) plusBtn.addEventListener('click', () => sendUpdate((parseInt(input.value, 10) || 1) + 1));
    if (input) input.addEventListener('change', () => sendUpdate(parseInt(input.value, 10) || 1));
  });
}

function refreshOrderSummary(data) {
  if (data.formatted_total) {
    const totalEls = document.querySelectorAll('.js-cart-total');
    totalEls.forEach(el => el.textContent = data.formatted_total);
  }
  if (data.formatted_subtotal) {
    const subEls = document.querySelectorAll('.js-cart-subtotal');
    subEls.forEach(el => el.textContent = data.formatted_subtotal);
  }

  // Update Free Shipping Progress Meter
  const meter = document.getElementById('freeShippingMeter');
  const fill = document.getElementById('meterFill');
  const text = document.getElementById('meterText');
  if (meter && fill && typeof data.cart_total === 'number') {
    const threshold = parseFloat(meter.dataset.threshold) || 5000;
    const progress = Math.min(100, Math.round((data.cart_total / threshold) * 100));
    fill.style.width = progress + '%';
    
    if (data.cart_total >= threshold) {
      if (text) text.innerHTML = '🎉 <strong style="color:#4ade80;">You\'ve unlocked FREE Express Delivery!</strong>';
      const shipEl = document.querySelector('.js-shipping-val');
      if (shipEl) shipEl.innerHTML = '<strong style="color:#4ade80;">FREE</strong>';
    }
  }
}

// ---------------------------------------------------------------
// Fade-in on scroll for product cards
// ---------------------------------------------------------------
function initFadeInOnScroll() {
  const items = document.querySelectorAll('.product-card, .category-card');
  if (!items.length || !('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  items.forEach(item => {
    item.style.opacity = '0';
    item.style.transform = 'translateY(16px)';
    item.style.transition = 'opacity .5s ease, transform .5s ease';
    observer.observe(item);
  });
}

// ---------------------------------------------------------------
// Deal of the day countdown
// ---------------------------------------------------------------
function initDealTimer() {
  const timer = document.getElementById('dealTimer');
  if (!timer) return;
  // Counts down to midnight
  function update() {
    const now = new Date();
    const end = new Date();
    end.setHours(24, 0, 0, 0);
    const diff = Math.max(0, end - now);
    const h = String(Math.floor(diff / 3600000)).padStart(2, '0');
    const m = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
    const s = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
    const hEl = timer.querySelector('.hh');
    const mEl = timer.querySelector('.mm');
    const sEl = timer.querySelector('.ss');
    if (hEl) hEl.textContent = h;
    if (mEl) mEl.textContent = m;
    if (sEl) sEl.textContent = s;
  }
  update();
  setInterval(update, 1000);
}
