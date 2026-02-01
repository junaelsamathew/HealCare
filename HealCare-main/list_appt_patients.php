<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT DISTINCT patient_id FROM appointments");
while($row = $res->fetch_assoc()) {
    echo "Patient ID in Appointments: " . $row['patient_id'] . "\n";
}
?>
