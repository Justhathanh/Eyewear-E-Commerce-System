<?php
session_start();
include "db.php";

$id = $_SESSION['user_id'];
mysqli_query($conn, "DELETE FROM users WHERE id=$id");

session_destroy();
header("Location: home.php");
exit();