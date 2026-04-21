<?php

$controller = $_GET['controller'] ?? '';
$action = $_GET['action'] ?? '';

switch ($controller) {

    case 'user':
        require_once "controllers/UserController.php";
        $c = new UserController();
        break;

    case 'product':
        require_once "controllers/ProductController.php";
        $c = new ProductController();
        break;

    case 'order':
        require_once "controllers/OrderController.php";
        $c = new OrderController();
        break;

    case 'orderItem':
        require_once "controllers/OrderItemController.php";
        $c = new OrderItemController();
        break;

    case 'payment':
        require_once "controllers/PaymentController.php";
        $c = new PaymentController();
        break;

    default:
        echo "Invalid controller";
        exit;
}

$c->$action();