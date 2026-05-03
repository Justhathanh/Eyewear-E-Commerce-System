<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $_SESSION['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Giỏ hàng — Vista Optic</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .cart-page { max-width: 960px; margin: 3rem auto; padding: 0 2rem; }

    .cart-title {
      font-family: 'Cormorant Garamond', serif; font-size: 2rem;
      font-weight: 300; margin-bottom: 2rem;
    }

    /* Auth gate */
    .auth-gate {
      text-align: center; padding: 4rem 2rem;
      border: .5px solid var(--border); background: var(--surface);
    }
    .auth-gate p { color: var(--muted); margin: 1rem 0 1.5rem; }

    /* Cart table */
    .cart-table { width: 100%; border-collapse: collapse; }
    .cart-table th {
      font-size: .68rem; letter-spacing: .12em; text-transform: uppercase;
      color: var(--muted); padding: .6rem 0; border-bottom: .5px solid var(--border);
      text-align: left;
    }
    .cart-table td {
      padding: 1rem 0; border-bottom: .5px solid var(--border);
      vertical-align: middle; font-size: .88rem;
    }
    .cart-table tr:last-child td { border-bottom: none; }

    .item-thumb {
      width: 64px; height: 64px; background: var(--surface);
      display: flex; align-items: center; justify-content: center;
      border: .5px solid var(--border); margin-right: .75rem; flex-shrink: 0;
    }
    .item-cell { display: flex; align-items: center; }
    .item-name { font-weight: 500; color: var(--ink); }
    .item-cat  { font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; }

    /* Qty controls */
    .qty-ctrl { display: flex; align-items: center; gap: .5rem; }
    .qty-btn {
      width: 28px; height: 28px; border: .5px solid var(--border);
      background: transparent; cursor: pointer; font-size: 1rem; line-height: 1;
      display: flex; align-items: center; justify-content: center; transition: all .15s;
    }
    .qty-btn:hover { background: var(--ink); color: var(--cream); border-color: var(--ink); }
    .qty-val { min-width: 28px; text-align: center; font-size: .9rem; }

    .remove-btn {
      font-size: .72rem; letter-spacing: .06em; text-transform: uppercase;
      color: var(--muted); background: none; border: none; cursor: pointer;
      padding: 0; transition: color .2s;
    }
    .remove-btn:hover { color: #c0392b; }

    /* Summary */
    .cart-footer { display: flex; justify-content: flex-end; margin-top: 2rem; }
    .cart-summary {
      width: 320px; border: .5px solid var(--border);
      background: var(--surface); padding: 1.5rem;
    }
    .summary-row {
      display: flex; justify-content: space-between;
      font-size: .88rem; padding: .4rem 0; color: var(--muted);
    }
    .summary-row.total {
      border-top: .5px solid var(--border); margin-top: .5rem; padding-top: .8rem;
      font-size: 1rem; color: var(--ink); font-weight: 500;
    }
    .checkout-btn {
      width: 100%; margin-top: 1.25rem; padding: .9rem;
      background: var(--ink); color: var(--cream);
      font-family: 'DM Sans', sans-serif; font-size: .78rem;
      letter-spacing: .1em; text-transform: uppercase;
      border: none; cursor: pointer; transition: background .2s;
    }
    .checkout-btn:hover:not(:disabled) { background: var(--gold); }
    .checkout-btn:disabled { opacity: .5; cursor: not-allowed; }

    /* Empty state */
    .cart-empty {
      text-align: center; padding: 4rem;
      border: .5px solid var(--border); background: var(--surface);
    }
    .cart-empty svg { width: 48px; height: 48px; color: var(--muted); margin-bottom: 1rem; }
    .cart-empty p { color: var(--muted); margin: .5rem 0 1.5rem; font-size: .9rem; }

    .spinner-inline {
      border: 2px solid var(--border); border-top-color: var(--ink);
      border-radius: 50%; width: 20px; height: 20px;
      animation: spin .6s linear infinite; display: inline-block;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>

<!-- NAV -->
<nav id="navbar">
  <div class="nav-logo" onclick="window.location='home.php'">VISTA<span>.</span>OPTIC</div>
  <ul class="nav-links">
    <li><a href="product.php">Kính mắt</a></li>
    <li><a href="product.php?category=sunglasses">Kính râm</a></li>
    <li><a href="#">Đo mắt</a></li>
    <li><a href="#">Thương hiệu</a></li>
  </ul>
  <div class="nav-actions">
    <?php if ($isLoggedIn): ?>
      <div class="user-menu">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <div class="nav-dropdown">
          <p>Xin chào, <?= htmlspecialchars($userName) ?></p>
          <a href="order-history.php">Đơn hàng của tôi</a>
          <a href="logout.php">Đăng xuất</a>
        </div>
      </div>
    <?php else: ?>
      <svg class="nav-icon" id="openLoginBtn" style="cursor:pointer" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <?php endif; ?>
    <button class="cart-btn" style="background:var(--gold)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      Giỏ hàng
    </button>
  </div>
</nav>

<!-- CONTENT -->
<div class="cart-page">
  <h1 class="cart-title">Giỏ hàng của bạn</h1>

  <?php if (!$isLoggedIn): ?>
  <!-- Not logged in -->
  <div class="auth-gate">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:var(--muted);margin:0 auto;display:block"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    <p>Đăng nhập để xem giỏ hàng của bạn</p>
    <button class="btn-primary" id="openLoginBtn">Đăng nhập</button>
  </div>

  <?php else: ?>
  <!-- Logged in — render cart via JS -->
  <div id="cartContent">
    <div style="text-align:center;padding:3rem"><span class="spinner-inline"></span></div>
  </div>
  <?php endif; ?>
</div>

<!-- LOGIN MODAL -->
<?php if (!$isLoggedIn): ?>
<div class="modal-overlay" id="loginModal">
  <span class="close-modal" id="closeLoginBtn">✕</span>
  <div class="modal-container">
    <div class="modal-form" id="formSignin">
      <h2>Đăng nhập</h2>
      <form action="login_process.php" method="POST">
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Mật khẩu" required />
        <button type="submit" class="btn-primary" style="width:100%;margin-top:.5rem">Đăng nhập</button>
      </form>
      <p class="modal-switch">Chưa có tài khoản? <span onclick="switchModal('signup')">Đăng ký</span></p>
    </div>
    <div class="modal-form" id="formSignup" style="display:none">
      <h2>Tạo tài khoản</h2>
      <form action="sign_up.php" method="POST">
        <input type="text"  name="name"     placeholder="Họ tên"   required />
        <input type="email" name="email"    placeholder="Email"     required />
        <input type="password" name="password" placeholder="Mật khẩu" required />
        <button type="submit" class="btn-primary" style="width:100%;margin-top:.5rem">Đăng ký</button>
      </form>
      <p class="modal-switch">Đã có tài khoản? <span onclick="switchModal('signin')">Đăng nhập</span></p>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="toast" class="toast"></div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/cart.js"></script>
</body>
</html>