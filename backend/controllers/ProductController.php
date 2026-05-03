<?php
// backend/controllers/ProductController.php
require_once __DIR__ . "/../config/database.php";

class ProductController
{
    private PDO $db;

    public function __construct()
    {
        $db = new Database();
        $this->db = $db->getConnection();
    }

    // =========================================================
    // GET /api/products
    // Query: ?category=regular|sunglasses|prescription
    // =========================================================
    public function getAll(): void
    {
        $category = $_GET['category'] ?? '';
        $valid    = ['regular', 'sunglasses', 'prescription'];

        $sql    = "SELECT product_id, name, description, price, stock, image, category FROM products";
        $params = [];

        if (in_array($category, $valid, true)) {
            $sql   .= " WHERE category = :category";
            $params = [':category' => $category];
        }

        $sql .= " ORDER BY product_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ép kiểu
        foreach ($data as &$row) {
            $row['price'] = (float)$row['price'];
            $row['stock'] = (int)$row['stock'];
        }

        $this->json(['status' => 'success', 'data' => $data]);
    }

    private function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}