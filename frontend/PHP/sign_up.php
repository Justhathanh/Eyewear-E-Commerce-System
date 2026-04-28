<?php
require_once "db.php";

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$name || !$email || !$password) {
    header("Location: home.php?signup_error=1");
    exit();
}

$check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$check->execute([$email]);

if ($check->fetch()) {
    header("Location: home.php?signup_error=1&name=" . urlencode($name) . "&email=" . urlencode($email));
    exit();
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $hash]);

header("Location: home.php?signup_success=1");
exit();