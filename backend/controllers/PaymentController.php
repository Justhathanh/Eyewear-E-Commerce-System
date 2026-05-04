<?php
// backend/controllers/PaymentController.php
require_once __DIR__ . "/../config/database.php";

class PaymentController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // POST /api/payments
    // Body: { orderId, method }
    public function create(): void
    {
        $userId = $this->requireAuth();
        $data   = json_decode(file_get_contents("php://input"), true);

        $orderId = (int)($data['orderId'] ?? 0);
        $method  = strtoupper(trim($data['method'] ?? ''));

        if (!$orderId || !in_array($method, ['CASH', 'BANK', 'MOMO'], true)) {
            $this->json(['error' => 'Dữ liệu không hợp lệ.'], 400);
        }

        // Kiểm tra order thuộc user này
        $stmt = $this->db->prepare(
            "SELECT order_id, total_price FROM orders WHERE order_id = :oid AND user_id = :uid LIMIT 1"
        );
        $stmt->execute([':oid' => $orderId, ':uid' => $userId]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->json(['error' => 'Đơn hàng không tồn tại.'], 404);
        }

        // Upsert: nếu đã có payment thì update, chưa có thì insert
        $check = $this->db->prepare("SELECT payment_id FROM payments WHERE order_id = :oid LIMIT 1");
        $check->execute([':oid' => $orderId]);
        $existing = $check->fetch();

        if ($existing) {
            $this->db->prepare(
                "UPDATE payments SET method = :method, status = 'PENDING' WHERE order_id = :oid"
            )->execute([':method' => $method, ':oid' => $orderId]);
        } else {
            $this->db->prepare(
                "INSERT INTO payments (order_id, amount, method, status) VALUES (:oid, :amount, :method, 'PENDING')"
            )->execute([':oid' => $orderId, ':amount' => $order['total_price'], ':method' => $method]);
        }

        // Nếu COD → tự động chuyển sang CONFIRMED
        if ($method === 'CASH') {
            $this->db->prepare(
                "UPDATE payments SET status = 'PAID' WHERE order_id = :oid"
            )->execute([':oid' => $orderId]);

            $this->db->prepare(
                "UPDATE orders SET status = 'CONFIRMED' WHERE order_id = :oid"
            )->execute([':oid' => $orderId]);
        }

        $this->json(['message' => 'Ghi nhận thanh toán thành công.', 'method' => $method]);
    }

    private function requireAuth(): int
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
        $this->json(['error' => 'Bạn cần đăng nhập.'], 401);
    }

    private function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}