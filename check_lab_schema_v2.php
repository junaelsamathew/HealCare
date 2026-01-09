<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE lab_orders");
echo str_pad("Field", 20) . str_pad("Type", 30) . str_pad("Null", 5) . str_pad("Key", 5) . str_pad("Default", 10) . "Extra\n";
echo str_repeat("-", 80) . "\n";
while($row = $res->fetch_assoc()) {
    echo str_pad($row['Field'], 20) . 
         str_pad($row['Type'], 30) . 
         str_pad($row['Null'], 5) . 
         str_pad($row['Key'], 5) . 
         str_pad((string)$row['Default'], 10) . 
         $row['Extra'] . "\n";
}
?>
