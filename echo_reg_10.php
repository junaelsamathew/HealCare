<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT name FROM registrations WHERE registration_id = 10");
$row = $res->fetch_assoc();
echo "Reg 10 Name: " . $row['name'] . "\n";
?>
