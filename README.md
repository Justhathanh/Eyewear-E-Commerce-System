# Eyewear-E-Commerce-System
Hệ thống bán kính mắt trực tuyến cho phép khách hàng tìm kiếm, xem và đặt mua sản phẩm kính một cách tiện lợi.

Hệ thống hỗ trợ nhiều loại đơn hàng:

Mua kính có sẵn
Đặt trước khi hết hàng (pre-order)
Đặt làm kính theo đơn đo mắt (prescription)

Ngoài ra, hệ thống hỗ trợ nhân viên trong việc:

Xử lý đơn hàng
Tư vấn khách hàng
Gia công kính, đóng gói và vận chuyển

Mục tiêu của hệ thống là nâng cao trải nghiệm mua kính bằng cách giảm phụ thuộc vào cửa hàng vật lý và cung cấp quy trình mua hàng linh hoạt, thuận tiện.

Optional 
Tech Stack
Frontend: HTML, CSS, JavaScript
Backend: PHP
Database: MySQL (Docker)

eyewear-shop/
│
├── docker/
│   ├── docker-compose.yml
│   └── mysql/
│       └── init.sql
│
├── backend/                 # PHP
│   ├── config/
│   │   └── database.php
│   │
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   ├── OrderController.php
│   │   └── UserController.php
│   │
│   ├── models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Order.php
│   │   └── OrderItem.php
│   │
│   ├── services/            # business logic (optional nhưng nên có)
│   │   └── OrderService.php
│   │
│   ├── routes/
│   │   └── api.php
│   │
│   ├── middleware/
│   │   └── AuthMiddleware.php
│   │
│   └── index.php           # entry point (API)
│
├── frontend/               # HTML/CSS/JS
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css
│   │   │
│   │   ├── js/
│   │   │   ├── main.js
│   │   │   ├── api.js
│   │   │   └── auth.js
│   │   │
│   │   └── images/
│   │
│   ├── pages/
│   │   ├── index.html
│   │   ├── login.html
│   │   ├── product.html
│   │   ├── cart.html
│   │   └── order.html
│   │
│   └── components/         # dùng JS render (header, navbar)
│       ├── header.js
│       └── footer.js
│
├── public/                 # expose ra browser
│   ├── index.html
│   └── .htaccess
│
└── README.md