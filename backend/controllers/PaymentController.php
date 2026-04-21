<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Payment.php';

class PaymentController
{
    private $payment;

    public function __construct()
    {
        $db = (new Database())->connect();
        $this->payment = new Payment($db);
    }

    public function create()
    {
        $data = json_decode(file_get_contents("php://input"));

        $this->payment->order_id = $data->order_id;
        $this->payment->amount = $data->amount;
        $this->payment->method = $data->method;
        $this->payment->status = "PAID";

        echo json_encode([
            "success" => $this->payment->create()
        ]);
    }

    public function getByOrder()
    {
        $orderId = $_GET['orderId'];

        $stmt = $this->payment->getByOrder($orderId);

        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }
}