<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';

class OrderController
{
    private $order;

    public function __construct()
    {
        $db = (new Database())->connect();
        $this->order = new Order($db);
    }

    public function getAll()
    {
        $stmt = $this->order->getAll();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getByUser()
    {
        $userId = $_GET['userId'];
        $stmt = $this->order->getByUser($userId);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create()
    {
        $data = json_decode(file_get_contents("php://input"));

        $this->order->user_id = $data->user_id;
        $this->order->total_price = $data->total_price;
        $this->order->status = "PENDING";

        echo json_encode([
            "success" => $this->order->create()
        ]);
    }
}