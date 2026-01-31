<?php
include 'includes/db_connect.php';

function dump_table($conn, $table) {
    echo "\nTable: $table\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default']);
    }
}

dump_table($conn, 'appointments');
dump_table($conn, 'doctors');
dump_table($conn, 'users');
?>
