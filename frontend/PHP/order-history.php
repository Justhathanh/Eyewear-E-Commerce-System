<?php
// frontend/PHP/order-history.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: home.php?login_error=1");
    exit();
}

$userName = $_SESSION['name'] ?? 'Khách';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lịch sử đơn hàng — Vista Optic</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    :root {
      --success:#2e7d52; --success-light:#ebf5ef;
      --danger:#b91c1c;  --danger-light:#fef2f2;
      --warning:#b45309; --warning-light:#fef3e2;
      --accent:#2c5f8a;  --accent-light:#ebf2f9;
      --gray-light:#f4f2ed;
    }

    /* ── Page layout ── */
    .oh-page { max-width:1100px; margin:0 auto; padding:2.5rem 1.5rem; }
    .oh-header { margin-bottom:2rem; }
    .oh-header h1 { font-family:'Cormorant Garamond',serif; font-size:2rem; font-weight:300; margin-bottom:.25rem; }
    .oh-header p  { color:var(--muted); font-size:.88rem; }

    /* ── Stats ── */
    .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:2rem; }
    @media(max-width:640px){ .stats-row{ grid-template-columns:1fr; } }
    .stat-card { background:var(--surface); border:.5px solid var(--border); padding:1.25rem 1.5rem; }
    .stat-label { font-size:.68rem; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:.4rem; }
    .stat-value { font-family:'Cormorant Garamond',serif; font-size:2rem; font-weight:300; }

    /* ── Controls ── */
    .controls { display:flex; gap:.75rem; margin-bottom:1.5rem; flex-wrap:wrap; align-items:center; }
    .search-wrap { flex:1; min-width:200px; position:relative; }
    .search-wrap svg { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none; }
    .oh-search {
      width:100%; padding:.6rem 1rem .6rem 2.4rem;
      border:.5px solid var(--border); background:var(--surface);
      font-family:'DM Sans',sans-serif; font-size:.875rem; color:var(--ink); outline:none;
      transition:border-color .2s;
    }
    .oh-search:focus { border-color:var(--gold); }
    .oh-select {
      padding:.6rem 1rem; border:.5px solid var(--border);
      background:var(--surface); font-family:'DM Sans',sans-serif;
      font-size:.875rem; color:var(--ink); outline:none; cursor:pointer;
    }

    /* ── Order cards ── */
    .orders-list { display:flex; flex-direction:column; gap:1rem; }
    .order-card {
      background:var(--surface); border:.5px solid var(--border); overflow:hidden;
      transition:box-shadow .2s, transform .2s;
      animation:fadeUp .3s ease both;
    }
    .order-card:hover { box-shadow:0 4px 20px rgba(26,20,16,.08); transform:translateY(-1px); }

    .order-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:1rem 1.5rem; cursor:pointer; user-select:none;
      border-bottom:.5px solid transparent; transition:background .15s;
    }
    .order-header:hover { background:var(--gray-light); }
    .order-card.open .order-header { border-bottom-color:var(--border); }

    .order-id   { font-size:.75rem; color:var(--muted); font-weight:500; margin-bottom:.2rem; }
    .order-date { font-size:.875rem; font-weight:500; }
    .order-meta { display:flex; align-items:center; gap:1.5rem; }
    .order-total { font-family:'Cormorant Garamond',serif; font-size:1.1rem; }

    /* ── Badge ── */
    .badge { display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .75rem; font-size:.7rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase; white-space:nowrap; }
    .badge-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
    .badge-success  { background:var(--success-light); color:var(--success); }
    .badge-success  .badge-dot { background:var(--success); }
    .badge-warning  { background:var(--warning-light); color:var(--warning); }
    .badge-warning  .badge-dot { background:var(--warning); }
    .badge-danger   { background:var(--danger-light);  color:var(--danger);  }
    .badge-danger   .badge-dot { background:var(--danger); }
    .badge-accent   { background:var(--accent-light);  color:var(--accent);  }
    .badge-accent   .badge-dot { background:var(--accent); }
    .badge-gray     { background:var(--gray-light); color:var(--muted); }
    .badge-gray     .badge-dot { background:var(--muted); }

    .chevron { width:18px; height:18px; color:var(--muted); transition:transform .25s; flex-shrink:0; }
    .order-card.open .chevron { transform:rotate(180deg); }

    /* ── Detail panel ── */
    .order-detail { display:none; padding:1.25rem 1.5rem; }
    .order-card.open .order-detail { display:block; }

    .items-table { width:100%; border-collapse:collapse; font-size:.875rem; margin-bottom:1rem; }
    .items-table th {
      text-align:left; color:var(--muted); font-weight:500;
      font-size:.68rem; text-transform:uppercase; letter-spacing:.08em;
      padding:.4rem 0; border-bottom:.5px solid var(--border);
    }
    .items-table td { padding:.75rem 0; border-bottom:.5px solid var(--border); vertical-align:middle; }
    .items-table tr:last-child td { border-bottom:none; }

    .item-thumb {
      width:48px; height:48px; background:var(--gray-light);
      display:flex; align-items:center; justify-content:center;
      font-size:1.4rem; margin-right:.75rem; flex-shrink:0; overflow:hidden;
    }
    .item-thumb img { width:100%; height:100%; object-fit:cover; }
    .item-info { display:flex; align-items:center; }
    .item-name { font-weight:500; font-size:.875rem; }

    .order-summary { display:flex; justify-content:flex-end; margin-top:.5rem; }
    .totals { min-width:220px; }
    .totals-row { display:flex; justify-content:space-between; font-size:.875rem; padding:.3rem 0; color:var(--muted); }
    .totals-row.grand { border-top:.5px solid var(--border); margin-top:.4rem; padding-top:.6rem; color:var(--ink); font-weight:600; font-size:1rem; }

    .detail-actions { display:flex; gap:.75rem; margin-top:1.25rem; flex-wrap:wrap; }
    .act-btn {
      padding:.5rem 1.2rem; border:.5px solid var(--border); background:var(--surface);
      font-family:'DM Sans',sans-serif; font-size:.78rem; letter-spacing:.06em; text-transform:uppercase;
      cursor:pointer; color:var(--ink); transition:all .15s;
    }
    .act-btn:hover { background:var(--gray-light); }
    .act-btn-primary { background:var(--ink); color:var(--cream); border-color:var(--ink); }
    .act-btn-primary:hover { background:var(--warm); border-color:var(--warm); }
    .act-btn-danger { color:var(--danger); border-color:var(--danger); }
    .act-btn-danger:hover { background:var(--danger); color:#fff; }

    /* ── States ── */
    .state-box { text-align:center; padding:4rem 2rem; }
    .state-icon { font-size:2.5rem; margin-bottom:1rem; }
    .state-title { font-family:'Cormorant Garamond',serif; font-size:1.4rem; font-weight:300; margin-bottom:.5rem; }
    .state-desc { color:var(--muted); font-size:.88rem; }
    .spinner {
      width:36px; height:36px; border:2px solid var(--border);
      border-top-color:var(--gold); border-radius:50%;
      animation:spin .7s linear infinite; margin:0 auto 1rem;
    }
    @keyframes spin { to{ transform:rotate(360deg); } }

    /* ── Pagination ── */
    .pagination { display:flex; justify-content:center; gap:.5rem; margin-top:2rem; }
    .page-btn {
      width:36px; height:36px; border:.5px solid var(--border); background:var(--surface);
      color:var(--muted); font-family:'DM Sans',sans-serif; font-size:.875rem;
      cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .15s;
    }
    .page-btn:hover:not(:disabled) { background:var(--gray-light); color:var(--ink); }
    .page-btn.active { background:var(--ink); color:var(--cream); border-color:var(--ink); }
    .page-btn:disabled { opacity:.4; cursor:not-allowed; }

    @media(max-width:768px){
      .order-meta { flex-direction:column; align-items:flex-end; gap:.5rem; }
      .order-summary { flex-direction:column; }
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav id="navbar">
  <div class="nav-logo" onclick="window.location='home.php'">VISTA<span>.</span>OPTIC</div>
  <ul class="nav-links">
    <li><a href="home.php">Trang chủ</a></li>
    <li><a href="Product.php">Kính mắt</a></li>
    <li><a href="order-history.php" style="color:var(--ink)">Đơn hàng</a></li>
  </ul>
  <div class="nav-actions">
    <div class="user-menu">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <div class="nav-dropdown">
        <p>Xin chào, <?= htmlspecialchars($userName) ?></p>
        <a href="order-history.php">Đơn hàng của tôi</a>
        <a href="logout.php">Đăng xuất</a>
        <a href="delete_account.php" onclick="return confirm('Xoá tài khoản?')">Xoá tài khoản</a>
      </div>
    </div>
    <button class="cart-btn" onclick="window.location='cart.php'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      Giỏ (<span id="cartCount">0</span>)
    </button>
  </div>
</nav>

<!-- MAIN -->
<div class="oh-page">
  <div class="oh-header">
    <h1>Lịch sử đơn hàng</h1>
    <p>Xem và quản lý tất cả đơn hàng của bạn</p>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card"><div class="stat-label">Tổng đơn hàng</div><div class="stat-value" id="statTotal">—</div></div>
    <div class="stat-card"><div class="stat-label">Đã hoàn thành</div><div class="stat-value" id="statDone">—</div></div>
    <div class="stat-card"><div class="stat-label">Tổng chi tiêu</div><div class="stat-value" id="statSpend">—</div></div>
  </div>

  <!-- Controls -->
  <div class="controls">
    <div class="search-wrap">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <input type="text" id="searchInput" class="oh-search" placeholder="Tìm theo mã đơn, sản phẩm…" />
    </div>
    <select class="oh-select" id="statusFilter">
      <option value="">Tất cả trạng thái</option>
      <option value="PENDING">Chờ xử lý</option>
      <option value="CONFIRMED">Đã xác nhận</option>
      <option value="SHIPPED">Đang giao</option>
      <option value="COMPLETED">Hoàn thành</option>
      <option value="CANCELLED">Đã huỷ</option>
    </select>
    <select class="oh-select" id="sortFilter">
      <option value="newest">Mới nhất</option>
      <option value="oldest">Cũ nhất</option>
      <option value="highest">Giá cao nhất</option>
      <option value="lowest">Giá thấp nhất</option>
    </select>
  </div>

  <div id="ordersContainer">
    <div class="state-box"><div class="spinner"></div><div class="state-title">Đang tải đơn hàng…</div></div>
  </div>

  <div class="pagination" id="pagination"></div>
</div>

<div id="toast" class="toast"></div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/order-history.js"></script>
</body>
</html>