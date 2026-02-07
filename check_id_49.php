<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM lab_tests WHERE labtest_id = 49");
$row = $res->fetch_assoc();
echo "ID: " . $row['labtest_id'] . " | Status: " . $row['status'] . " | Path: " . $row['report_path'] . "\n";
?>
