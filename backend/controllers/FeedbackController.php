<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Models/Feedback.php';

class FeedbackController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    // ── GET /api/products/{productId}/feedbacks ──────────────────
    // Public: lấy danh sách feedback + rating summary của 1 sản phẩm
    public function getByProduct(string $productId): void
    {
        $pid    = (int) $productId;
        $limit  = min(20, max(1, (int) ($_GET['size'] ?? 10)));
        $offset = max(0, (int) ($_GET['page'] ?? 0)) * $limit;

        $model   = new Feedback($this->db);
        $items   = $model->getByProduct($pid, $limit, $offset);
        $summary = $model->getRatingSummary($pid);

        $this->json([
            'summary'  => [
                'total'   => (int)   ($summary['total']   ?? 0),
                'average' => (float) ($summary['average'] ?? 0),
                'stars'   => [
                    5 => (int) ($summary['five']  ?? 0),
                    4 => (int) ($summary['four']  ?? 0),
                    3 => (int) ($summary['three'] ?? 0),
                    2 => (int) ($summary['two']   ?? 0),
                    1 => (int) ($summary['one']   ?? 0),
                ],
            ],
            'reviews' => $items,
        ]);
    }

    // ── POST /api/feedbacks ──────────────────────────────────────
    // Tạo feedback mới (user đã đăng nhập)
    public function create(): void
    {
        $userId = $this->requireAuth();
        $data   = $this->parseBody();

        $productId = (int) ($data->product_id ?? 0);
        $orderId   = (int) ($data->order_id   ?? 0);
        $rating    = (int) ($data->rating     ?? 0);
        $comment   = trim((string) ($data->comment ?? ''));

        if ($productId <= 0 || $orderId <= 0) {
            $this->json(['error' => 'Thiếu product_id hoặc order_id.'], 400);
            return;
        }

        if ($rating < 1 || $rating > 5) {
            $this->json(['error' => 'Đánh giá phải từ 1 đến 5 sao.'], 422);
            return;
        }

        if (mb_strlen($comment) < 5) {
            $this->json(['error' => 'Nhận xét phải có ít nhất 5 ký tự.'], 422);
            return;
        }

        $model             = new Feedback($this->db);
        $model->user_id    = $userId;
        $model->product_id = $productId;
        $model->order_id   = $orderId;
        $model->rating     = $rating;
        $model->comment    = $comment;

        if (!$model->create()) {
            $this->json([
                'error' => 'Không thể gửi đánh giá. Bạn chưa mua sản phẩm này, '
                         . 'đơn hàng chưa hoàn thành, hoặc đã đánh giá rồi.',
            ], 422);
            return;
        }

        $this->json([
            'message'     => 'Đánh giá của bạn đã được gửi và đang chờ duyệt.',
            'feedback_id' => $model->feedback_id,
        ], 201);
    }

    // ── PUT /api/feedbacks/{feedbackId} ──────────────────────────
    // User sửa feedback của mình
    public function update(string $feedbackId): void
    {
        $userId = $this->requireAuth();
        $data   = $this->parseBody();

        $rating  = (int) ($data->rating  ?? 0);
        $comment = trim((string) ($data->comment ?? ''));

        if ($rating < 1 || $rating > 5) {
            $this->json(['error' => 'Đánh giá phải từ 1 đến 5 sao.'], 422);
            return;
        }

        if (mb_strlen($comment) < 5) {
            $this->json(['error' => 'Nhận xét phải có ít nhất 5 ký tự.'], 422);
            return;
        }

        $model              = new Feedback($this->db);
        $model->feedback_id = (int) $feedbackId;
        $model->user_id     = $userId;
        $model->rating      = $rating;
        $model->comment     = $comment;

        if (!$model->update()) {
            $this->json(['error' => 'Không thể cập nhật đánh giá.'], 404);
            return;
        }

        $this->json(['message' => 'Đánh giá đã được cập nhật, đang chờ duyệt lại.']);
    }

    // ── DELETE /api/feedbacks/{feedbackId} ───────────────────────
    // User xóa feedback của mình
    public function delete(string $feedbackId): void
    {
        $userId = $this->requireAuth();

        $model              = new Feedback($this->db);
        $model->feedback_id = (int) $feedbackId;
        $model->user_id     = $userId;

        if (!$model->delete()) {
            $this->json(['error' => 'Không tìm thấy đánh giá hoặc bạn không có quyền xóa.'], 404);
            return;
        }

        $this->json(['message' => 'Đánh giá đã được xóa.']);
    }

    // ── GET /api/feedbacks/me ────────────────────────────────────
    // Lấy tất cả feedbacks của user hiện tại
    public function getMyFeedbacks(): void
    {
        $userId = $this->requireAuth();
        $limit  = min(20, max(1, (int) ($_GET['size'] ?? 10)));
        $offset = max(0, (int) ($_GET['page'] ?? 0)) * $limit;

        $model = new Feedback($this->db);
        $items = $model->getByUser($userId, $limit, $offset);

        $this->json(['reviews' => $items]);
    }

    // ── PUT /api/admin/feedbacks/{feedbackId}/status ─────────────
    // Admin duyệt / từ chối feedback
    public function updateStatus(string $feedbackId): void
    {
        $this->requireAdmin();
        $data   = $this->parseBody();
        $status = strtoupper(trim((string) ($data->status ?? '')));

        $model              = new Feedback($this->db);
        $model->feedback_id = (int) $feedbackId;
        $model->status      = $status;

        if (!$model->updateStatus()) {
            $this->json(['error' => 'Trạng thái không hợp lệ hoặc không tìm thấy đánh giá.'], 422);
            return;
        }

        $this->json(['message' => "Đánh giá đã được cập nhật thành {$status}."]);
    }

    // ── HELPERS ──────────────────────────────────────────────────

    private function requireAuth(): int
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['user_id'])) return (int) $_SESSION['user_id'];
        $this->json(['error' => 'Bạn cần đăng nhập.'], 401);
        exit;
    }

    private function requireAdmin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') return;
        $this->json(['error' => 'Không có quyền truy cập.'], 403);
        exit;
    }

    private function parseBody(): object
    {
        return json_decode(file_get_contents('php://input')) ?? new stdClass();
    }

    private function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
