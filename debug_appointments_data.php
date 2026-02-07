<?php
include 'includes/db_connect.php';

echo "Appointments Table:\n";
$res = $conn->query("SELECT appointment_id, patient_id, doctor_id, appointment_date, status, payment_status FROM appointments ORDER BY appointment_id DESC LIMIT 20");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\nDoctors Table:\n";
$res = $conn->query("SELECT doctor_id, user_id, specialization FROM doctors");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\nUsers Table (Doctors):\n";
$res = $conn->query("SELECT user_id, registration_id, role FROM users WHERE role = 'doctor'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
