<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW CREATE TABLE appointments");
print_r($res->fetch_assoc());
?>
