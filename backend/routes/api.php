<?php
$request = explode("?", $_SERVER['REQUEST_URI'])[0];
$request = str_replace("/index.php", "", $request);
// Docker mount backend tại /var/www/html/api → strip prefix
$request = preg_replace('#^/api#', '', $request) ?: '/';
$method  = $_SERVER['REQUEST_METHOD'];

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($method === 'OPTIONS') { http_response_code(204); exit; }

// ── Products ─────────────────────────────────────────────────
if ($request === "/products" && $method === "GET") {
    require_once __DIR__ . "/../controllers/ProductController.php";
    (new ProductController())->getAll();
}

// ── Cart ─────────────────────────────────────────────────────
elseif ($request === "/cart" && $method === "GET") {
    require_once __DIR__ . "/../controllers/CartController.php";
    (new CartController())->getCart();
}
elseif ($request === "/cart" && $method === "POST") {
    require_once __DIR__ . "/../controllers/CartController.php";
    (new CartController())->add();
}
elseif ($request === "/cart" && $method === "DELETE") {
    require_once __DIR__ . "/../controllers/CartController.php";
    (new CartController())->clear();
}
elseif (preg_match('#^/cart/(\d+)$#', $request, $m) && $method === "PUT") {
    require_once __DIR__ . "/../controllers/CartController.php";
    (new CartController())->update((int)$m[1]);
}
elseif (preg_match('#^/cart/(\d+)$#', $request, $m) && $method === "DELETE") {
    require_once __DIR__ . "/../controllers/CartController.php";
    (new CartController())->remove((int)$m[1]);
}

// ── Orders ───────────────────────────────────────────────────
elseif ($request === "/orders/history" && $method === "GET") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->getHistory();
}
elseif ($request === "/orders" && $method === "POST") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->create();
}
elseif (preg_match('#^/orders/([^/]+)/cancel$#', $request, $m) && $method === "PUT") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->cancelOrder($m[1]);
}
elseif (preg_match('#^/orders/([^/]+)/reorder$#', $request, $m) && $method === "POST") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->reorder($m[1]);
}
elseif (preg_match('#^/orders/([^/]+)$#', $request, $m) && $method === "GET") {
    require_once __DIR__ . "/../controllers/OrderController.php";
    (new OrderController())->getDetail($m[1]);
}

// ── 404 ──────────────────────────────────────────────────────
else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Route not found', 'request' => $request]);
}