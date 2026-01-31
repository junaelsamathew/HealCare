<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT appointment_id, patient_id, status FROM appointments WHERE patient_id = 9");
while($row = $res->fetch_assoc()) {
    echo "Appt ID: " . $row['appointment_id'] . " | Status: [" . $row['status'] . "]\n";
}
?>
