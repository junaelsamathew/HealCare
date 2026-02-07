<?php
include 'includes/db_connect.php';

$chart_labels = [];
$chart_values = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_date = date('M d', strtotime("-$i days"));
    $chart_labels[] = $display_date;
    
    $traffic = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = '$date'")->fetch_assoc()['count'];
    $chart_values[] = (int)$traffic;
}

echo "Labels: " . json_encode($chart_labels) . "\n";
echo "Values: " . json_encode($chart_values) . "\n";

$res = $conn->query("SELECT COUNT(*) as total FROM appointments");
echo "Total appointments: " . $res->fetch_assoc()['total'] . "\n";
?>
