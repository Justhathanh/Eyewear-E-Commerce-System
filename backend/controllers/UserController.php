<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class UserController
{
    private $user;

    public function __construct()
    {
        $db = (new Database())->connect();
        $this->user = new User($db);
    }

    public function getAll()
    {
        $stmt = $this->user->getAll();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getById()
    {
        $id = $_GET['id'];
        $stmt = $this->user->getById($id);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function create()
    {
        $data = json_decode(file_get_contents("php://input"));

        $this->user->name = $data->name;
        $this->user->email = $data->email;
        $this->user->password = $data->password;
        $this->user->role = $data->role ?? "CUSTOMER";

        echo json_encode([
            "success" => $this->user->create()
        ]);
    }
}