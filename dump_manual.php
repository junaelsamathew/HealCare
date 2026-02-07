<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM manual_reports ORDER BY created_at DESC LIMIT 50");
$data = [];
while($row = $res->fetch_assoc()) $data[] = $row;
echo json_encode($data, JSON_PRETTY_PRINT);
?>
