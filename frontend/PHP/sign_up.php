<?php
include "db.php";

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];

// kiểm tra email đã tồn tại
$check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
    // quay lại home + giữ dữ liệu
    header("Location: home.php?signup_error=1&name=" . urlencode($name) . "&email=" . urlencode($email));
    exit();
}

// không trùng → mới hash + insert
$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (name, email, password)
        VALUES ('$name', '$email', '$hash')";

if (mysqli_query($conn, $sql)) {
    header("Location: home.php?signup_success=1");
    exit();
}
