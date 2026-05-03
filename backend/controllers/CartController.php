<?php
require_once __DIR__ . "/../config/database.php";

class CartController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // GET /api/cart
    public function getCart(): void {
        $userId = $this->requireAuth();

        $stmt = $this->db->prepare("
            SELECT c.id, c.quantity, p.product_id, p.name, p.price, p.image, p.stock
            FROM cart c
            JOIN products p ON p.product_id = c.product_id
            WHERE c.user_id = :userId
            ORDER BY c.added_at DESC
        ");
        $stmt->execute([':userId' => $userId]);
        $items = $stmt->fetchAll();

        $total = array_reduce($items, fn($sum, $i) => $sum + ($i['price'] * $i['quantity']), 0);

        $this->json(['items' => $items, 'total' => $total]);
    }
    public function count(): void {
        $userId = $this->requireAuth();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM cart WHERE user_id = :userId");
        $stmt->execute([':userId' => $userId]);
        $count = (int)$stmt->fetchColumn();

        $this->json(['count' => $count]);
    }

    // POST /api/cart
    public function add(): void {
        $userId = $this->requireAuth();
        $data   = json_decode(file_get_contents("php://input"), true);

        $productId = (int)($data['product_id'] ?? 0);
        $quantity  = max(1, (int)($data['quantity'] ?? 1));

        if (!$productId) {
            $this->json(['error' => 'Thiếu product_id'], 400);
        }

        // Kiểm tra sản phẩm tồn tại
        $stmt = $this->db->prepare("SELECT stock FROM products WHERE product_id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            $this->json(['error' => 'Sản phẩm không tồn tại'], 404);
        }

        if ($product['stock'] < $quantity) {
            $this->json(['error' => 'Không đủ hàng trong kho'], 422);
        }

        $stmt = $this->db->prepare("
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (:userId, :productId, :qty)
            ON DUPLICATE KEY UPDATE quantity = quantity + :qty
        ");
        $stmt->execute([
            ':userId'    => $userId,
            ':productId' => $productId,
            ':qty'       => $quantity,
        ]);

        $this->json(['message' => 'Đã thêm vào giỏ hàng']);
    }

    // PUT /api/cart/{id}
    public function update(int $cartId): void {
        $userId   = $this->requireAuth();
        $data     = json_decode(file_get_contents("php://input"), true);
        $quantity = max(1, (int)($data['quantity'] ?? 1));

        $stmt = $this->db->prepare("
            UPDATE cart SET quantity = :qty
            WHERE id = :id AND user_id = :userId
        ");
        $stmt->execute([':qty' => $quantity, ':id' => $cartId, ':userId' => $userId]);

        if ($stmt->rowCount() === 0) {
            $this->json(['error' => 'Không tìm thấy sản phẩm trong giỏ'], 404);
        }

        $this->json(['message' => 'Đã cập nhật số lượng']);
    }

    // DELETE /api/cart/{id}
    public function remove(int $cartId): void {
        $userId = $this->requireAuth();

        $stmt = $this->db->prepare("DELETE FROM cart WHERE id = :id AND user_id = :userId");
        $stmt->execute([':id' => $cartId, ':userId' => $userId]);

        if ($stmt->rowCount() === 0) {
            $this->json(['error' => 'Không tìm thấy sản phẩm trong giỏ'], 404);
        }

        $this->json(['message' => 'Đã xóa khỏi giỏ hàng']);
    }

    // DELETE /api/cart
    public function clear(): void {
        $userId = $this->requireAuth();

        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = :userId");
        $stmt->execute([':userId' => $userId]);

        $this->json(['message' => 'Đã xóa toàn bộ giỏ hàng']);
    }

    // ── Helpers ──────────────────────────────────────────────

    private function requireAuth(): int {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!empty($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        $this->json(['error' => 'Bạn cần đăng nhập'], 401);
        exit;
    }

    private function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}