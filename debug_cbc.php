<?php
include 'includes/db_connect.php';
echo "CBC LAB TESTS:\n";
$res = $conn->query("SELECT l.labtest_id, l.patient_id, l.test_name, l.status, r.name as p_name FROM lab_tests l JOIN users u ON l.patient_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id WHERE l.test_name LIKE '%CBC%'");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
?>
