<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT user_id, email, registration_id FROM users WHERE user_id = 9");
print_r($res->fetch_assoc());
?>
