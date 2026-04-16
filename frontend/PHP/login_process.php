<?php

session_start();
include "db.php";

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $sql);

// CHỈ KIỂM TRA ĐỂ KHÔNG BỊ CRASH
if ($result && $user = mysqli_fetch_assoc($result)) {

    // BỎ KIỂM TRA PASSWORD để demo bypass
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    header("Location: home.php");
    exit();
}

// Nếu không tìm thấy user hoặc có lỗi
header("Location: home.php?login_error=1");
exit();
