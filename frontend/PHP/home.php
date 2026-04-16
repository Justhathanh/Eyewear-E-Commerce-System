<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Các biến xử lý Modal báo lỗi/thành công từ trang login cũ
$showModal = isset($_GET['login_error']) || isset($_GET['signup_error']) || isset($_GET['signup_success']) ? 'show' : '';
$activePanel = isset($_GET['signup_error']) ? 'active' : '';
$old_name  = $_GET['name'] ?? '';
$old_email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project</title>

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Boxicons (Cho icon mạng xã hội trong Modal) -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- CSS ngoài -->
    <link rel="stylesheet" href="../assest/css/style.css">
</head>

<body>

<!-- ==========================================
     PHẦN HEADER (TIÊU ĐỀ & MENU ĐIỀU HƯỚNG)
     ========================================== -->
<header>
    <div class="header">
        <img src="../assest/img/logo.png" alt="Logo" class="logo">

        <div class="right">
            <div class="call">
                <i class="fa-solid fa-phone"></i> 
                <span>Call Center</span>
            </div>

            <div class="ship">
                <i class="fa-solid fa-truck"></i> 
                <span>Free Shipping</span>
            </div>
        </div>
    </div>
    
    <div class="menu">
        <ul>
            <li><a href="#">Shop</a></li>
            <li><a href="#">Promo</a></li>
            <li><a href="#">About</a></li>
            <li><a href="#">Blog</a></li>
        </ul>

        <div class="search">
            <input type="text" placeholder="Search what you need"> 
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="icon">
            <a href="#"><i class="fa-regular fa-heart"></i></a>
            <a href="#"><i class="fa-solid fa-cart-arrow-down"></i></a>

            <!-- USER MENU -->
            <div class="user-menu">
                <?php if ($isLoggedIn): ?>
                    <!-- Nếu đã đăng nhập thì hiện dropdown thông tin -->
                    <i class="fa-regular fa-user"></i>
                    <div class="dropdown">
                        <p>Xin chào, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></p>
                        <a href="logout.php">Đăng xuất</a>
                        <a href="delete_account.php"
                           onclick="return confirm('Bạn có chắc muốn xoá tài khoản?');">
                           Xóa tài khoản
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Nếu chưa đăng nhập thì bấm icon sẽ mở Modal Login -->
                    <a href="javascript:void(0)" id="openLoginBtn" style="color: inherit;">
                        <i class="fa-regular fa-user"></i>
                    </a>
                <?php endif; ?>
            </div>

            <a href="#"><i class="fa-regular fa-bell"></i></a>
        </div>
    </div>
</header>

<!-- ==========================================
     PHẦN NỘI DUNG CHÍNH (MAIN CONTENT)
     ========================================== -->
<div class="main">

    <!-- BANNER QUẢNG CÁO -->
    <div class="banner">
        <img src="" alt="">
        <div class="content">
            <h2>Welcome to Our Store</h2>
            <p>Your eyes deserve the best!</p>
            <button>Find Out More</button>
        </div>
    </div>

    <!-- DANH SÁCH SẢN PHẨM (GALLERY) -->
    <div class="gallery">
        <h2 class="title">Our Products</h2>

        <div class="product-item">
            <div class="product">
                <img src="" alt="">
                <div class="category">Category</div>
                <h3 class="title">Product Name</h3>
                <i class="fa-solid fa-arrow-right"></i>
            </div>

            <div class="product">
                <img src="" alt="">
                <div class="category">Category</div>
                <h3 class="title">Product Name</h3>
                <i class="fa-solid fa-arrow-right"></i>
            </div>

            <div class="product">
                <img src="" alt="">
                <div class="category">Category</div>
                <h3 class="title">Product Name</h3>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     PHẦN MODAL OVERLAY (ĐĂNG NHẬP / ĐĂNG KÝ)
     ========================================== -->
<div class="modal-overlay <?= $showModal ?>" id="loginModal">
    <i class='bx bx-x close-modal' id="closeLoginBtn"></i>
    
    <div class="container <?= $activePanel ?>" id="container">
        <!-- SIGN UP -->
        <div class="form-container sign-up">
            <form action="sign_up.php" method="POST">
                <h1>Create Account</h1>
                <div class="social-icons">
                    <a class="icon"><i class='bx bxl-facebook'></i></a>
                    <a class="icon"><i class='bx bxl-google'></i></a>
                    <a class="icon"><i class='bx bxl-linkedin'></i></a>
                    <a class="icon"><i class='bx bxl-github'></i></a>
                </div>
                <span>or use your email for registration</span>
                <input type="text" name="name" placeholder="Name" value="<?= htmlspecialchars($old_name) ?>" required>
                <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($old_email) ?>">
                <input type="password" name="password" placeholder="Password" required>
                <!-- THÔNG BÁO SIGN UP -->
                <?php
                if (isset($_GET['signup_error'])) {
                    echo '<p style="color:red; margin-top:-5px; font-size:14px;">Email đã tồn tại</p>';
                }
                if (isset($_GET['signup_success'])) {
                    echo '<p style="color:green; margin-top:-5px; font-size:14px;">Tạo tài khoản thành công</p>';
                }
                ?>
                <button type="submit">Sign Up</button>
            </form>
        </div>

        <!-- SIGN IN -->
        <div class="form-container sign-in">
            <form action="login_process.php" method="POST">
                <h1>Sign In</h1>
                <div class="social-icons">
                    <a class="icon"><i class='bx bxl-facebook'></i></a>
                    <a class="icon"><i class='bx bxl-google'></i></a>
                    <a class="icon"><i class='bx bxl-linkedin'></i></a>
                    <a class="icon"><i class='bx bxl-github'></i></a>
                </div>
                <span>or use your email password</span>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <a href="#" class="fg">Forgot password?</a>
                <!-- THÔNG BÁO LOGIN -->
                <?php
                if (isset($_GET['login_error'])) {
                    echo '<p style="color:red; margin-top:-15px; font-size:14px;">Tài khoản không tồn tại hoặc sai thông tin</p>';
                }
                ?>
                <button type="submit">Sign In</button>
            </form>
        </div>

        <!-- TOGGLE -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all site features</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Friend!</h1>
                    <p>Register with your personal details to use all site features</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="script.js?v=2"></script>
</body>
</html>