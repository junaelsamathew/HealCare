<?php
require 'includes/db_connect.php';
$tables = ['appointments', 'medical_records', 'lab_tests', 'billing', 'prescriptions'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        echo "Table not found or error: " . $conn->error . "\n";
    }
    echo "\n";
}
?>
