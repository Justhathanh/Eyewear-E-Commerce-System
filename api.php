<?php
// api.php
require_once __DIR__ . '/config/database.php';           
require_once __DIR__ . '/controllers/ProductController.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối database']);
    exit;
}

$controller = new ProductController($conn);
$controller->index();

$conn->close();