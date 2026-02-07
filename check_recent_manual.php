<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM manual_reports ORDER BY created_at DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['report_id'] . " | Title: " . $row['report_title'] . " | PID: " . $row['patient_id'] . " | File: " . $row['file_path'] . " | Created: " . $row['created_at'] . "\n";
}
?>
