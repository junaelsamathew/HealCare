<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM manual_reports ORDER BY report_id DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['report_id'] . " | Title: " . $row['report_title'] . " | PID: " . ($row['patient_id'] ?? 'NULL') . " | Created: " . $row['created_at'] . "\n";
}
?>
