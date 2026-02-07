<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM lab_tests WHERE labtest_id = 48");
$row = $res->fetch_assoc();
echo "ID: 48 | Patient ID: " . $row['patient_id'] . "\n";
?>
