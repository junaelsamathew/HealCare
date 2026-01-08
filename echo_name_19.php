<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT name FROM patient_profiles WHERE user_id = 19");
$row = $res->fetch_assoc();
echo "Name in profile 19: [" . $row['name'] . "]\n";
?>
