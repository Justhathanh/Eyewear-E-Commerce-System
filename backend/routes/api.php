<?php

$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// remove query string
$request = explode("?", $request)[0];

// xử lý path khi có /index.php
$request = str_replace("/index.php", "", $request);

// ROUTE
if ($request == "/api/products" && $method == "GET") {
    require_once __DIR__ . "/../controllers/ProductController.php";
    (new ProductController())->getAll();
} 
else {
    echo json_encode([
        "status" => "error",
        "message" => "Route not found",
        "request" => $request
    ]);
}