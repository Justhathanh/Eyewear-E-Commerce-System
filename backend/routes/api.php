<?php

$request = $_SERVER['REQUEST_URI'];
$method  = $_SERVER['REQUEST_METHOD'];

// remove query string
$request = explode("?", $request)[0];

// xử lý path khi có /index.php
$request = str_replace("/index.php", "", $request);

// ── CORS (nếu frontend và backend khác port) ──────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($method === 'OPTIONS') { http_response_code(204); exit; }

// ─────────────────────────────────────────────────────────────
// ROUTES
// ─────────────────────────────────────────────────────────────

// ── Products ─────────────────────────────────────────────────
if ($request === "/api/products" && $method === "GET") {
    require_once __DIR__ . "/../controllers/ProductController.php";
    (new ProductController())->getAll();
}

// ── Orders: lịch sử đơn hàng ─────────────────────────────────

// GET /api/orders/history
elseif ($request === "/api/orders/history" && $method === "GET") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->getHistory();
}

// GET /api/orders/{orderId}       VD: /api/orders/OPT-2024-0892
elseif (preg_match('#^/api/orders/([^/]+)$#', $request, $m) && $method === "GET") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->getDetail($m[1]);
}

// PUT /api/orders/{orderId}/cancel
elseif (preg_match('#^/api/orders/([^/]+)/cancel$#', $request, $m) && $method === "PUT") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->cancelOrder($m[1]);
}

// POST /api/orders/{orderId}/reorder
elseif (preg_match('#^/api/orders/([^/]+)/reorder$#', $request, $m) && $method === "POST") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->reorder($m[1]);
}

// ── 404 ──────────────────────────────────────────────────────
else {
    http_response_code(404);
    echo json_encode([
        "status"  => "error",
        "message" => "Route not found",
        "request" => $request
    ]);
}
