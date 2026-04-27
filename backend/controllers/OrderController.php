<?php
require_once __DIR__ . "/../config/database.php";

class OrderController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getConnection();
    }
    // =========================================================
    // POST /api/orders
    // =========================================================
    public function create(): void
    {
        $userId = $this->requireAuth();

        $data = json_decode(file_get_contents("php://input"), true);

        if (
            !$data ||
            !isset($data['items']) ||
            !is_array($data['items']) ||
            count($data['items']) === 0
        ) {
            $this->json(['error' => 'Dữ liệu không hợp lệ'], 400);
            return;
        }

        $items = $data['items'];

        try {
            $this->db->beginTransaction();

            $total = 0;
            $productPrices = [];

            // 👉 lấy giá + tính tổng
            foreach ($items as $item) {
                $stmt = $this->db->prepare("SELECT price FROM products WHERE id = :id");
                $stmt->execute([':id' => $item['productId']]);

                $price = $stmt->fetchColumn();

                if ($price === false) {
                    throw new Exception("Sản phẩm không tồn tại");
                }

                $productPrices[$item['productId']] = $price;
                $total += $price * $item['quantity'];
            }

            // 👉 tạo order
            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, total_price, status, created_at)
                VALUES (:userId, :total, 'PENDING', NOW())
            ");
            $stmt->execute([
                ':userId' => $userId,
                ':total'  => $total
            ]);

            $orderId = $this->db->lastInsertId();

            // 👉 tạo order_items
            $itemStmt = $this->db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (:orderId, :productId, :quantity, :price)
            ");

            foreach ($items as $item) {
                $itemStmt->execute([
                    ':orderId'   => $orderId,
                    ':productId' => $item['productId'],
                    ':quantity'  => $item['quantity'],
                    ':price'     => $productPrices[$item['productId']]
                ]);
            }

            $this->db->commit();

            $this->json([
                'message' => 'Đặt hàng thành công',
                'orderId' => $orderId
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();

            $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // =========================================================
    // GET /api/orders/history
    // =========================================================
    public function getHistory(): void
    {
        $userId = $this->requireAuth();

        $page   = max(0, (int)($_GET['page'] ?? 0));
        $size   = min(50, max(1, (int)($_GET['size'] ?? 5)));
        $status = $this->sanitizeStatus($_GET['status'] ?? '');
        $sort   = $this->sanitizeSort($_GET['sort'] ?? 'newest');
        $offset = $page * $size;

        $orderBy = match ($sort) {
            'oldest'  => 'o.created_at ASC',
            'highest' => 'o.total_price DESC',
            'lowest'  => 'o.total_price ASC',
            default   => 'o.created_at DESC',
        };

        $whereParts = ['o.user_id = :userId'];
        $params     = [':userId' => $userId];

        if ($status !== '') {
            $whereParts[]      = 'o.status = :status';
            $params[':status'] = $status;
        }

        $where = implode(' AND ', $whereParts);

        // Đếm tổng
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM orders o WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Lấy danh sách đơn
        $sql = "
            SELECT
                o.order_id   AS orderId,
                o.user_id    AS userId,
                o.total_price AS total,
                o.status,
                o.created_at AS createdAt
            FROM orders o
            WHERE $where
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $size,   PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gắn items vào từng đơn
        $orderIds = array_column($orders, 'orderId');
        $itemsMap = $this->fetchItemsByOrderIds($orderIds);

        foreach ($orders as &$order) {
            $order['items'] = $itemsMap[$order['orderId']] ?? [];
            $order['total'] = (float)$order['total'];
        }

        $this->json([
            'content'       => $orders,
            'totalElements' => $total,
            'totalPages'    => (int)ceil($total / $size),
            'number'        => $page,
            'size'          => $size,
        ]);
    }

    // =========================================================
    // GET /api/orders/{orderId}
    // =========================================================
    public function getDetail(string $orderId): void
    {
        $userId = $this->requireAuth();
        $order  = $this->findOrderOrFail($userId, (int)$orderId);
        $order['items'] = $this->fetchItemsByOrderIds([$order['orderId']])[$order['orderId']] ?? [];
        $this->json($order);
    }

    // =========================================================
    // PUT /api/orders/{orderId}/cancel
    // =========================================================
    public function cancelOrder(string $orderId): void
    {
        $userId = $this->requireAuth();
        $order  = $this->findOrderOrFail($userId, (int)$orderId);

        if (!in_array($order['status'], ['PENDING', 'CONFIRMED'])) {
            $this->json(['error' => 'Chỉ có thể huỷ đơn hàng đang chờ xử lý hoặc đã xác nhận.'], 422);
            return;
        }

        $stmt = $this->db->prepare(
            "UPDATE orders SET status = 'CANCELLED' WHERE order_id = :id"
        );
        $stmt->execute([':id' => $order['orderId']]);

        $this->json(['message' => 'Đơn hàng đã được huỷ thành công.']);
    }

    // =========================================================
    // POST /api/orders/{orderId}/reorder
    // =========================================================
    public function reorder(string $orderId): void
    {
        $userId = $this->requireAuth();
        $order  = $this->findOrderOrFail($userId, (int)$orderId);
        $items  = $this->fetchItemsByOrderIds([$order['orderId']])[$order['orderId']] ?? [];

        if (empty($items)) {
            $this->json(['error' => 'Không tìm thấy sản phẩm trong đơn hàng.'], 404);
            return;
        }

        // Thêm lại vào cart (điều chỉnh nếu project có bảng cart khác)
        $insertStmt = $this->db->prepare("
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (:userId, :productId, :qty)
            ON DUPLICATE KEY UPDATE quantity = quantity + :qty
        ");

        foreach ($items as $item) {
            $insertStmt->execute([
                ':userId'    => $userId,
                ':productId' => $item['productId'],
                ':qty'       => $item['quantity'],
            ]);
        }

        $this->json(['message' => 'Đã thêm sản phẩm vào giỏ hàng.']);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function fetchItemsByOrderIds(array $orderIds): array
    {
        if (empty($orderIds)) return [];

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        $stmt = $this->db->prepare("
            SELECT
                oi.order_id   AS orderId,
                oi.product_id AS productId,
                p.name,
                oi.quantity,
                oi.price
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.id ASC
        ");
        $stmt->execute(array_values($orderIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $oid = $row['orderId'];
            unset($row['orderId']);
            $row['price']    = (float)$row['price'];
            $row['quantity'] = (int)$row['quantity'];
            $map[$oid][]     = $row;
        }
        return $map;
    }

    private function findOrderOrFail(int $userId, int $orderId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                order_id    AS orderId,
                user_id     AS userId,
                total_price AS total,
                status,
                created_at  AS createdAt
            FROM orders
            WHERE order_id = :orderId AND user_id = :userId
            LIMIT 1
        ");
        $stmt->execute([':orderId' => $orderId, ':userId' => $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $this->json(['error' => 'Đơn hàng không tồn tại.'], 404);
            exit;
        }
        return $order;
    }

    private function requireAuth(): int
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!empty($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        $this->json(['error' => 'Bạn cần đăng nhập.'], 401);
        exit;
    }

    private function sanitizeStatus(string $s): string
    {
        $valid = ['PENDING', 'CONFIRMED', 'SHIPPED', 'COMPLETED', 'CANCELLED', ''];
        return in_array(strtoupper($s), $valid, true) ? strtoupper($s) : '';
    }

    private function sanitizeSort(string $s): string
    {
        return in_array($s, ['newest', 'oldest', 'highest', 'lowest'], true) ? $s : 'newest';
    }

    private function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
