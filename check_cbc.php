<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM lab_tests WHERE test_name = 'CBC' ORDER BY created_at DESC LIMIT 5");
$data = [];
while($row = $res->fetch_assoc()) $data[] = $row;
echo json_encode($data, JSON_PRETTY_PRINT);
?>
