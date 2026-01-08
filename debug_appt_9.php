<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM appointments WHERE appointment_id = 9");
print_r($res->fetch_assoc());
?>
