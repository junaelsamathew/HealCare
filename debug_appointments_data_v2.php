<?php
include 'includes/db_connect.php';

echo "ID | PatID | DocID | Date | Status | PayStatus\n";
$res = $conn->query("SELECT appointment_id, patient_id, doctor_id, appointment_date, status, payment_status FROM appointments ORDER BY appointment_id DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo "{$row['appointment_id']} | {$row['patient_id']} | {$row['doctor_id']} | {$row['appointment_date']} | {$row['status']} | {$row['payment_status']}\n";
}

echo "\nDocID | UserID | Spec\n";
$res = $conn->query("SELECT doctor_id, user_id, specialization FROM doctors");
while($row = $res->fetch_assoc()) {
    echo "{$row['doctor_id']} | {$row['user_id']} | {$row['specialization']}\n";
}

echo "\nUserID | RegID | Role\n";
$res = $conn->query("SELECT user_id, registration_id, role FROM users WHERE role = 'doctor'");
while($row = $res->fetch_assoc()) {
    echo "{$row['user_id']} | {$row['registration_id']} | {$row['role']}\n";
}
?>
