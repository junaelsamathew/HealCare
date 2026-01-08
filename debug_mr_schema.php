<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW CREATE TABLE medical_records");
print_r($res->fetch_assoc());
?>
