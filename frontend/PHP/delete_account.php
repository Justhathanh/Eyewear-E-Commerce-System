<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

session_destroy();
header("Location: home.php");
exit();