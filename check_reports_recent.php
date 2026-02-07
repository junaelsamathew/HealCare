<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM manual_reports WHERE created_at >= NOW() - INTERVAL 2 HOUR ORDER BY created_at DESC");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
?>
