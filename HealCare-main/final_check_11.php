<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM users WHERE user_id = 11");
print_r($res->fetch_assoc());
?>
