<?php
include 'includes/db_connect.php';

function descTable($tableName, $conn) {
    echo "\n--- Table: $tableName ---\n";
    $res = $conn->query("DESCRIBE $tableName");
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
}

descTable('registrations', $conn);
descTable('users', $conn);
descTable('doctors', $conn);
?>
