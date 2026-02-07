<?php
include 'includes/db_connect.php';
$pid = 38;
echo "LAB TESTS for PID 38:\n";
$res = $conn->query("SELECT labtest_id, test_name, status, report_path FROM lab_tests WHERE patient_id = $pid");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
?>
