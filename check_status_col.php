<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW COLUMNS FROM appointments LIKE 'status'");
print_r($res->fetch_assoc());
?>
