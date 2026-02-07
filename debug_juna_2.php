<?php
include 'includes/db_connect.php';
$pid = 16;
echo "LAB TESTS:\n";
$res = $conn->query("SELECT labtest_id, test_name, status, report_path FROM lab_tests WHERE patient_id = $pid");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
echo "MANUAL REPORTS:\n";
$res = $conn->query("SELECT report_id, report_title, report_type, file_path, patient_id FROM manual_reports WHERE patient_id = $pid");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
?>
