<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController
{
    private $product;

    public function __construct()
    {
        $db = (new Database())->connect();
        $this->product = new Product($db);
    }

    public function getAll()
    {
        $stmt = $this->product->getAll();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getById()
    {
        $id = $_GET['id'];
        $stmt = $this->product->getById($id);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function create()
    {
        $data = json_decode(file_get_contents("php://input"));

        $this->product->name = $data->name;
        $this->product->description = $data->description;
        $this->product->price = $data->price;
        $this->product->stock = $data->stock;

        echo json_encode([
            "success" => $this->product->create()
        ]);
    }

    public function delete()
    {
        $this->product->product_id = $_GET['id'];

        echo json_encode([
            "success" => $this->product->delete()
        ]);
    }
}