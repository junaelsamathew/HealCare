<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM lab_staff WHERE user_id = 30");
print_r($res->fetch_assoc());
?>
