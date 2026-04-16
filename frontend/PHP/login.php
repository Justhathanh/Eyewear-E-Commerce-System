<?php
$active = isset($_GET['signup_error']) ? 'active' : '';
$name  = $_GET['name'] ?? '';
$email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <!-- Liên kết CSS (đã được gộp chung) -->
    <link rel="stylesheet" href="../assest/css/style.css">
    <!-- Icon Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body class="login-body">

<div class="container <?= $active ?>" id="container">

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

            <input type="text" name="name" placeholder="Name"
                   value="<?= htmlspecialchars($name) ?>" required>

            <input type="email" name="email" placeholder="Email"
                   value="<?= htmlspecialchars($email) ?>" >

            <input type="password" name="password" placeholder="Password" required>

            <!-- THÔNG BÁO SIGN UP -->
            <?php
            if (isset($_GET['signup_error'])) {
                echo '<p style="color:red;">Email đã tồn tại</p>';
            }
            if (isset($_GET['signup_success'])) {
                echo '<p style="color:red;">Tạo tài khoản thành công</p>';
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
                echo '<p style="color:red; margin-top:-15px;">Tài khoản không tồn tại</p>';
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

<script src="script.js"></script>
</body>
</html>
