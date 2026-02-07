<?php
include 'includes/db_connect.php';
$tables = ['nurses', 'lab_staff', 'pharmacists', 'receptionists', 'canteen_staff'];
foreach($tables as $t) {
    echo "Table: $t\n";
    $res = $conn->query("DESCRIBE $t");
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
    echo "\n";
}
?>
