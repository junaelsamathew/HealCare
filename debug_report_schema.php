<?php
include 'includes/db_connect.php';
$tables = ['billing', 'appointments', 'lab_orders', 'canteen_orders', 'pharmacy_stock', 'payments', 'users', 'doctors'];
foreach ($tables as $t) {
    echo "\nTable: $t\n";
    $res = $conn->query("DESCRIBE $t");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo " - FAILED TO DESCRIBE: " . $conn->error . "\n";
    }
}
?>
