// ─── CONFIG ────────────────────────────────────────────────────────────────

const API_BASE = "http://localhost:8080"; // đổi thành URL thật của backend
const USE_MOCK = false; // ← set false khi backend sẵn sàng
const PAGE_SIZE = 5;

// ─── STATE ─────────────────────────────────────────────────────────────────

let state = {
  orders: [], // danh sách đang hiển thị (sau filter)
  allOrders: [], // toàn bộ đơn hàng (từ API)
  currentPage: 1,
  totalPages: 1,
  loading: false,
  error: null,
  filters: {
    status: "",
    search: "",
    sort: "newest",
  },
};

// ─── API ───────────────────────────────────────────────────────────────────

/**
 * Gọi API lấy danh sách đơn hàng của user đang đăng nhập.
 *
 * Backend cần implement:
 *   GET /api/orders/history
 *   Header: Authorization: Bearer <token>
 *   Query: page (0-based), size, status, sort
 *   Response: { content: [...], totalElements, totalPages, number }
 */
async function fetchOrdersFromAPI(page = 0) {
  const { status, sort } = state.filters;
  const params = new URLSearchParams({
    page,
    size: PAGE_SIZE,
    ...(status && { status }),
    sort,
  });

  // Lấy token từ localStorage (điều chỉnh theo cơ chế auth của project)
  const token = localStorage.getItem("accessToken") || "";

  const res = await fetch(`${API_BASE}/orders/history?${params}`, {
    headers: {
      "Content-Type": "application/json",
      ...(token && { Authorization: `Bearer ${token}` }),
    },
    credentials: "include", // nếu dùng cookie session
  });

  if (res.status === 401)
    throw new Error("Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.");
  if (!res.ok)
    throw new Error(`Lỗi máy chủ (${res.status}). Vui lòng thử lại sau.`);

  return res.json();
}

/**
 * Hàm chính – load orders (mock hoặc API thật).
 */
async function loadOrders(page = 1) {
  state.loading = true;
  state.error = null;
  renderContainer();

  try {
    let data;

    if (USE_MOCK) {
      // ── Giả lập delay mạng ──
      await sleep(700);
      data = getMockData();
    } else {
      data = await fetchOrdersFromAPI(page - 1); // API dùng 0-based
    }

    // Chuẩn hóa về cấu trúc nội bộ
    state.allOrders = data.content ?? data;
    state.totalPages =
      data.totalPages ?? Math.ceil(state.allOrders.length / PAGE_SIZE);
    state.currentPage = page;

    applyLocalFilters(); // lọc + sort phía client (khi dùng mock)
    renderStats();
    renderContainer();
    renderPagination();
  } catch (err) {
    state.error = err.message;
    state.loading = false;
    renderContainer();
  }

  state.loading = false;
}

// ─── FILTER & SORT (client-side cho mock; khi dùng API thật gửi params lên server) ───

function applyLocalFilters() {
  let list = [...state.allOrders];
  const { status, search, sort } = state.filters;

  if (status) list = list.filter((o) => o.status === status);

  if (search) {
    const q = search.toLowerCase();
    list = list.filter(
      (o) =>
        o.orderId.toLowerCase().includes(q) ||
        o.items.some((i) => i.name.toLowerCase().includes(q)),
    );
  }

  if (sort === "newest")
    list.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
  if (sort === "oldest")
    list.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
  if (sort === "highest") list.sort((a, b) => b.total - a.total);
  if (sort === "lowest") list.sort((a, b) => a.total - b.total);

  // Pagination phía client
  const start = (state.currentPage - 1) * PAGE_SIZE;
  state.orders = list.slice(start, start + PAGE_SIZE);
  state.totalPages = Math.ceil(list.length / PAGE_SIZE) || 1;
}

// ─── RENDER ────────────────────────────────────────────────────────────────

function renderStats() {
  const all = state.allOrders;
  const done = all.filter((o) => o.status === "delivered").length;
  const spend = all.reduce((s, o) => s + o.total, 0);

  setText("statTotal", all.length);
  setText("statDone", done);
  setText("statSpend", formatCurrency(spend));
}

function renderContainer() {
  const el = document.getElementById("ordersContainer");

  if (state.loading) {
    el.innerHTML = `
      <div class="state-loading">
        <div class="spinner"></div>
        <div class="state-title">Đang tải đơn hàng…</div>
      </div>`;
    return;
  }

  if (state.error) {
    el.innerHTML = `
      <div class="state-error">
        <div class="state-icon">⚠️</div>
        <div class="state-title">Đã xảy ra lỗi</div>
        <div class="state-desc">${escHtml(state.error)}</div>
        <button class="btn btn-primary" style="margin-top:1.25rem" onclick="loadOrders()">Thử lại</button>
      </div>`;
    return;
  }

  if (!state.orders.length) {
    el.innerHTML = `
      <div class="state-empty">
        <div class="state-icon">🛍️</div>
        <div class="state-title">Chưa có đơn hàng nào</div>
        <div class="state-desc">Hãy khám phá bộ sưu tập mắt kính của chúng tôi</div>
        <a href="/products" class="btn btn-primary" style="margin-top:1.25rem">Mua sắm ngay</a>
      </div>`;
    return;
  }

  el.innerHTML = `<div class="orders-list">${state.orders.map((o, i) => renderOrderCard(o, i)).join("")}</div>`;

  // Toggle expand/collapse
  el.querySelectorAll(".order-header").forEach((header) => {
    header.addEventListener("click", () => {
      header.closest(".order-card").classList.toggle("open");
    });
  });
}

function renderOrderCard(order, idx) {
  const badge = statusBadge(order.status);
  const delay = idx * 60 + "ms";
  const itemsHtml = order.items
    .map(
      (item) => `
    <tr>
      <td>
        <div class="item-info">
          <div class="item-thumb">${item.emoji || "👓"}</div>
          <div>
            <div class="item-name">${escHtml(item.name)}</div>
            <div class="item-variant">${escHtml(item.variant || "")}</div>
          </div>
        </div>
      </td>
      <td style="text-align:center;color:var(--text-secondary)">${item.qty}</td>
      <td style="text-align:right">${formatCurrency(item.price)}</td>
      <td style="text-align:right;font-weight:500">${formatCurrency(item.price * item.qty)}</td>
    </tr>`,
    )
    .join("");

  return `
    <div class="order-card" style="animation-delay:${delay}">
      <div class="order-header">
        <div>
          <div class="order-id">#${escHtml(order.orderId)}</div>
          <div class="order-date">${formatDate(order.createdAt)}</div>
        </div>
        <div class="order-meta">
          <span class="badge ${badge.cls}">
            <span class="badge-dot"></span>${badge.label}
          </span>
          <span class="order-total">${formatCurrency(order.total)}</span>
          <svg class="chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
          <tbody>${itemsHtml}</tbody>
        </table>

        <div class="order-summary">
          <div class="order-address">
            <strong>Địa chỉ giao hàng</strong>
            ${escHtml(order.shippingAddress || "—")}
          </div>
          <div class="totals">
            <div class="totals-row"><span>Tạm tính</span><span>${formatCurrency(order.subtotal ?? order.total)}</span></div>
            <div class="totals-row"><span>Phí vận chuyển</span><span>${order.shippingFee ? formatCurrency(order.shippingFee) : "Miễn phí"}</span></div>
            ${order.discount ? `<div class="totals-row"><span>Giảm giá</span><span style="color:var(--success)">-${formatCurrency(order.discount)}</span></div>` : ""}
            <div class="totals-row grand"><span>Tổng cộng</span><span>${formatCurrency(order.total)}</span></div>
          </div>
        </div>

        <div class="detail-actions">
          <button class="btn" onclick="printOrder('${order.orderId}')">🖨 In hoá đơn</button>
          ${
            order.status === "delivered"
              ? `<button class="btn" onclick="reorder('${order.orderId}')">🔄 Mua lại</button>`
              : ""
          }
          ${
            order.status === "processing"
              ? `<button class="btn" style="color:var(--danger);border-color:var(--danger)" onclick="cancelOrder('${order.orderId}')">Huỷ đơn</button>`
              : ""
          }
          <a href="/orders/${order.orderId}" class="btn btn-primary">Chi tiết</a>
        </div>
      </div>
    </div>`;
}

function renderPagination() {
  const el = document.getElementById("pagination");
  const { currentPage, totalPages } = state;
  if (totalPages <= 1) {
    el.innerHTML = "";
    return;
  }

  let html = "";

  // Prev
  html += `<button class="page-btn" onclick="goPage(${currentPage - 1})" ${currentPage === 1 ? "disabled" : ""}>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
  </button>`;

  // Pages
  for (let p = 1; p <= totalPages; p++) {
    if (
      totalPages > 7 &&
      Math.abs(p - currentPage) > 2 &&
      p !== 1 &&
      p !== totalPages
    ) {
      if (p === 2 || p === totalPages - 1)
        html += `<span style="padding:0 4px;color:var(--text-muted)">…</span>`;
      continue;
    }
    html += `<button class="page-btn ${p === currentPage ? "active" : ""}" onclick="goPage(${p})">${p}</button>`;
  }

  // Next
  html += `<button class="page-btn" onclick="goPage(${currentPage + 1})" ${currentPage === totalPages ? "disabled" : ""}>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
  </button>`;

  el.innerHTML = html;
}

// ─── ACTIONS ───────────────────────────────────────────────────────────────

function goPage(p) {
  if (p < 1 || p > state.totalPages) return;
  state.currentPage = p;
  applyLocalFilters();
  renderContainer();
  renderPagination();
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function printOrder(orderId) {
  // Mở trang in hoặc gọi API xuất PDF
  window.open(`/api/orders/${orderId}/invoice`, "_blank");
}

function reorder(orderId) {
  // Thêm lại tất cả sản phẩm của đơn cũ vào giỏ hàng
  fetch(`${API_BASE}/orders/${orderId}/reorder`, {
    method: "POST",
    credentials: "include",
  })
    .then((r) =>
      r.ok
        ? (window.location.href = "/cart")
        : alert("Không thể tái đặt hàng."),
    )
    .catch(() => alert("Lỗi kết nối."));
}

function cancelOrder(orderId) {
  if (!confirm("Bạn có chắc muốn huỷ đơn hàng này?")) return;
  fetch(`${API_BASE}/orders/${orderId}/cancel`, {
    method: "PUT",
    credentials: "include",
  })
    .then((r) => {
      if (r.ok) loadOrders(state.currentPage);
      else alert("Không thể huỷ đơn hàng.");
    })
    .catch(() => alert("Lỗi kết nối."));
}

// ─── EVENT LISTENERS ───────────────────────────────────────────────────────

document.getElementById("statusFilter").addEventListener("change", (e) => {
  state.filters.status = e.target.value;
  state.currentPage = 1;
  applyLocalFilters();
  renderContainer();
  renderPagination();
});

document.getElementById("sortFilter").addEventListener("change", (e) => {
  state.filters.sort = e.target.value;
  state.currentPage = 1;
  applyLocalFilters();
  renderContainer();
  renderPagination();
});

let searchDebounce;
document.getElementById("searchInput").addEventListener("input", (e) => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    state.filters.search = e.target.value.trim();
    state.currentPage = 1;
    applyLocalFilters();
    renderContainer();
    renderPagination();
  }, 300);
});

// ─── UTILS ─────────────────────────────────────────────────────────────────

function formatCurrency(n) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
}

function formatDate(iso) {
  return new Date(iso).toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}

function statusBadge(status) {
  const map = {
    COMPLETED: { cls: "badge-success", label: "Hoàn thành" },
    SHIPPED: { cls: "badge-accent", label: "Đang giao" },
    CONFIRMED: { cls: "badge-warning", label: "Đã xác nhận" },
    CANCELLED: { cls: "badge-danger", label: "Đã huỷ" },
    PENDING: { cls: "badge-gray", label: "Chờ xử lý" },
  };
  return map[status] || { cls: "badge-gray", label: status };
}

function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

function escHtml(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

// ─── MOCK DATA ─────────────────────────────────────────────────────────────

function getMockData() {
  return {
    content: [
      {
        orderId: "OPT-2024-0892",
        createdAt: "2024-11-20T10:30:00",
        status: "delivered",
        total: 2850000,
        subtotal: 2850000,
        shippingFee: 0,
        discount: 150000,
        shippingAddress: "123 Nguyễn Huệ, P. Bến Nghé, Q.1, TP.HCM",
        items: [
          {
            name: "Ray-Ban Aviator Classic",
            variant: "Gọng vàng / Tròng khói",
            qty: 1,
            price: 2200000,
            emoji: "🕶️",
          },
          {
            name: "Dung dịch vệ sinh mắt kính",
            variant: "100ml",
            qty: 2,
            price: 150000,
            emoji: "🧴",
          },
        ],
      },
      {
        orderId: "OPT-2024-0851",
        createdAt: "2024-10-08T14:15:00",
        status: "shipping",
        total: 1590000,
        subtotal: 1590000,
        shippingFee: 30000,
        shippingAddress: "45 Lê Lợi, P. Bến Thành, Q.1, TP.HCM",
        items: [
          {
            name: "Oakley Holbrook",
            variant: "Gọng đen / Tròng đỏ",
            qty: 1,
            price: 1560000,
            emoji: "😎",
          },
        ],
      },
      {
        orderId: "OPT-2024-0790",
        createdAt: "2024-09-15T09:00:00",
        status: "delivered",
        total: 4500000,
        subtotal: 4700000,
        shippingFee: 0,
        discount: 200000,
        shippingAddress: "78 Trần Hưng Đạo, Q.5, TP.HCM",
        items: [
          {
            name: "Tom Ford FT0237",
            variant: "Gọng vàng rose / Tròng gradient",
            qty: 1,
            price: 4500000,
            emoji: "👓",
          },
        ],
      },
      {
        orderId: "OPT-2024-0712",
        createdAt: "2024-08-02T16:45:00",
        status: "processing",
        total: 980000,
        subtotal: 980000,
        shippingFee: 30000,
        shippingAddress: "10 Phạm Văn Đồng, Bình Thạnh, TP.HCM",
        items: [
          {
            name: "Warby Parker Haskell",
            variant: "Gọng tortoise / Tròng trong",
            qty: 1,
            price: 950000,
            emoji: "🤓",
          },
        ],
      },
      {
        orderId: "OPT-2024-0644",
        createdAt: "2024-07-19T11:20:00",
        status: "cancelled",
        total: 3200000,
        subtotal: 3200000,
        shippingFee: 0,
        shippingAddress: "200 Nguyễn Văn Cừ, Q.5, TP.HCM",
        items: [
          {
            name: "Persol PO3152S",
            variant: "Gọng xanh havana / Tròng xanh",
            qty: 1,
            price: 3200000,
            emoji: "🕶️",
          },
        ],
      },
      {
        orderId: "OPT-2024-0601",
        createdAt: "2024-06-05T08:00:00",
        status: "delivered",
        total: 720000,
        subtotal: 720000,
        shippingFee: 0,
        shippingAddress: "33 Bùi Thị Xuân, Q. Tân Bình, TP.HCM",
        items: [
          {
            name: "Hộp đựng mắt kính cao cấp",
            variant: "Màu nâu da",
            qty: 2,
            price: 250000,
            emoji: "📦",
          },
          {
            name: "Khăn lau mắt kính microfiber",
            variant: "Set 5 cái",
            qty: 1,
            price: 120000,
            emoji: "🧤",
          },
          {
            name: "Xịt vệ sinh chống bụi",
            variant: "150ml",
            qty: 1,
            price: 100000,
            emoji: "🧴",
          },
        ],
      },
    ],
    totalPages: 2,
    totalElements: 6,
  };
}

// ─── INIT ──────────────────────────────────────────────────────────────────

loadOrders(1);
