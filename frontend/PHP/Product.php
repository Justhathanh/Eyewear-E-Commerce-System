<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $_SESSION['name'] ?? '';
$category   = $_GET['category'] ?? '';
$validCats  = ['regular' => 'Kính mắt thường', 'sunglasses' => 'Kính râm', 'prescription' => 'Kính theo đơn'];
$pageTitle  = isset($validCats[$category]) ? $validCats[$category] : 'Tất cả sản phẩm';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — Vista Optic</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .page-hero {
      background: var(--ink); color: var(--cream);
      padding: 3rem 3rem 2rem; text-align: center;
    }
    .page-hero h1 { font-family: 'Cormorant Garamond', serif; font-size: 2.4rem; font-weight: 300; }
    .page-hero p  { font-size: .85rem; color: rgba(245,240,232,.5); margin-top: .5rem; }

    .product-page { padding: 3rem; }

    .cat-filter { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 2rem; align-items: center; }
    .cat-filter a {
      font-size: .72rem; letter-spacing: .08em; text-transform: uppercase;
      padding: .45rem 1.1rem; border: .5px solid var(--border);
      color: var(--muted); text-decoration: none; transition: all .2s;
    }
    .cat-filter a:hover, .cat-filter a.active {
      background: var(--ink); color: var(--cream); border-color: var(--ink);
    }

    .prod-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
    @media (max-width: 1024px) { .prod-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px)  { .prod-grid { grid-template-columns: 1fr; } .product-page { padding: 1.5rem; } }

    .loading-msg { color: var(--muted); font-size: .85rem; }
  </style>
</head>
<body>

<!-- NAV -->
<nav id="navbar">
  <div class="nav-logo" onclick="window.location='home.php'">VISTA<span>.</span>OPTIC</div>
  <ul class="nav-links">
    <li><a href="Product.php" class="<?= !$category ? 'active':'' ?>">Kính mắt</a></li>
    <li><a href="Product.php?category=sunglasses" class="<?= $category==='sunglasses'?'active':'' ?>">Kính râm</a></li>
    <li><a href="#">Đo mắt</a></li>
    <li><a href="#">Thương hiệu</a></li>
    <li><a href="#">Sale</a></li>
  </ul>
  <div class="nav-actions">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>

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

    <button class="cart-btn" onclick="window.location='cart.php'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      Giỏ (<span id="cartCount">0</span>)
    </button>
  </div>
</nav>

<!-- PAGE HERO -->
<div class="page-hero">
  <h1><?= htmlspecialchars($pageTitle) ?></h1>
  <p>Khám phá bộ sưu tập kính mắt được tuyển chọn kỹ lưỡng</p>
</div>

<!-- PRODUCTS -->
<div class="product-page">
  <div class="cat-filter">
    <a href="Product.php" class="<?= !$category ? 'active' : '' ?>">Tất cả</a>
    <a href="Product.php?category=regular"      class="<?= $category==='regular'      ? 'active':'' ?>">Kính mắt thường</a>
    <a href="Product.php?category=sunglasses"   class="<?= $category==='sunglasses'   ? 'active':'' ?>">Kính râm</a>
    <a href="Product.php?category=prescription" class="<?= $category==='prescription' ? 'active':'' ?>">Kính theo đơn</a>
  </div>

  <div class="filter-bar" style="margin-bottom:2rem">
    <button class="filter-btn active" data-filter="all">Tất cả</button>
    <button class="filter-btn" data-filter="available">Có sẵn</button>
    <button class="filter-btn" data-filter="preorder">Pre-order</button>
  </div>

  <div class="prod-grid" id="prodGrid">
    <p class="loading-msg">Đang tải sản phẩm…</p>
  </div>
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
        <input type="text"  name="name"     placeholder="Họ tên" required />
        <input type="email" name="email"    placeholder="Email"   required />
        <input type="password" name="password" placeholder="Mật khẩu" required />
        <button type="submit" class="btn-primary" style="width:100%;margin-top:.5rem">Đăng ký</button>
      </form>
      <p class="modal-switch">Đã có tài khoản? <span onclick="switchModal('signin')">Đăng nhập</span></p>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="toast" class="toast"></div>

<!-- cart.js PHẢI load trước api.js để override addToCart placeholder -->
<script src="../assets/js/main.js"></script>
<script src="../assets/js/cart.js"></script>
<script src="../assets/js/api.js"></script>
<script>
(async function () {
  const grid     = document.getElementById('prodGrid');
  const category = <?= json_encode($category) ?>;
  try {
    const json     = await fetchProducts(category);
    const products = json.data || [];
    if (!products.length) {
      grid.innerHTML = '<p style="color:var(--muted)">Không có sản phẩm nào.</p>';
      return;
    }
    grid.innerHTML = products.map(renderProductCard).join('');
    initFadeObserver();
  } catch (e) {
    grid.innerHTML = '<p style="color:var(--muted)">Không thể tải sản phẩm.</p>';
  }
})();
</script>
</body>
</html>