// frontend/assets/js/order-history.js
alert('JS loaded');
console.log('Order history JS loaded');

const API_BASE  = 'http://localhost:9090/api';
const PAGE_SIZE = 5;

let state = {
  orders:      [],
  allOrders:   [],
  currentPage: 1,
  totalPages:  1,
  loading:     false,
  error:       null,
  filters: { status: '', search: '', sort: 'newest' },
};

// ── API ───────────────────────────────────────────────────────

async function fetchOrdersFromAPI(page = 0) {
  const { status, sort } = state.filters;
const params = new URLSearchParams({
  page,
  size: PAGE_SIZE,
  sort,
  userId: window.USER_ID
});
  if (status) params.set('status', status);

  console.log('Fetching orders from:', `${API_BASE}/orders/history?${params}`);
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

  try {
    const res = await fetch(`${API_BASE}/orders/history?${params}`, { 
      credentials: 'include',
      signal: controller.signal
    });

    clearTimeout(timeoutId);
    console.log('Response status:', res.status);
    if (res.status === 401) throw new Error('Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.');
    if (!res.ok)            throw new Error(`Lỗi máy chủ (${res.status}). Vui lòng thử lại.`);

    const data = await res.json();
    console.log('Response data:', data);
    return data;
  } catch (err) {
    clearTimeout(timeoutId);
    if (err.name === 'AbortError') {
      throw new Error('Yêu cầu bị timeout. Vui lòng thử lại.');
    }
    throw err;
  }
}

// ── Load ──────────────────────────────────────────────────────

// ── Load ──────────────────────────────────────────────────────

async function loadOrders(page = 1) {
  console.log('loadOrders called with page:', page);
  state.loading = true;
  state.error = null;
  renderContainer();

  // Bảo vệ timeout (12 giây)
  const timeoutId = setTimeout(() => {
    if (state.loading) {
      state.loading = false;
      state.error = "Yêu cầu tải quá lâu. Vui lòng kiểm tra kết nối và thử lại.";
      renderContainer();
      console.warn("⏰ LoadOrders bị timeout");
    }
  }, 12000);

  try {
    console.log(`📡 Đang tải trang ${page}...`);
    const data = await fetchOrdersFromAPI(page - 1);

    state.allOrders = data.content ?? [];
    state.totalPages = data.totalPages ?? 1;
    state.currentPage = page;

    applySearchFilter();
    renderStats();
  } catch (err) {
    console.error("❌ LoadOrders Error:", err);
    state.error = err.message || "Không thể tải dữ liệu. Vui lòng thử lại sau.";
  } finally {
    clearTimeout(timeoutId);
    state.loading = false;
    renderContainer();
    renderPagination();
  }
}
function applySearchFilter() {
  const q = (state.filters.search || '').toLowerCase();

  if (!q) {
    state.orders = [...state.allOrders];
    return;
  }

  state.orders = state.allOrders.filter(o =>
    String(o.orderId).toLowerCase().includes(q) ||
    (o.items || []).some(i =>
      (i.name || '').toLowerCase().includes(q)
    )
  );
}
// ── Render ────────────────────────────────────────────────────

function renderStats() {
  const all   = state.allOrders;
  const done  = all.filter(o => o.status === 'COMPLETED').length;
  const spend = all.reduce((s, o) => s + (o.total || 0), 0);

  setText('statTotal', all.length);
  setText('statDone',  done);
  setText('statSpend', formatCurrency(spend));
}

function renderContainer() {
  const el = document.getElementById('ordersContainer');
  if (!el) return;

  if (state.loading) {
    el.innerHTML = `
      <div class="state-box">
        <div class="spinner"></div>
        <div class="state-title">Đang tải đơn hàng…</div>
      </div>`;
    return;
  }

  if (state.error) {
    el.innerHTML = `
      <div class="state-box">
        <div class="state-icon">⚠️</div>
        <div class="state-title">Đã xảy ra lỗi</div>
        <div class="state-desc">${escHtml(state.error)}</div>
        <button class="act-btn act-btn-primary" style="margin-top:1.25rem" onclick="loadOrders()">Thử lại</button>
      </div>`;
    return;
  }

  if (!state.orders.length) {
    el.innerHTML = `
      <div class="state-box">
        <div class="state-icon">🛍️</div>
        <div class="state-title">Chưa có đơn hàng nào</div>
        <div class="state-desc">Hãy khám phá bộ sưu tập kính mắt của chúng tôi</div>
        <a href="Product.php" class="act-btn act-btn-primary" style="display:inline-block;margin-top:1.25rem;text-decoration:none">Mua sắm ngay</a>
      </div>`;
    return;
  }

  el.innerHTML = `<div class="orders-list">${state.orders.map((o, i) => orderCardHtml(o, i)).join('')}</div>`;

  el.querySelectorAll('.order-header').forEach(h => {
    h.addEventListener('click', () => h.closest('.order-card').classList.toggle('open'));
  });
}

function orderCardHtml(order, idx) {
  const badge = statusBadge(order.status);
  const delay = (idx * 60) + 'ms';

  const itemsHtml = (order.items || []).map(item => {
    const thumbInner = item.image
      ? `<img src="${item.image}" alt="" style="width:100%;height:100%;object-fit:cover">`
      : `<span style="font-size:1.4rem">👓</span>`;
    return `
      <tr>
        <td>
          <div class="item-info">
            <div class="item-thumb">${thumbInner}</div>
            <div><div class="item-name">${escHtml(item.name)}</div></div>
          </div>
        </td>
        <td style="text-align:center;color:var(--muted)">${item.quantity}</td>
        <td style="text-align:right">${formatCurrency(item.price)}</td>
        <td style="text-align:right;font-weight:500">${formatCurrency(item.price * item.quantity)}</td>
      </tr>`;
  }).join('');

  const canCancel  = ['PENDING', 'CONFIRMED'].includes(order.status);
  const canReorder = order.status === 'COMPLETED';

  // Badge thanh toán
  const payBadge = paymentBadge(order.paymentMethod, order.paymentStatus);

  return `
    <div class="order-card" style="animation-delay:${delay}">
      <div class="order-header">
        <div>
          <div class="order-id">Mã đơn #${escHtml(String(order.orderId))}</div>
          <div class="order-date">${formatDate(order.createdAt)}</div>
        </div>
        <div class="order-meta">
          <span class="badge ${badge.cls}"><span class="badge-dot"></span>${badge.label}</span>
          ${payBadge ? `<span class="badge ${payBadge.cls}" style="font-size:.65rem">${payBadge.icon} ${payBadge.label}</span>` : ''}
          <span class="order-total">${formatCurrency(order.total)}</span>
          <svg class="chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>
      </div>

      <div class="order-detail">
        <table class="items-table">
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th style="text-align:center">SL</th>
              <th style="text-align:right">Đơn giá</th>
              <th style="text-align:right">Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            ${itemsHtml || '<tr><td colspan="4" style="color:var(--muted);padding:.75rem 0">Không có dữ liệu.</td></tr>'}
          </tbody>
        </table>

        <div class="order-summary">
          <div class="totals">
            <div class="totals-row"><span>Tạm tính</span><span>${formatCurrency(order.total)}</span></div>
            <div class="totals-row"><span>Phí vận chuyển</span><span>Miễn phí</span></div>
            <div class="totals-row grand"><span>Tổng cộng</span><span>${formatCurrency(order.total)}</span></div>
          </div>
        </div>

        <div class="detail-actions">
          ${canReorder ? `<button class="act-btn" onclick="doReorder(${order.orderId})">🔄 Mua lại</button>` : ''}
          ${canCancel  ? `<button class="act-btn act-btn-danger" onclick="doCancel(${order.orderId})">Huỷ đơn</button>` : ''}
        </div>
      </div>
    </div>`;
}

function renderPagination() {
  const el = document.getElementById('pagination');
  if (!el) return;
  const { currentPage, totalPages } = state;

  if (totalPages <= 1) { el.innerHTML = ''; return; }

  let html = `<button class="page-btn" onclick="goPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
  </button>`;

  for (let p = 1; p <= totalPages; p++) {
    if (totalPages > 7 && Math.abs(p - currentPage) > 2 && p !== 1 && p !== totalPages) {
      if (p === 2 || p === totalPages - 1)
        html += `<span style="padding:0 4px;color:var(--muted)">…</span>`;
      continue;
    }
    html += `<button class="page-btn ${p === currentPage ? 'active' : ''}" onclick="goPage(${p})">${p}</button>`;
  }

  html += `<button class="page-btn" onclick="goPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
  </button>`;

  el.innerHTML = html;
}

// ── Actions ───────────────────────────────────────────────────

function goPage(p) {
  if (p < 1 || p > state.totalPages) return;
  loadOrders(p);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function doCancel(orderId) {
  if (!confirm('Bạn có chắc muốn huỷ đơn hàng này?')) return;
  try {
    const res  = await fetch(`${API_BASE}/orders/${orderId}/cancel`, { method: 'PUT', credentials: 'include' });
    const data = await res.json();
    if (res.ok) {
      showToast('Đã huỷ đơn hàng.');
      loadOrders(state.currentPage);
    } else {
      showToast(data.error || 'Không thể huỷ đơn.');
    }
  } catch { showToast('Lỗi kết nối.'); }
}

async function doReorder(orderId) {
  try {
    const res  = await fetch(`${API_BASE}/orders/${orderId}/reorder`, { method: 'POST', credentials: 'include' });
    const data = await res.json();
    if (res.ok) {
      showToast('Đã thêm vào giỏ hàng!');
      setTimeout(() => window.location.href = 'cart.php', 1000);
    } else {
      showToast(data.error || 'Không thể tái đặt hàng.');
    }
  } catch { showToast('Lỗi kết nối.'); }
}

// ── Event listeners ───────────────────────────────────────────

document.getElementById('statusFilter')?.addEventListener('change', e => {
  state.filters.status = e.target.value;
  loadOrders(1);
});

document.getElementById('sortFilter')?.addEventListener('change', e => {
  state.filters.sort = e.target.value;
  loadOrders(1);
});

let searchTimer;
document.getElementById('searchInput')?.addEventListener('input', e => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    state.filters.search = e.target.value.trim();
    applySearchFilter();
    renderContainer();
    renderPagination();
  }, 300);
});

// ── Utils ─────────────────────────────────────────────────────

function formatCurrency(n) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0);
}

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('vi-VN', { day: '2-digit', month: 'long', year: 'numeric' });
}

function statusBadge(status) {
  const map = {
    COMPLETED: { cls: 'badge-success', label: 'Hoàn thành'   },
    SHIPPED:   { cls: 'badge-accent',  label: 'Đang giao'    },
    CONFIRMED: { cls: 'badge-warning', label: 'Đã xác nhận'  },
    CANCELLED: { cls: 'badge-danger',  label: 'Đã huỷ'       },
    PENDING:   { cls: 'badge-gray',    label: 'Chờ xử lý'    },
  };
  return map[status] || { cls: 'badge-gray', label: status };
}

function paymentBadge(method, pStatus) {
  if (!method) return null;
  const icons  = { CASH: '💵', BANK: '🏦', MOMO: '📱' };
  const labels = { CASH: 'COD', BANK: 'Chuyển khoản', MOMO: 'MoMo' };
  const cls    = pStatus === 'PAID' ? 'badge-success' : 'badge-gray';
  return { cls, icon: icons[method] || '', label: labels[method] || method };
}

function setText(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init ──────────────────────────────────────────────────────

console.log('USER_ID:', window.USER_ID);
if (!window.USER_ID) {
  console.error('❌ USER_ID chưa được set!');
}

loadOrders(1);
