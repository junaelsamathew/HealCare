<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT l.*, r.name FROM lab_tests l JOIN users u ON l.patient_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id ORDER BY l.labtest_id DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['labtest_id'] . " | Patient: " . $row['name'] . " | Test: " . $row['test_name'] . " | Status: " . $row['status'] . " | Path: " . $row['report_path'] . "\n";
}
?>
