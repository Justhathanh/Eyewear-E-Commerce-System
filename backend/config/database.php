<?php
// backend/config/database.php
class Database {
    private string $host = "mysql";
    private string $db   = "eyewear_shop";
    private string $user = "root";
    private string $pass = "123456";

    public function getConnection(): PDO {
        $pdo = new PDO(
            "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4",
            $this->user,
            $this->pass
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }
}