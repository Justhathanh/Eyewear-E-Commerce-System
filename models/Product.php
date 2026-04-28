<?php
// models/Product.php
class Product {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Lấy tất cả sản phẩm
     */
    public function getAll() {
        $sql = "SELECT id, name, price, image FROM products ORDER BY id ASC";
        $result = $this->conn->query($sql);
        
        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }
}