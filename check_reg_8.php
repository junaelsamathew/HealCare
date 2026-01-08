<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT name, email FROM registrations WHERE registration_id = 8");
print_r($res->fetch_assoc());
?>
