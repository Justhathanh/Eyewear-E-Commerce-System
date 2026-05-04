const API_BASE = 'http://localhost:9090/api';

// ── Fetch helper ─────────────────────────────────────────────
async function apiFetch(endpoint, options = {}) {
  const res = await fetch(`${API_BASE}${endpoint}`, {
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    ...options,
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// ── Products ─────────────────────────────────────────────────
async function fetchProducts(category = '') {
  const qs = category ? `?category=${encodeURIComponent(category)}` : '';
  return apiFetch(`/products${qs}`);
}

// ── Orders ───────────────────────────────────────────────────
async function fetchOrderHistory(params = {}) {
  const qs = new URLSearchParams(params).toString();
  return apiFetch(`/orders/history?${qs}`);
}

async function cancelOrder(orderId) {
  return apiFetch(`/orders/${orderId}/cancel`, { method: 'PUT' });
}

async function reorder(orderId) {
  return apiFetch(`/orders/${orderId}/reorder`, { method: 'POST' });
}

// ── Render product card ───────────────────────────────────────
function renderProductCard(p) {
  const type     = p.stock > 0 ? 'available' : 'preorder';
  const tagClass = type === 'preorder' ? 'prod-tag pre' : 'prod-tag';
  const tagLabel = type === 'preorder' ? 'Pre-order' : 'Có sẵn';
  const btnLabel = type === 'preorder' ? 'Đặt trước' : 'Thêm vào giỏ';
  const price    = Number(p.price).toLocaleString('vi-VN') + ' ₫';
  const safeName = (p.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');

  const imgHtml = p.image
    ? `<img src="${p.image}" alt="${safeName}" style="width:100%;height:220px;object-fit:cover;display:block">`
    : `<div style="display:flex;align-items:center;justify-content:center;height:220px;background:var(--surface)">
        <svg viewBox="0 0 160 80" width="140" height="70">
          <rect x="8" y="12" width="60" height="50" rx="12" fill="none" stroke="#1a1410" stroke-width="3"/>
          <rect x="92" y="12" width="60" height="50" rx="12" fill="none" stroke="#1a1410" stroke-width="3"/>
          <path d="M68 34 Q80 28 92 34" stroke="#1a1410" stroke-width="2.5" fill="none"/>
        </svg>
       </div>`;

  return `
    <div class="prod-card fade-item" data-type="${type}" data-id="${p.product_id}">
      <div class="prod-img" style="overflow:hidden;aspect-ratio:unset;height:220px">${imgHtml}</div>
      <div class="prod-info">
        <div class="prod-brand">${p.category || ''}</div>
        <div class="prod-name">${p.name || ''}</div>
        <div class="prod-bottom">
          <span class="prod-price">${price}</span>
          <span class="${tagClass}">${tagLabel}</span>
        </div>
        <button class="add-to-cart" onclick="addToCart(this, ${p.product_id}, '${safeName}', ${p.price})">${btnLabel}</button>
      </div>
    </div>
  `;
}

// ── Load products vào #prodGrid (home.php) ───────────────────
async function loadHomeProducts() {
  const grid = document.getElementById('prodGrid');
  if (!grid) return;

  try {
    const res = await fetchProducts();
    const products = res.data || [];
    if (!products.length) {
      grid.innerHTML = '<p style="color:var(--muted)">Chưa có sản phẩm.</p>';
      return;
    }
    grid.innerHTML = products.slice(0, 4).map(renderProductCard).join('');
    if (typeof initFadeObserver === 'function') initFadeObserver();
  } catch (e) {
    grid.innerHTML = '<p style="color:var(--muted)">Không thể tải sản phẩm.</p>';
  }
}

document.addEventListener('DOMContentLoaded', loadHomeProducts);