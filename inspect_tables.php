<?php
include 'includes/db_connect.php';

$tables = ['appointments', 'prescriptions', 'lab_results', 'canteen_orders', 'patient_vitals', 'users'];

foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo $row['Field'] . "\n";
        }
    } else {
        echo "Error describe $table: " . $conn->error . "\n";
    }
    echo "\n";
}
?>
