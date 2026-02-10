<?php
include 'includes/db_connect.php';
$tables = ['wards', 'rooms', 'admissions', 'billing', 'pharmacy_stock', 'lab_tests', 'prescriptions'];
foreach($tables as $t) {
    echo "--- $t ---\n";
    $res = $conn->query("DESCRIBE $t");
    if($res) {
        while($row = $res->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
?>
