<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/OrderItem.php';

class OrderItemController
{
    private $orderItem;

    public function __construct()
    {
        $db = (new Database())->connect();
        $this->orderItem = new OrderItem($db);
    }

    public function getByOrder()
    {
        $orderId = $_GET['orderId'];

        $stmt = $this->orderItem->getByOrder($orderId);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create()
    {
        $data = json_decode(file_get_contents("php://input"));

        $this->orderItem->order_id = $data->order_id;
        $this->orderItem->product_id = $data->product_id;
        $this->orderItem->quantity = $data->quantity;
        $this->orderItem->price = $data->price;

        echo json_encode([
            "success" => $this->orderItem->create()
        ]);
    }
}