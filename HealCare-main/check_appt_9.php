<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT appointment_id, patient_id, status FROM appointments WHERE appointment_id = 9");
if ($row = $res->fetch_assoc()) {
    echo "Appt ID: 9 | Patient ID: " . $row['patient_id'] . " | Status: '" . $row['status'] . "'\n";
} else {
    echo "No appointment with ID 9 found.\n";
}
?>
