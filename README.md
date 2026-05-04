# 👓 Vista Optic — Eyewear E-Commerce System

##  Giới thiệu

Vista Optic là hệ thống web bán kính mắt với đầy đủ chức năng cơ bản của một e-commerce:

* Xem sản phẩm
* Thêm vào giỏ hàng
* Đặt hàng
* Thanh toán
* Xem lịch sử đơn hàng

Dự án sử dụng kiến trúc tách biệt **Frontend (PHP + JS)** và **Backend (REST API PHP)**.

---

## 🏗️ Cấu trúc project

```bash
E:.
│   product.html
│   product.js
│   README.md
│
├── backend/              # API server
│   ├── config/
│   │   └── database.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── CartController.php
│   │   ├── OrderController.php
│   │   ├── PaymentController.php
│   │   └── ProductController.php
│   └── routes/
│       └── api.php
│
├── frontend/
│   ├── assets/
│   │   ├── css/
│   │   ├── images/
│   │   └── js/
│   │       ├── api.js
│   │       ├── cart.js
│   │       ├── main.js
│   │       └── order-history.js
│   │
│   ├── pages/
│   │   ├── index.html
│   │   └── order-history.html
│   │
│   └── PHP/
│       ├── home.php
│       ├── Product.php
│       ├── cart.php
│       ├── Checkout.php
│       ├── order-history.php
│       ├── login.php
│       ├── logout.php
│       └── ...
│
├── docker/
│   ├── docker-compose.yml
│   └── mysql/
│       └── init.sql
│
└── models/
    └── Product.php
```

---

##  Cách chạy project

### 1. Chạy Docker

```bash
docker compose up
```

### 2. Truy cập

* Frontend: http://localhost:9090
* API: http://localhost:9090/api

---

## Xác thực người dùng

Hệ thống sử dụng **PHP Session**:

```php
$_SESSION['user_id']
```

Truyền sang JS:

```html
<script>
  window.USER_ID = <?= $_SESSION['user_id'] ?? 'null' ?>;
</script>
```

---

## API chính

###  Sản phẩm

```http
GET /api/products
```

---

### Giỏ hàng

```http
GET    /api/cart
POST   /api/cart
DELETE /api/cart/{id}
```

---

### 📄 Đơn hàng

#### Lấy lịch sử đơn hàng

```http
GET /api/orders/history
```

Query:

* `page`
* `size`
* `status`
* `sort`
* `userId`

---

#### Huỷ đơn

```http
PUT /api/orders/{id}/cancel
```

---

#### Mua lại

```http
POST /api/orders/{id}/reorder
```

---

### Thanh toán

```http
POST /api/payments
```

---

## 🧠 Luồng hoạt động

1. User đăng nhập → tạo session
2. Frontend lấy `USER_ID`
3. Gọi API qua `fetch()`
4. Backend xử lý và trả JSON
5. Frontend render UI

---

## ⚠️ Các lỗi đã gặp & cách xử lý

### ❌ Spinner loading mãi

Nguyên nhân:

* Gọi API bị loop
* JS crash (thiếu function)

Fix:

* Sửa `loadOrders()`
* Thêm `applySearchFilter()`

---

### ❌ Không hiển thị ảnh sản phẩm

Nguyên nhân:

* Sai path `/assets/images/...`

Fix:

```js
<img src="http://localhost:9090${item.image}">
```

---

###  API trả nhưng UI không render

Nguyên nhân:

* JS lỗi logic
* JSON không hợp lệ

---
USER_ID undefined

Fix:

```php
<script>
  window.USER_ID = <?= $_SESSION['user_id'] ?>
</script>
```

---

## 🛠️ Công nghệ sử dụng

* Frontend: HTML, CSS, JavaScript, PHP
* Backend: PHP (REST API)
* Database: MySQL
* DevOps: Docker

---

## 📈 Hướng phát triển

* JWT Authentication (thay session)
* Thanh toán online (MoMo, VNPay)
* Admin dashboard
* Tối ưu performance (lazy load, caching)
