<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT labtest_id, test_name, created_at FROM lab_tests WHERE patient_id = 38 ORDER BY created_at DESC");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
?>
