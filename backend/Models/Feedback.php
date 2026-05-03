<?php

class Feedback
{
    private PDO    $conn;
    private string $table = "feedbacks";

    public ?int    $feedback_id = null;
    public ?int    $user_id     = null;
    public ?int    $product_id  = null;
    public ?int    $order_id    = null;          // thêm để khớp với DB
    public int     $rating      = 5;             // 1–5
    public string  $comment     = '';
    public string  $status      = 'PENDING';     // PENDING | APPROVED | REJECTED

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ── CREATE ───────────────────────────────────────────────────
    // Tự kiểm tra: đúng order, đơn COMPLETED, chưa review → mới INSERT
    public function create(): bool
    {
        // Xác nhận order thuộc về user, có chứa product, và đã COMPLETED
        $stmtCheck = $this->conn->prepare(
            "SELECT 1 FROM orders o
             JOIN order_items oi ON o.order_id = oi.order_id
             WHERE o.order_id    = ?
               AND o.user_id     = ?
               AND oi.product_id = ?
               AND o.status      = 'COMPLETED'
             LIMIT 1"
        );
        $stmtCheck->execute([$this->order_id, $this->user_id, $this->product_id]);

        if (!$stmtCheck->fetchColumn()) {
            return false; // chưa mua, đơn chưa hoàn thành, hoặc order không hợp lệ
        }

        // INSERT (UNIQUE key trong DB sẽ chặn nếu đã review rồi)
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table}
                (user_id, product_id, order_id, rating, comment, status)
             VALUES (?, ?, ?, ?, ?, 'PENDING')"
        );

        $result = $stmt->execute([
            $this->user_id,
            $this->product_id,
            $this->order_id,
            $this->rating,
            $this->comment,
        ]);

        if ($result) {
            $this->feedback_id = (int) $this->conn->lastInsertId();
        }

        return $result;
    }

    // ── UPDATE (user sửa nội dung, tự reset về PENDING để duyệt lại) ──
    public function update(): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET rating  = ?,
                 comment = ?,
                 status  = 'PENDING'
             WHERE feedback_id = ? AND user_id = ?"
        );
        return $stmt->execute([
            $this->rating,
            $this->comment,
            $this->feedback_id,
            $this->user_id,
        ]);
    }

    // ── UPDATE STATUS (admin duyệt / từ chối) ────────────────────
    public function updateStatus(): bool
    {
        $valid = ['PENDING', 'APPROVED', 'REJECTED'];

        if (!in_array($this->status, $valid, true)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET status = ?
             WHERE feedback_id = ?"
        );
        return $stmt->execute([$this->status, $this->feedback_id]);
    }

    // ── DELETE ────────────────────────────────────────────────────
    // user_id set → user chỉ xóa của mình | user_id null → admin xóa bất kỳ
    public function delete(): bool
    {
        if ($this->user_id !== null) {
            $stmt = $this->conn->prepare(
                "DELETE FROM {$this->table}
                 WHERE feedback_id = ? AND user_id = ?"
            );
            return $stmt->execute([$this->feedback_id, $this->user_id]);
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE feedback_id = ?"
        );
        return $stmt->execute([$this->feedback_id]);
    }

    // ── GET BY PRODUCT (chỉ hiện APPROVED, có pagination) ────────
    public function getByProduct(int $productId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->conn->prepare(
            "SELECT f.feedback_id,
                    f.user_id,
                    u.name  AS user_name,
                    f.rating,
                    f.comment,
                    f.created_at
             FROM {$this->table} f
             JOIN users u ON f.user_id = u.user_id
             WHERE f.product_id = :productId
               AND f.status     = 'APPROVED'
             ORDER BY f.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':productId', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',     $limit,     PDO::PARAM_INT);
        $stmt->bindValue(':offset',    $offset,    PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ── GET RATING SUMMARY ────────────────────────────────────────
    public function getRatingSummary(int $productId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT
                COUNT(*)                          AS total,
                ROUND(AVG(rating), 1)             AS average,
                SUM(rating = 5)                   AS five,
                SUM(rating = 4)                   AS four,
                SUM(rating = 3)                   AS three,
                SUM(rating = 2)                   AS two,
                SUM(rating = 1)                   AS one
             FROM {$this->table}
             WHERE product_id = ? AND status = 'APPROVED'"
        );
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: [];
    }

    // ── GET BY USER (lịch sử review của user, có pagination) ─────
    public function getByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->conn->prepare(
            "SELECT f.feedback_id,
                    f.product_id,
                    p.name  AS product_name,
                    f.rating,
                    f.comment,
                    f.status,
                    f.created_at
             FROM {$this->table} f
             JOIN products p ON f.product_id = p.product_id
             WHERE f.user_id = :userId
             ORDER BY f.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
