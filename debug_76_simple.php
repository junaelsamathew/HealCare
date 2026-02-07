<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT appointment_id, patient_id, doctor_id, status FROM appointments WHERE appointment_id = 76");
$row = $res->fetch_assoc();
echo "ID: " . $row['appointment_id'] . "\n";
echo "Patient ID: " . $row['patient_id'] . "\n";
echo "Doctor ID: " . $row['doctor_id'] . "\n";
echo "Status: " . $row['status'] . "\n";
?>
