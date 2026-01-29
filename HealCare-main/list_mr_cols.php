<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE medical_records");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
