<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE appointments status");
$row = $res->fetch_assoc();
echo $row['Type'];
?>
