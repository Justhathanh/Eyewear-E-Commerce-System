<?php
require_once __DIR__ . "/../config/database.php";

class ProductController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // GET /api/products
    public function getAll(): void {
        header('Content-Type: application/json; charset=utf-8');

        $stmt = $this->db->query("SELECT product_id, name, description, price, stock, image, category FROM products ORDER BY product_id ASC");
        $data = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
    }
}