<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE patient_vitals");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - Null: " . $row['Null'] . "\n";
}
?>
