<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM patient_profiles WHERE user_id = 19");
print_r($res->fetch_assoc());
?>
