<?php
include 'includes/db_connect.php';
ob_start();
$tables = ['canteen_menu', 'canteen_orders'];
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        printf("%-20s %-20s %-10s %-10s %-20s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default']);
    }
    echo "\n";
}
file_put_contents('schema_output.txt', ob_get_clean());
?>
