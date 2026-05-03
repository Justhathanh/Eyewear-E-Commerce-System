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
  const type = p.stock > 0 ? 'available' : 'preorder';
  const tagClass = type === 'preorder' ? 'prod-tag pre' : 'prod-tag';
  const tagLabel = type === 'preorder' ? 'Pre-order' : 'Có sẵn';
  const price = parseInt(p.price).toLocaleString('vi-VN') + ' ₫';

  return `
    <div class="prod-card" data-type="${type}" data-id="${p.product_id}">
@@ -57,26 +58,26 @@ function renderProductCard(p) {
        </button>
      </div>
    </div>`;
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
    // re-apply fade observer từ main.js
    if (typeof initFadeObserver === 'function') initFadeObserver();
  } catch (e) {
    grid.innerHTML = '<p style="color:var(--muted)">Không thể tải sản phẩm.</p>';
  }
}

document.addEventListener('DOMContentLoaded', loadHomeProducts);