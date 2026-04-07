<?php
require_once __DIR__ . "/../config/database.php";
header('Content-Type: application/json; charset=utf-8');
class ProductController {

    public function getAll() {
        
        $conn = getConnection();

        $stmt = $conn->query("SELECT * FROM products");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    }
}