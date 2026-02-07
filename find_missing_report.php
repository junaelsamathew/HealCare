<?php
include 'includes/db_connect.php';
$today = date('Y-m-d');
$res = $conn->query("SELECT * FROM manual_reports WHERE created_at LIKE '$today%' OR report_title LIKE '%Juna%' OR report_title LIKE '%CBC%' ORDER BY created_at DESC");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['report_id'] . " | Title: " . $row['report_title'] . " | PID: " . $row['patient_id'] . " | Created: " . $row['created_at'] . "\n";
}
?>
