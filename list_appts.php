<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM appointments");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['appointment_id'] . " | Status: " . $row['status'] . "\n";
}
?>
