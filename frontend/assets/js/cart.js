// frontend/assets/js/cart.js
// Giỏ hàng dùng API session PHP — không dùng localStorage

const CART_API = '/api/cart';

// ── Load & render giỏ hàng ───────────────────────────────────────────────────

async function loadCart() {
  const container = document.getElementById('cartContent');
  if (!container) return;

  container.innerHTML = `<div style="text-align:center;padding:3rem"><span class="spinner-inline"></span></div>`;

  try {
    const res  = await fetch(CART_API, { credentials: 'include' });

    if (res.status === 401) {
      // Đã login ở PHP nhưng session API chưa sync — không xảy ra trong flow chuẩn
      container.innerHTML = renderEmpty('Vui lòng đăng nhập lại.');
      return;
    }

    const data  = await res.json();
    const items = data.items || [];

    updateSummary(data.total || 0, items);

    if (!items.length) {
      container.innerHTML = renderEmpty();
      disableCheckout();
      return;
    }

    container.innerHTML = `
      <table class="cart-table">
        <thead>
          <tr>
            <th style="width:55%">Sản phẩm</th>
            <th>Đơn giá</th>
            <th>Số lượng</th>
            <th style="text-align:right">Thành tiền</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${items.map(cartRowHtml).join('')}
        </tbody>
      </table>`;

    enableCheckout();
  } catch {
    container.innerHTML = `<p style="color:#c0392b;padding:2rem 0">Không thể tải giỏ hàng.</p>`;
  }
}

function cartRowHtml(item) {
  const price = parseInt(item.price).toLocaleString('vi-VN') + ' ₫';
  const total = (item.price * item.quantity).toLocaleString('vi-VN') + ' ₫';
  const thumb = item.image
    ? `<img src="${item.image}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover">`
    : `<svg viewBox="0 0 160 80" width="56" height="28"><rect x="8" y="12" width="60" height="50" rx="12" fill="none" stroke="#1a1410" stroke-width="3"/><rect x="92" y="12" width="60" height="50" rx="12" fill="none" stroke="#1a1410" stroke-width="3"/><path d="M68 34 Q80 28 92 34" stroke="#1a1410" stroke-width="2.5" fill="none"/></svg>`;

  return `
    <tr>
      <td>
        <div class="item-cell">
          <div class="item-thumb">${thumb}</div>
          <div>
            <div class="item-name">${escHtml(item.name)}</div>
            <div class="item-cat">${item.category || ''}</div>
          </div>
        </div>
      </td>
      <td>${price}</td>
      <td>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="changeQty(${item.id}, ${item.quantity - 1})">−</button>
          <span class="qty-val">${item.quantity}</span>
          <button class="qty-btn" onclick="changeQty(${item.id}, ${item.quantity + 1})">+</button>
        </div>
      </td>
      <td style="text-align:right;font-family:'Cormorant Garamond',serif;font-size:1rem">${total}</td>
      <td style="text-align:right">
        <button class="remove-btn" onclick="removeItem(${item.id})">Xoá</button>
      </td>
    </tr>`;
}

function renderEmpty(msg = 'Giỏ hàng của bạn đang trống.') {
  return `
    <div class="cart-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width:56px;height:56px;color:var(--border);margin:0 auto;display:block"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      <p>${msg}</p>
      <a href="Product.php" class="btn-primary" style="display:inline-block;padding:.75rem 2rem;font-size:.78rem;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;color:var(--cream)">Khám phá sản phẩm</a>
    </div>`;
}

// ── Summary sidebar ───────────────────────────────────────────────────────────

function updateSummary(total, items) {
  const fmt   = parseInt(total).toLocaleString('vi-VN') + ' ₫';
  const count = (items || []).reduce((s, i) => s + i.quantity, 0);

  ['summarySubtotal', 'summaryTotal'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = fmt;
  });

  document.querySelectorAll('#cartCount').forEach(el => el.textContent = count);
}

function enableCheckout()  { const b = document.getElementById('checkoutBtn'); if (b) b.disabled = false; }
function disableCheckout() { const b = document.getElementById('checkoutBtn'); if (b) b.disabled = true;  }

// ── Actions ───────────────────────────────────────────────────────────────────

async function changeQty(cartId, newQty) {
  if (newQty < 1) { removeItem(cartId); return; }

  try {
    const res  = await fetch(`${CART_API}/${cartId}`, {
      method:  'PUT',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ quantity: newQty }),
    });
    const data = await res.json();
    if (!res.ok) showToast(data.error || 'Không thể cập nhật.');
    await loadCart();
  } catch { showToast('Lỗi kết nối.'); }
}

async function removeItem(cartId) {
  try {
    await fetch(`${CART_API}/${cartId}`, { method: 'DELETE', credentials: 'include' });
    showToast('Đã xoá sản phẩm.');
    await loadCart();
  } catch { showToast('Lỗi kết nối.'); }
}

async function checkout() {
  const btn = document.getElementById('checkoutBtn');
  if (!btn) return;
  btn.disabled   = true;
  btn.textContent = 'Đang xử lý…';

  try {
    // Lấy cart để tạo payload
    const cartRes  = await fetch(CART_API, { credentials: 'include' });
    const cartData = await cartRes.json();
    const items    = (cartData.items || []).map(i => ({
      productId: i.product_id,
      quantity:  i.quantity,
    }));

    if (!items.length) {
      showToast('Giỏ hàng trống!');
      btn.disabled    = false;
      btn.textContent = 'Đặt hàng ngay';
      return;
    }

    const res  = await fetch('/api/orders', {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ items }),
    });
    const data = await res.json();

    if (res.ok) {
      showToast('Đặt hàng thành công!');
      setTimeout(() => window.location.href = 'order-history.php', 1200);
    } else {
      showToast(data.error || 'Đặt hàng thất bại.');
      btn.disabled    = false;
      btn.textContent = 'Đặt hàng ngay';
      await loadCart();
    }
  } catch {
    showToast('Lỗi kết nối server.');
    btn.disabled    = false;
    btn.textContent = 'Đặt hàng ngay';
  }
}

// ── Global addToCart (override main.js) ──────────────────────────────────────
// Gọi từ các trang sản phẩm — dùng API thay localStorage

async function addToCart(btn, productId, name, price) {
  if (!productId) return;

  const original  = btn.textContent;
  btn.textContent = 'Đang thêm…';
  btn.disabled    = true;

  try {
    const res  = await fetch(CART_API, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ product_id: productId, quantity: 1 }),
    });
    const data = await res.json();

    if (res.status === 401) {
      showToast('Vui lòng đăng nhập để thêm vào giỏ.');
      const modal = document.getElementById('loginModal');
      if (modal) modal.classList.add('show');
      btn.textContent = original;
      btn.disabled    = false;
      return;
    }

    if (!res.ok) {
      showToast(data.error || 'Không thể thêm vào giỏ.');
      btn.textContent = original;
      btn.disabled    = false;
      return;
    }

    showToast('Đã thêm vào giỏ hàng!');
    btn.textContent = '✓ Đã thêm';
    syncCartCount();
    setTimeout(() => { btn.textContent = original; btn.disabled = false; }, 2000);

  } catch {
    showToast('Lỗi kết nối.');
    btn.textContent = original;
    btn.disabled    = false;
  }
}

// Cập nhật số đếm giỏ trên navbar
async function syncCartCount() {
  try {
    const res  = await fetch(CART_API, { credentials: 'include' });
    if (!res.ok) return;
    const data = await res.json();
    const count = (data.items || []).reduce((s, i) => s + i.quantity, 0);
    document.querySelectorAll('#cartCount').forEach(el => el.textContent = count);
  } catch {}
}

// ── Utils ─────────────────────────────────────────────────────────────────────
function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Chỉ load giỏ trên trang cart
  if (document.getElementById('cartContent')) loadCart();
  // Luôn sync số đếm trên navbar
  syncCartCount();
});