<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT name, staff_type FROM registrations WHERE registration_id = 21");
print_r($res->fetch_assoc());
?>
