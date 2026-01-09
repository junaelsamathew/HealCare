<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW COLUMNS FROM lab_orders LIKE 'order_status'");
$row = $res->fetch_assoc();
echo "DEFAULT:[" . $row['Default'] . "]\n";
echo "TYPE:[" . $row['Type'] . "]\n";
?>
