<?php
session_start();
require_once 'db.php'; // $conn (mysqli) của nhóm

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;

// --- Lấy product_id từ URL ---
$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
if ($productId <= 0) {
    header('Location: home.php');
    exit();
}

// --- Lấy thông tin sản phẩm ---
$stmt = $conn->prepare('SELECT product_id, name, description, price FROM products WHERE product_id = ?');
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: home.php');
    exit();
}

// --- Xử lý gửi feedback (POST) ---
$successMsg = '';
$errorMsg   = '';

if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = (int)  ($_POST['rating']  ?? 0);
    $orderId = (int)  ($_POST['order_id'] ?? 0);
    $comment = trim(  $_POST['comment']  ?? '');

    if ($rating < 1 || $rating > 5) {
        $errorMsg = 'Vui lòng chọn số sao từ 1 đến 5.';
    } elseif ($orderId <= 0) {
        $errorMsg = 'Vui lòng chọn đơn hàng muốn đánh giá.';
    } else {
        // Kiểm tra order hợp lệ: thuộc user, có product này, đã COMPLETED
        $stmtBuy = $conn->prepare(
            "SELECT 1 FROM orders o
             JOIN order_items oi ON o.order_id = oi.order_id
             WHERE o.order_id    = ?
               AND o.user_id     = ?
               AND oi.product_id = ?
               AND o.status      = 'COMPLETED'
             LIMIT 1"
        );
        $stmtBuy->bind_param('iii', $orderId, $userId, $productId);
        $stmtBuy->execute();
        $hasBought = (bool) $stmtBuy->get_result()->fetch_row();
        $stmtBuy->close();

        if (!$hasBought) {
            $errorMsg = 'Đơn hàng không hợp lệ hoặc chưa hoàn thành.';
        } else {
            // Kiểm tra đã review đơn này chưa
            $stmtCheck = $conn->prepare(
                'SELECT 1 FROM feedbacks WHERE user_id = ? AND product_id = ? AND order_id = ? LIMIT 1'
            );
            $stmtCheck->bind_param('iii', $userId, $productId, $orderId);
            $stmtCheck->execute();
            $alreadyDone = (bool) $stmtCheck->get_result()->fetch_row();
            $stmtCheck->close();

            if ($alreadyDone) {
                $errorMsg = 'Bạn đã đánh giá sản phẩm này trong đơn hàng đó rồi.';
            } else {
                $stmtInsert = $conn->prepare(
                    'INSERT INTO feedbacks (user_id, product_id, order_id, rating, comment, status)
                     VALUES (?, ?, ?, ?, ?, "PENDING")'
                );
                $stmtInsert->bind_param('iiiis', $userId, $productId, $orderId, $rating, $comment);
                if ($stmtInsert->execute()) {
                    $successMsg = 'Cảm ơn bạn đã đánh giá! Đánh giá sẽ hiển thị sau khi được duyệt.';
                } else {
                    $errorMsg = 'Có lỗi xảy ra, vui lòng thử lại.';
                }
                $stmtInsert->close();
            }
        }
    }
}

// --- Lấy danh sách đơn hàng COMPLETED của user có chứa sản phẩm này (cho dropdown) ---
$completedOrders = [];
if ($isLoggedIn) {
    $stmtOrders = $conn->prepare(
        "SELECT o.order_id, o.created_at FROM orders o
         JOIN order_items oi ON o.order_id = oi.order_id
         WHERE o.user_id     = ?
           AND oi.product_id = ?
           AND o.status      = 'COMPLETED'
         ORDER BY o.created_at DESC"
    );
    $stmtOrders->bind_param('ii', $userId, $productId);
    $stmtOrders->execute();
    $completedOrders = $stmtOrders->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtOrders->close();
}

// --- Lấy danh sách feedback đã APPROVED ---
$stmtFb = $conn->prepare(
    "SELECT f.feedback_id, f.rating, f.comment, f.created_at, u.name AS user_name
     FROM feedbacks f
     JOIN users u ON f.user_id = u.user_id
     WHERE f.product_id = ? AND f.status = 'APPROVED'
     ORDER BY f.created_at DESC
     LIMIT 50"
);
$stmtFb->bind_param('i', $productId);
$stmtFb->execute();
$feedbacks = $stmtFb->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtFb->close();

// --- Rating trung bình ---
$stmtAvg = $conn->prepare(
    'SELECT ROUND(AVG(rating), 1), COUNT(*) FROM feedbacks WHERE product_id = ? AND status = "APPROVED"'
);
$stmtAvg->bind_param('i', $productId);
$stmtAvg->execute();
[$avgRating, $totalReviews] = $stmtAvg->get_result()->fetch_row();
$stmtAvg->close();
$avgRating    = (float) ($avgRating ?? 0);
$totalReviews = (int)   ($totalReviews ?? 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá - <?= htmlspecialchars($product['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assest/css/style.css">
    <style>
        .feedback-page {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px 60px;
        }
        .product-card {
            background: #F4F7F8;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 30px;
        }
        .product-card h2 { margin: 0 0 6px; font-size: 20px; }
        .product-card p  { margin: 0; color: #666; font-size: 14px; }
        .product-price   { font-size: 18px; font-weight: bold; color: #F86338; margin-top: 8px; }

        .rating-summary {
            display: flex;
            align-items: center;
            gap: 24px;
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 28px;
        }
        .rating-big { text-align: center; min-width: 90px; }
        .rating-big .score { font-size: 48px; font-weight: bold; color: #F86338; line-height: 1; }
        .rating-big .stars { color: #f5a623; font-size: 18px; margin: 6px 0; }
        .rating-big .count { font-size: 13px; color: #888; }

        .review-form {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
        }
        .review-form h3 { margin: 0 0 18px; font-size: 17px; }

        .star-picker { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; margin-bottom: 16px; }
        .star-picker input { display: none; }
        .star-picker label { font-size: 32px; color: #ccc; cursor: pointer; transition: color 0.15s; }
        .star-picker input:checked ~ label,
        .star-picker label:hover,
        .star-picker label:hover ~ label { color: #f5a623; }

        .review-form select,
        .review-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
            margin-bottom: 14px;
        }
        .review-form textarea { resize: vertical; min-height: 100px; }
        .review-form select:focus,
        .review-form textarea:focus { border-color: #512da8; }

        .submit-btn {
            background: #512da8;
            color: #fff;
            padding: 10px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.2s;
        }
        .submit-btn:hover { background: #3e1f8a; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #e6f9ee; color: #1a7a3e; border: 1px solid #b2dfc5; }
        .alert-error   { background: #fdecea; color: #b71c1c; border: 1px solid #f5c6c3; }

        .login-notice {
            background: #f0eeff;
            border: 1px solid #c9bff0;
            border-radius: 8px;
            padding: 14px 18px;
            text-align: center;
            font-size: 14px;
            color: #512da8;
            margin-bottom: 30px;
        }
        .login-notice a { color: #512da8; font-weight: bold; border-bottom: 1px solid #512da8; }

        .reviews-section h3 { font-size: 17px; margin-bottom: 16px; }
        .review-item { border-bottom: 1px solid #f0f0f0; padding: 16px 0; }
        .review-item:last-child { border-bottom: none; }
        .review-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .reviewer-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: #512da8; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 15px; flex-shrink: 0;
        }
        .reviewer-info .name { font-weight: bold; font-size: 14px; }
        .reviewer-info .date { font-size: 12px; color: #aaa; }
        .review-stars   { color: #f5a623; font-size: 14px; margin-bottom: 6px; }
        .review-comment { font-size: 14px; color: #444; line-height: 1.6; }
        .no-reviews     { text-align: center; padding: 40px 0; color: #aaa; font-size: 15px; }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #512da8; font-size: 14px; margin-bottom: 20px; text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }

        label.field-label { font-size: 14px; font-weight: bold; display: block; margin-bottom: 6px; }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <div class="header">
        <a href="home.php">
            <img src="../assest/img/logo.png" alt="Logo" class="logo">
        </a>
        <div class="right">
            <div class="call"><i class="fa-solid fa-phone"></i> <span>Call Center</span></div>
            <div class="ship"><i class="fa-solid fa-truck"></i> <span>Free Shipping</span></div>
        </div>
    </div>
    <div class="menu">
        <ul>
            <li><a href="#">Shop</a></li>
            <li><a href="#">Promo</a></li>
            <li><a href="#">About</a></li>
            <li><a href="#">Blog</a></li>
        </ul>
        <div class="search">
            <input type="text" placeholder="Search what you need">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <div class="icon">
            <a href="#"><i class="fa-regular fa-heart"></i></a>
            <a href="#"><i class="fa-solid fa-cart-arrow-down"></i></a>
            <div class="user-menu">
                <?php if ($isLoggedIn): ?>
                    <i class="fa-regular fa-user"></i>
                    <div class="dropdown">
                        <p>Xin chào, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></p>
                        <a href="logout.php">Đăng xuất</a>
                    </div>
                <?php else: ?>
                    <a href="home.php" style="color:inherit;">
                        <i class="fa-regular fa-user"></i>
                    </a>
                <?php endif; ?>
            </div>
            <a href="#"><i class="fa-regular fa-bell"></i></a>
        </div>
    </div>
</header>

<!-- NỘI DUNG -->
<div class="feedback-page">
    <a href="home.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Về trang chủ
    </a>

    <!-- Thông tin sản phẩm -->
    <div class="product-card">
        <h2><?= htmlspecialchars($product['name']) ?></h2>
        <p><?= htmlspecialchars($product['description'] ?? '') ?></p>
        <div class="product-price">
            <?= number_format($product['price'], 0, ',', '.') ?>đ
        </div>
    </div>

    <!-- Rating tổng quan -->
    <div class="rating-summary">
        <div class="rating-big">
            <div class="score"><?= $avgRating > 0 ? $avgRating : '—' ?></div>
            <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-<?= $i <= round($avgRating) ? 'solid' : 'regular' ?> fa-star"></i>
                <?php endfor; ?>
            </div>
            <div class="count"><?= $totalReviews ?> đánh giá</div>
        </div>
    </div>

    <!-- Form gửi đánh giá -->
    <?php if ($isLoggedIn): ?>
        <?php if (!empty($completedOrders)): ?>
            <div class="review-form">
                <h3><i class="fa-regular fa-pen-to-square"></i> Viết đánh giá của bạn</h3>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?>
                    </div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="feedback.php?product_id=<?= $productId ?>">

                    <label class="field-label">
                        Chọn đơn hàng <span style="color:#c0392b;">*</span>
                    </label>
                    <select name="order_id" required>
                        <option value="">-- Chọn đơn hàng --</option>
                        <?php foreach ($completedOrders as $order): ?>
                            <option value="<?= $order['order_id'] ?>">
                                Đơn #<?= $order['order_id'] ?> —
                                <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="field-label">
                        Chọn số sao <span style="color:#c0392b;">*</span>
                    </label>
                    <div class="star-picker">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                            <label for="star<?= $i ?>" title="<?= $i ?> sao">&#9733;</label>
                        <?php endfor; ?>
                    </div>

                    <label class="field-label">Nhận xét (tùy chọn)</label>
                    <textarea name="comment"
                        placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."></textarea>

                    <button type="submit" class="submit-btn">
                        <i class="fa-regular fa-paper-plane"></i> Gửi đánh giá
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="login-notice">
                <i class="fa-solid fa-bag-shopping"></i>
                Bạn chưa có đơn hàng hoàn thành với sản phẩm này.
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="login-notice">
            <i class="fa-regular fa-user"></i>
            Vui lòng <a href="home.php">đăng nhập</a> để viết đánh giá sản phẩm.
        </div>
    <?php endif; ?>

    <!-- Danh sách feedback đã được duyệt -->
    <div class="reviews-section">
        <h3><i class="fa-regular fa-comments"></i> Đánh giá từ khách hàng</h3>

        <?php if (empty($feedbacks)): ?>
            <div class="no-reviews">
                <i class="fa-regular fa-face-smile" style="font-size:36px; display:block; margin-bottom:10px;"></i>
                Chưa có đánh giá nào. Hãy là người đầu tiên!
            </div>
        <?php else: ?>
            <?php foreach ($feedbacks as $fb): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-avatar">
                            <?= mb_strtoupper(mb_substr($fb['user_name'], 0, 1)) ?>
                        </div>
                        <div class="reviewer-info">
                            <div class="name"><?= htmlspecialchars($fb['user_name']) ?></div>
                            <div class="date"><?= date('d/m/Y', strtotime($fb['created_at'])) ?></div>
                        </div>
                    </div>
                    <div class="review-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-<?= $i <= $fb['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <?php if (!empty($fb['comment'])): ?>
                        <div class="review-comment"><?= nl2br(htmlspecialchars($fb['comment'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="script.js?v=2"></script>
</body>
</html>
