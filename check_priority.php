<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW COLUMNS FROM lab_orders LIKE 'priority'");
$row = $res->fetch_assoc();
print_r($row);
?>
