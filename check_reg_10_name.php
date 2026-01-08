<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT name FROM registrations WHERE registration_id = 10");
echo "Reg 10 Name: " . $row = $res->fetch_assoc()['name'] . "\n";
?>
