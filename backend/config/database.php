<?php
$host = "mysql";
$db = "eyewear_shop";
$user = "root";
$pass = "123456";

function getConnection() {
    global $host, $db, $user, $pass;

    try {
        $conn = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass
);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}