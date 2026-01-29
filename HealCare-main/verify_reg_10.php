<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM registrations WHERE registration_id = 10");
print_r($res->fetch_assoc());
?>
