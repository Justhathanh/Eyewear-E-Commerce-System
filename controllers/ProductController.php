<?php
require_once __DIR__ . '/../models/Product.php';

class ProductController {
    private $productModel;

    public function __construct($conn) {
        $this->productModel = new Product($conn);
    }

   
    public function index() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $products = $this->productModel->getAll();

            echo json_encode([
                'success' => true,
                'data'    => $products
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách sản phẩm'
            ]);
        }
    }
}