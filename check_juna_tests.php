<?php
include 'includes/db_connect.php';
$pid = 38;
$res = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $pid ORDER BY created_at DESC");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['labtest_id'] . " | Test: " . $row['test_name'] . " | Status: " . $row['status'] . " | Path: " . $row['report_path'] . "\n";
}
?>
