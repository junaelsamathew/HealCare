<?php
include 'includes/db_connect.php';

echo "All Doctors:\n";
$res = $conn->query("SELECT u.user_id, r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.role = 'doctor'");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['user_id']} | Name: {$row['name']}\n";
}

echo "\nRecent Appointments (Last 10):\n";
$res = $conn->query("SELECT a.appointment_id, a.patient_id, a.doctor_id, a.appointment_date, a.status, a.payment_status, r_pat.name as pat_name, r_doc.name as doc_name 
                    FROM appointments a 
                    LEFT JOIN users u_pat ON a.patient_id = u_pat.user_id 
                    LEFT JOIN registrations r_pat ON u_pat.registration_id = r_pat.registration_id 
                    LEFT JOIN users u_doc ON a.doctor_id = u_doc.user_id 
                    LEFT JOIN registrations r_doc ON u_doc.registration_id = r_doc.registration_id 
                    ORDER BY a.appointment_id DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['appointment_id']} | Pat: {$row['pat_name']} | Doc: {$row['doc_name']} (ID: {$row['doctor_id']}) | Date: {$row['appointment_date']} | Status: {$row['status']} | Pay: {$row['payment_status']}\n";
}
?>
