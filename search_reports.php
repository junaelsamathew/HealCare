<?php
include 'includes/db_connect.php';
$tests = ['Juna', 'CBC'];
foreach($tests as $t) {
    echo "--- Search: $t ---\n";
    $res = $conn->query("SELECT * FROM manual_reports WHERE report_title LIKE '%$t%' OR report_type LIKE '%$t%'");
    while($row = $res->fetch_assoc()) {
        echo "ID: " . $row['report_id'] . " | Title: " . $row['report_title'] . " | PID: " . ($row['patient_id'] ?? 'NULL') . "\n";
    }
}
?>
