<?php
include 'includes/db_connect.php';

$res = $conn->query("SHOW COLUMNS FROM nurse_vitals_requests WHERE Field = 'status'");
print_r($res->fetch_assoc());
?>
