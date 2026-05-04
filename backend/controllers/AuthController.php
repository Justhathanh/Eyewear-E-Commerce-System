<?php
// backend/controllers/AuthController.php
require_once __DIR__ . "/../config/database.php";

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function register(): void
    {
        $data     = json_decode(file_get_contents("php://input"), true);
        $name     = trim($data['name']     ?? '');
        $email    = trim($data['email']    ?? '');
        $password =      $data['password'] ?? '';

        if (!$name || !$email || !$password)
            $this->json(['error' => 'Vui lòng điền đầy đủ thông tin.'], 400);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $this->json(['error' => 'Email không hợp lệ.'], 400);
        if (strlen($password) < 6)
            $this->json(['error' => 'Mật khẩu phải có ít nhất 6 ký tự.'], 400);

        $check = $this->db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) $this->json(['error' => 'Email đã được sử dụng.'], 409);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'CUSTOMER')");
        $stmt->execute([$name, $email, $hash]);
        $userId = (int)$this->db->lastInsertId();

        $this->startSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['name']    = $name;
        $_SESSION['role']    = 'CUSTOMER';

        $this->json(['message' => 'Đăng ký thành công.',
            'user' => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'CUSTOMER']], 201);
    }

    public function login(): void
    {
        $data     = json_decode(file_get_contents("php://input"), true);
        $email    = trim($data['email']    ?? '');
        $password =      $data['password'] ?? '';

        if (!$email || !$password)
            $this->json(['error' => 'Vui lòng nhập email và mật khẩu.'], 400);

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password']))
            $this->json(['error' => 'Email hoặc mật khẩu không đúng.'], 401);

        $this->startSession();
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];

        $this->json(['message' => 'Đăng nhập thành công.',
            'user' => ['id' => (int)$user['user_id'], 'name' => $user['name'],
                       'email' => $user['email'], 'role' => $user['role']]]);
    }

    public function logout(): void
    {
        $this->startSession();
        session_destroy();
        $this->json(['message' => 'Đã đăng xuất.']);
    }

    public function me(): void
    {
        $this->startSession();
        if (empty($_SESSION['user_id']))
            $this->json(['error' => 'Chưa đăng nhập.'], 401);

        $stmt = $this->db->prepare(
            "SELECT user_id AS id, name, email, role, created_at FROM users WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) { session_destroy(); $this->json(['error' => 'Tài khoản không tồn tại.'], 404); }
        $this->json(['user' => $user]);
    }

    // ── Fix: set cookie path = '/' ────────────────────────────
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => false,
                'httponly'  => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    private function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}