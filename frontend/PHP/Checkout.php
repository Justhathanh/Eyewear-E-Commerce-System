<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php?login_error=1");
    exit();
}
$userName = $_SESSION['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Thanh toán — Vista Optic</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .checkout-page { max-width: 1100px; margin: 3rem auto; padding: 0 2rem; }
    .checkout-title { font-family:'Cormorant Garamond',serif; font-size:2rem; font-weight:300; margin-bottom:2rem; }

    /* Steps */
    .steps { display:flex; gap:0; margin-bottom:2.5rem; }
    .step {
      flex:1; display:flex; align-items:center; gap:.6rem;
      font-size:.72rem; letter-spacing:.1em; text-transform:uppercase;
      color:var(--muted); padding-bottom:.75rem;
      border-bottom:.5px solid var(--border);
    }
    .step.active { color:var(--ink); border-bottom-color:var(--ink); }
    .step.done   { color:var(--gold); border-bottom-color:var(--gold); }
    .step-num {
      width:24px; height:24px; border-radius:50%; border:.5px solid currentColor;
      display:flex; align-items:center; justify-content:center; font-size:.68rem; flex-shrink:0;
    }
    .step.done .step-num { background:var(--gold); border-color:var(--gold); color:#fff; }

    /* Layout */
    .checkout-layout { display:grid; grid-template-columns:1fr 360px; gap:2rem; align-items:start; }
    @media(max-width:768px) { .checkout-layout { grid-template-columns:1fr; } }

    /* Form sections */
    .form-section {
      background:var(--surface); border:.5px solid var(--border); padding:1.75rem; margin-bottom:1.25rem;
    }
    .form-section-title {
      font-family:'Cormorant Garamond',serif; font-size:1.2rem; font-weight:300;
      margin-bottom:1.25rem; padding-bottom:.75rem; border-bottom:.5px solid var(--border);
    }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .form-group { display:flex; flex-direction:column; gap:.4rem; margin-bottom:.9rem; }
    .form-group:last-child { margin-bottom:0; }
    .form-label { font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
    .form-input {
      padding:.65rem .9rem; border:.5px solid var(--border); background:var(--cream);
      font-family:'DM Sans',sans-serif; font-size:.88rem; color:var(--ink); outline:none;
      transition:border-color .2s;
    }
    .form-input:focus { border-color:var(--gold); }
    .form-select {
      padding:.65rem .9rem; border:.5px solid var(--border); background:var(--cream);
      font-family:'DM Sans',sans-serif; font-size:.88rem; color:var(--ink); outline:none;
      cursor:pointer; appearance:none;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b5d50' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position:right .75rem center;
    }

    /* Payment methods */
    .pay-options { display:flex; flex-direction:column; gap:.75rem; }
    .pay-option {
      display:flex; align-items:center; gap:1rem; padding:1rem 1.25rem;
      border:.5px solid var(--border); cursor:pointer; transition:border-color .2s;
      background:var(--cream);
    }
    .pay-option:hover { border-color:var(--warm); }
    .pay-option.selected { border-color:var(--ink); background:var(--surface); }
    .pay-radio {
      width:18px; height:18px; border-radius:50%; border:.5px solid var(--border);
      display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:border-color .2s;
    }
    .pay-option.selected .pay-radio { border-color:var(--ink); }
    .pay-dot {
      width:10px; height:10px; border-radius:50%; background:var(--ink);
      display:none;
    }
    .pay-option.selected .pay-dot { display:block; }
    .pay-icon { font-size:1.4rem; }
    .pay-label { font-size:.88rem; font-weight:500; }
    .pay-sub   { font-size:.75rem; color:var(--muted); margin-top:.1rem; }

    /* Bank details (hiện khi chọn BANK) */
    .bank-info {
      display:none; margin-top:1rem; padding:1rem; background:#f0ede6;
      border:.5px solid var(--border); font-size:.82rem; line-height:1.8;
    }
    .bank-info.show { display:block; }
    .bank-info strong { display:block; margin-bottom:.25rem; font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }

    /* Order summary sidebar */
    .order-summary {
      background:var(--surface); border:.5px solid var(--border); padding:1.5rem;
      position:sticky; top:90px;
    }
    .summary-title { font-family:'Cormorant Garamond',serif; font-size:1.2rem; font-weight:300; margin-bottom:1.25rem; padding-bottom:.75rem; border-bottom:.5px solid var(--border); }

    .summary-items { margin-bottom:1rem; }
    .summary-item {
      display:flex; gap:.75rem; align-items:center; padding:.6rem 0;
      border-bottom:.5px solid var(--border); font-size:.83rem;
    }
    .summary-item:last-child { border-bottom:none; }
    .s-thumb {
      width:52px; height:52px; background:var(--cream); border:.5px solid var(--border);
      flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center;
    }
    .s-thumb img { width:100%; height:100%; object-fit:cover; }
    .s-name  { font-size:.82rem; font-weight:500; flex:1; }
    .s-qty   { font-size:.75rem; color:var(--muted); margin-top:.15rem; }
    .s-price { font-family:'Cormorant Garamond',serif; font-size:.95rem; white-space:nowrap; }

    .summary-rows { margin-top:.75rem; }
    .s-row { display:flex; justify-content:space-between; font-size:.85rem; padding:.35rem 0; color:var(--muted); }
    .s-row.total { border-top:.5px solid var(--border); margin-top:.5rem; padding-top:.75rem; color:var(--ink); font-weight:500; }
    .s-row.total span:last-child { font-family:'Cormorant Garamond',serif; font-size:1.15rem; }

    .place-btn {
      width:100%; margin-top:1.25rem; padding:.95rem;
      background:var(--ink); color:var(--cream);
      font-family:'DM Sans',sans-serif; font-size:.78rem; letter-spacing:.1em; text-transform:uppercase;
      border:none; cursor:pointer; transition:background .2s;
    }
    .place-btn:hover:not(:disabled) { background:var(--gold); }
    .place-btn:disabled { opacity:.5; cursor:not-allowed; }

    .loading-spinner {
      border:2px solid var(--border); border-top-color:var(--ink);
      border-radius:50%; width:18px; height:18px;
      animation:spin .6s linear infinite; display:inline-block; vertical-align:middle;
    }
    @keyframes spin { to { transform:rotate(360deg); } }

    /* Success overlay */
    .success-overlay {
      display:none; position:fixed; inset:0; background:rgba(26,20,16,.75);
      z-index:2000; align-items:center; justify-content:center;
    }
    .success-overlay.show { display:flex; }
    .success-box {
      background:var(--surface); padding:3rem 2.5rem; max-width:420px; width:90%;
      text-align:center; animation:fadeUp .35s ease;
    }
    .success-icon { font-size:3rem; margin-bottom:1rem; }
    .success-title { font-family:'Cormorant Garamond',serif; font-size:1.8rem; font-weight:300; margin-bottom:.5rem; }
    .success-sub { color:var(--muted); font-size:.88rem; margin-bottom:1.5rem; line-height:1.7; }
    .success-actions { display:flex; gap:.75rem; justify-content:center; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

    .spinner-inline {
      border:2px solid var(--border); border-top-color:var(--ink);
      border-radius:50%; width:20px; height:20px;
      animation:spin .6s linear infinite; display:inline-block;
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav id="navbar">
  <div class="nav-logo" onclick="window.location='home.php'">VISTA<span>.</span>OPTIC</div>
  <ul class="nav-links">
    <li><a href="Product.php">Kính mắt</a></li>
    <li><a href="cart.php">Giỏ hàng</a></li>
  </ul>
  <div class="nav-actions">
    <div class="user-menu">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <div class="nav-dropdown">
        <p>Xin chào, <?= htmlspecialchars($userName) ?></p>
        <a href="order-history.php">Đơn hàng của tôi</a>
        <a href="logout.php">Đăng xuất</a>
      </div>
    </div>
  </div>
</nav>

<!-- PAGE -->
<div class="checkout-page">
  <h1 class="checkout-title">Thanh toán</h1>

  <!-- Steps -->
  <div class="steps">
    <div class="step done"><div class="step-num">✓</div> Giỏ hàng</div>
    <div class="step active"><div class="step-num">2</div> Thông tin giao hàng</div>
    <div class="step"><div class="step-num">3</div> Xác nhận</div>
  </div>

  <div class="checkout-layout">
    <!-- LEFT: form -->
    <div>
      <!-- Thông tin người nhận -->
      <div class="form-section">
        <div class="form-section-title">Thông tin người nhận</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Họ tên *</label>
            <input class="form-input" id="recipientName" type="text" placeholder="Nguyễn Văn A" value="<?= htmlspecialchars($userName) ?>" required />
          </div>
          <div class="form-group">
            <label class="form-label">Số điện thoại *</label>
            <input class="form-input" id="recipientPhone" type="tel" placeholder="0901 234 567" required />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-input" id="recipientEmail" type="email" placeholder="email@example.com" />
        </div>
      </div>

      <!-- Địa chỉ giao hàng -->
      <div class="form-section">
        <div class="form-section-title">Địa chỉ giao hàng</div>
        <div class="form-group">
          <label class="form-label">Địa chỉ *</label>
          <input class="form-input" id="address" type="text" placeholder="Số nhà, tên đường" required />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Quận / Huyện *</label>
            <input class="form-input" id="district" type="text" placeholder="Quận 1" required />
          </div>
          <div class="form-group">
            <label class="form-label">Tỉnh / Thành phố *</label>
            <select class="form-input form-select" id="city">
              <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
              <option value="Hà Nội">Hà Nội</option>
              <option value="Đà Nẵng">Đà Nẵng</option>
              <option value="Cần Thơ">Cần Thơ</option>
              <option value="Khác">Tỉnh / thành khác</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Ghi chú</label>
          <input class="form-input" id="note" type="text" placeholder="Giao giờ hành chính, gọi trước khi giao…" />
        </div>
      </div>

      <!-- Phương thức thanh toán -->
      <div class="form-section">
        <div class="form-section-title">Phương thức thanh toán</div>
        <div class="pay-options">

          <div class="pay-option selected" data-method="CASH" onclick="selectPayment(this)">
            <div class="pay-radio"><div class="pay-dot"></div></div>
            <div class="pay-icon">💵</div>
            <div>
              <div class="pay-label">Thanh toán khi nhận hàng (COD)</div>
              <div class="pay-sub">Trả tiền mặt khi nhận được hàng</div>
            </div>
          </div>

          <div class="pay-option" data-method="BANK" onclick="selectPayment(this)">
            <div class="pay-radio"><div class="pay-dot"></div></div>
            <div class="pay-icon">🏦</div>
            <div>
              <div class="pay-label">Chuyển khoản ngân hàng</div>
              <div class="pay-sub">Chuyển khoản trước, đơn xử lý sau khi xác nhận</div>
            </div>
          </div>

          <div class="pay-option" data-method="MOMO" onclick="selectPayment(this)">
            <div class="pay-radio"><div class="pay-dot"></div></div>
            <div class="pay-icon">📱</div>
            <div>
              <div class="pay-label">Ví MoMo</div>
              <div class="pay-sub">Thanh toán qua ví điện tử MoMo</div>
            </div>
          </div>

        </div>

        <!-- Hiện khi chọn BANK -->
        <div class="bank-info" id="bankInfo">
          <strong>Thông tin chuyển khoản</strong>
          Ngân hàng: <b>MB Bank</b><br>
          Số tài khoản: <b>0164578891125</b><br>
          Chủ tài khoản: <b>Anh DoMiSi</b><br>
          Nội dung: <b>Nà ná na na AnhDoMeeSee + Số điện thoại</b>
        </div>
      </div>
    </div>

    <!-- RIGHT: order summary -->
    <div>
      <div class="order-summary">
        <div class="summary-title">Đơn hàng của bạn</div>
        <div class="summary-items" id="summaryItems">
          <div style="text-align:center;padding:1rem"><span class="spinner-inline"></span></div>
        </div>
        <div class="summary-rows">
          <div class="s-row"><span>Tạm tính</span><span id="subtotalVal">—</span></div>
          <div class="s-row"><span>Phí vận chuyển</span><span>Miễn phí</span></div>
          <div class="s-row total"><span>Tổng cộng</span><span id="totalVal">—</span></div>
        </div>
        <button class="place-btn" id="placeBtn" onclick="placeOrder()" disabled>
          Đặt hàng
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Success overlay -->
<div class="success-overlay" id="successOverlay">
  <div class="success-box">
    <div class="success-icon">🎉</div>
    <div class="success-title">Đặt hàng thành công!</div>
    <div class="success-sub" id="successMsg">
      Đơn hàng của bạn đã được tiếp nhận.<br>
      Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.
    </div>
    <div class="success-actions">
      <button class="btn-outline" onclick="window.location.href='order-history.php'">Xem đơn hàng</button>
      <button class="btn-primary" onclick="window.location.href='Product.php'">Tiếp tục mua</button>
    </div>
  </div>
</div>

<div id="toast" class="toast"></div>

<script src="../assets/js/main.js"></script>
<script>
const CART_API  = 'http://localhost:9090/api/cart';
const ORDER_API = 'http://localhost:9090/api/orders';
let selectedMethod = 'CASH';
let cartItems = [];

// ── Load cart items vào summary ───────────────────────────────
async function loadSummary() {
  try {
    const res  = await fetch(CART_API, { credentials: 'include' });
    if (!res.ok) { window.location.href = 'cart.php'; return; }

    const data = await res.json();
    cartItems  = data.items || [];

    if (!cartItems.length) {
      window.location.href = 'cart.php';
      return;
    }

    const itemsHtml = cartItems.map(item => {
      const thumb = item.image
        ? `<img src="${item.image}" alt="">`
        : `<span style="font-size:1.2rem">👓</span>`;
      return `
        <div class="summary-item">
          <div class="s-thumb">${thumb}</div>
          <div style="flex:1">
            <div class="s-name">${escHtml(item.name)}</div>
            <div class="s-qty">x${item.quantity}</div>
          </div>
          <div class="s-price">${fmt(item.price * item.quantity)}</div>
        </div>`;
    }).join('');

    document.getElementById('summaryItems').innerHTML = itemsHtml;

    const total = data.total || 0;
    document.getElementById('subtotalVal').textContent = fmt(total);
    document.getElementById('totalVal').textContent    = fmt(total);
    document.getElementById('placeBtn').disabled = false;

  } catch (e) {
    document.getElementById('summaryItems').innerHTML = '<p style="color:var(--muted);font-size:.85rem">Không thể tải đơn hàng.</p>';
  }
}

// ── Chọn phương thức thanh toán ───────────────────────────────
function selectPayment(el) {
  document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  selectedMethod = el.dataset.method;
  document.getElementById('bankInfo').classList.toggle('show', selectedMethod === 'BANK');
}

// ── Validate form ─────────────────────────────────────────────
function validateForm() {
  const fields = [
    { id: 'recipientName',  label: 'Họ tên' },
    { id: 'recipientPhone', label: 'Số điện thoại' },
    { id: 'address',        label: 'Địa chỉ' },
    { id: 'district',       label: 'Quận / Huyện' },
  ];
  for (const f of fields) {
    const el = document.getElementById(f.id);
    if (!el.value.trim()) {
      el.focus();
      showToast(`Vui lòng nhập ${f.label}`);
      return false;
    }
  }
  return true;
}

// ── Đặt hàng ─────────────────────────────────────────────────
async function placeOrder() {
  if (!validateForm()) return;
  if (!cartItems.length) { showToast('Giỏ hàng trống!'); return; }

  const btn = document.getElementById('placeBtn');
  btn.disabled   = true;
  btn.innerHTML  = '<span class="loading-spinner"></span> Đang xử lý…';

  const items = cartItems.map(i => ({
    productId: i.product_id,
    quantity:  i.quantity,
  }));

  try {
    const res  = await fetch(ORDER_API, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ items, paymentMethod: selectedMethod }),
    });
    const data = await res.json();

    if (res.ok) {
      // Ghi payment record
      await recordPayment(data.orderId, selectedMethod);

      const methodLabel = { CASH: 'thanh toán khi nhận hàng', BANK: 'chuyển khoản ngân hàng', MOMO: 'ví MoMo' };
      document.getElementById('successMsg').innerHTML =
        `Đơn hàng <b>#${data.orderId}</b> đã được tiếp nhận.<br>
         Phương thức: <b>${methodLabel[selectedMethod] || selectedMethod}</b><br>
         Chúng tôi sẽ liên hệ xác nhận sớm nhất.`;

      document.getElementById('successOverlay').classList.add('show');
    } else {
      showToast(data.error || 'Đặt hàng thất bại, vui lòng thử lại.');
      btn.disabled  = false;
      btn.innerHTML = 'Đặt hàng';
    }
  } catch (err) {
    showToast('Lỗi kết nối server.');
    btn.disabled  = false;
    btn.innerHTML = 'Đặt hàng';
  }
}

async function recordPayment(orderId, method) {
  try {
    await fetch('http://localhost:9090/api/payments', {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ orderId, method }),
    });
  } catch {}  // non-critical
}

function fmt(n) {
  return parseInt(n || 0).toLocaleString('vi-VN') + ' ₫';
}
function escHtml(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.addEventListener('DOMContentLoaded', loadSummary);
</script>
</body>
</html>