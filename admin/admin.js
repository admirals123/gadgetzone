document.addEventListener('DOMContentLoaded', function () {
  // ---- Admin Theme Switcher ----
  const themeBtn = document.getElementById('adminThemeToggleBtn');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('gz_theme', next);
    });
  }

  // ---- Live Clock on Dashboard ----
  const clockEl = document.getElementById('adminClock');
  if (clockEl) {
    function updateClock() {
      const now = new Date();
      clockEl.textContent = '🕒 ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' (' + now.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ')';
    }
    updateClock();
    setInterval(updateClock, 1000);
  }

  // ---- Modal open/close ----
  document.querySelectorAll('[data-open-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.openModal);
      if (modal) modal.classList.add('open');
    });
  });
  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.modal-overlay');
      if (modal) modal.classList.remove('open');
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  // ---- Slug generator for Products & Categories ----
  const nameInput = document.getElementById('productNameInput');
  const slugPreview = document.getElementById('productSlugPreview');
  const catNameInput = document.getElementById('catNameInput');
  const catSlugInput = document.getElementById('catSlugInput');

  function slugify(str) {
    return (str || '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  if (nameInput && slugPreview) {
    nameInput.addEventListener('input', () => {
      slugPreview.value = slugify(nameInput.value);
    });
  }

  if (catNameInput && catSlugInput) {
    catNameInput.addEventListener('input', () => {
      catSlugInput.value = slugify(catNameInput.value);
    });
  }

  // ---- Edit Category Modal Population ----
  document.querySelectorAll('[data-edit-category]').forEach(btn => {
    btn.addEventListener('click', () => {
      const data = JSON.parse(btn.dataset.editCategory);
      const form = document.getElementById('categoryForm');
      if (!form) return;

      document.getElementById('catIdInput').value = data.id || '';
      document.getElementById('catNameInput').value = data.name || '';
      document.getElementById('catSlugInput').value = data.slug || '';
      document.getElementById('catIconInput').value = data.icon || '📦';

      const titleEl = document.getElementById('categoryModalTitle');
      if (titleEl) titleEl.textContent = 'Edit Category: ' + data.name;
      document.getElementById('categoryModal').classList.add('open');
    });
  });

  document.querySelectorAll('[data-new-category]').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = document.getElementById('categoryForm');
      if (!form) return;
      form.reset();
      document.getElementById('catIdInput').value = '';
      document.getElementById('catIconInput').value = '📦';
      const titleEl = document.getElementById('categoryModalTitle');
      if (titleEl) titleEl.textContent = 'Add New Category';
    });
  });

  // ---- Live Discount Calculator ----
  const priceInput = document.getElementById('prodPriceInput');
  const oldPriceInput = document.getElementById('prodOldPriceInput');
  const discountTag = document.getElementById('discountPreviewTag');

  function calculateDiscount() {
    if (!priceInput || !oldPriceInput || !discountTag) return;
    const price = parseFloat(priceInput.value) || 0;
    const oldPrice = parseFloat(oldPriceInput.value) || 0;
    if (oldPrice > price && price > 0) {
      const pct = Math.round(((oldPrice - price) / oldPrice) * 100);
      discountTag.textContent = `🎉 Savings: ${pct}% OFF`;
      discountTag.style.display = 'block';
    } else {
      discountTag.style.display = 'none';
    }
  }

  if (priceInput) priceInput.addEventListener('input', calculateDiscount);
  if (oldPriceInput) oldPriceInput.addEventListener('input', calculateDiscount);

  // ---- Edit product modal population ----
  document.querySelectorAll('[data-edit-product]').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = document.getElementById('productForm');
      if (!form) return;
      const data = JSON.parse(btn.dataset.editProduct);

      Object.keys(data).forEach(key => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) {
          if (field.type === 'checkbox') {
            field.checked = parseInt(data[key], 10) === 1;
          } else {
            field.value = data[key] ?? '';
          }
        }
      });

      form.querySelector('[name="id"]').value = data.id;
      if (slugPreview) slugPreview.value = data.slug || slugify(data.name);

      // Trigger image preview if image_url is present
      const imgPreview = document.getElementById('productImagePreview');
      if (imgPreview && data.image_url) {
        imgPreview.src = data.image_url;
        imgPreview.style.display = 'block';
      }

      calculateDiscount();

      const titleEl = document.getElementById('productModalTitle');
      if (titleEl) titleEl.textContent = 'Edit Product: ' + data.name;
      document.getElementById('productModal').classList.add('open');
    });
  });

  // ---- New product reset ----
  document.querySelectorAll('[data-new-product]').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = document.getElementById('productForm');
      if (!form) return;
      form.reset();
      form.querySelector('[name="id"]').value = '';
      if (slugPreview) slugPreview.value = '';
      const imgPreview = document.getElementById('productImagePreview');
      if (imgPreview) {
        imgPreview.src = '';
        imgPreview.style.display = 'none';
      }
      if (discountTag) discountTag.style.display = 'none';
      const titleEl = document.getElementById('productModalTitle');
      if (titleEl) titleEl.textContent = 'Add New Product';
    });
  });

  // ---- Live Image preview on URL input & File upload ----
  const imgUrlInput = document.getElementById('prodImageUrlInput');
  const imgInput = document.getElementById('productImageInput');
  const imgPreview = document.getElementById('productImagePreview');

  if (imgUrlInput && imgPreview) {
    imgUrlInput.addEventListener('input', () => {
      const url = imgUrlInput.value.trim();
      if (url.startsWith('http')) {
        imgPreview.src = url;
        imgPreview.style.display = 'block';
      }
    });
  }

  if (imgInput && imgPreview) {
    imgInput.addEventListener('change', () => {
      const file = imgInput.files[0];
      if (file) {
        imgPreview.src = URL.createObjectURL(file);
        imgPreview.style.display = 'block';
      }
    });
  }

  // ---- Currency settings picker ----
  document.querySelectorAll('.currency-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.currency-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      const hidden = document.getElementById('activeCurrencyInput');
      if (hidden) hidden.value = opt.dataset.code;
    });
  });
});
