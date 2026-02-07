<?php
include 'includes/db_connect.php';
$pid = 38;
echo "MANUAL REPORTS for PID 38:\n";
$res = $conn->query("SELECT report_id, report_title, report_type, file_path, patient_id FROM manual_reports WHERE patient_id = $pid");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
?>
