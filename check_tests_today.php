<?php
include 'includes/db_connect.php';
$today = date('Y-m-d');
$res = $conn->query("SELECT l.*, r.name FROM lab_tests l JOIN users u ON l.patient_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id WHERE l.created_at LIKE '$today%'");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
?>
