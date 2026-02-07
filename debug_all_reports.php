<?php
include 'includes/db_connect.php';
echo "ALL MANUAL REPORTS:\n";
$res = $conn->query("SELECT * FROM manual_reports");
while($row = $res->fetch_assoc()) echo json_encode($row) . "\n";
?>
