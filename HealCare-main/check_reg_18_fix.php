<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT registration_id, name, email FROM registrations WHERE registration_id = 18");
print_r($res->fetch_assoc());
?>
