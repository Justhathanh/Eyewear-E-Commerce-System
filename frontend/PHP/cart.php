<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng</title>

    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        .cart-item {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }

        button {
            margin: 3px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .remove {
            color: red;
        }
    </style>
</head>
<body>

<h1>🛒 Giỏ hàng</h1>

<div id="cart"></div>

<h3 id="total"></h3>

<p id="empty"></p>

<button onclick="checkout()">Đặt hàng</button>


<script src="../assets/js/cart.js"></script>

</body>
</html>
