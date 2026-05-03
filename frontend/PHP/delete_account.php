<?php
session_start();
require_once "db.php";

// Check if $pdo is defined (assuming it's set in db.php)
if (!isset($pdo)) {
    die("Database connection failed.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
} catch (PDOException $e) {
    // Log error or handle gracefully (e.g., redirect with error message)
    error_log("Deletion failed: " . $e->getMessage());
    header("Location: home.php?error=deletion_failed");
    exit();
}

session_destroy();
header("Location: home.php");
exit();