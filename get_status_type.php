<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW COLUMNS FROM appointments LIKE 'status'");
$row = $res->fetch_assoc();
echo "Type: " . $row['Type'] . "\n";
?>
