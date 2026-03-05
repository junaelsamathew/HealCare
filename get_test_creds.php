<?php
include 'includes/db_connect.php';

$roles = ['receptionist', 'nurse', 'lab_staff', 'pharmacist', 'canteen_staff'];
$credentials = [];

foreach ($roles as $role) {
    if ($role == 'receptionist') {
        $res = $conn->query("SELECT u.email, u.username FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.role = 'staff' AND r.staff_type = 'receptionist' LIMIT 1");
    } else {
        $res = $conn->query("SELECT u.email, u.username FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.role = 'staff' AND r.staff_type = '$role' LIMIT 1");
    }
    
    if ($res && $row = $res->fetch_assoc()) {
        $credentials[$role] = $row['email'];
    }
}

// Also get a doctor
$doc = $conn->query("SELECT email FROM users WHERE role = 'doctor' LIMIT 1");
if ($doc) $credentials['doctor'] = $doc->fetch_assoc()['email'];

// Also get a patient
$pat = $conn->query("SELECT email FROM users WHERE role = 'patient' LIMIT 1");
if ($pat) $credentials['patient'] = $pat->fetch_assoc()['email'];

echo json_encode($credentials, JSON_PRETTY_PRINT);
?>
