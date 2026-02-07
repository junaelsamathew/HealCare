<?php
include 'includes/db_connect.php';

$adm_id = 1; // Assuming this is the failing one based on previous output

echo "Checking Admission ID: $adm_id\n";

$adm = $conn->query("SELECT * FROM admissions WHERE admission_id = $adm_id")->fetch_assoc();
print_r($adm);

if ($adm) {
    if ($adm['room_id']) {
        echo "Room ID: " . $adm['room_id'] . "\n";
        $room = $conn->query("SELECT * FROM rooms WHERE room_id = " . $adm['room_id'])->fetch_assoc();
        print_r($room);
        
        if ($room) {
             echo "Ward ID: " . $room['ward_id'] . "\n";
             $ward = $conn->query("SELECT * FROM wards WHERE ward_id = " . $room['ward_id'])->fetch_assoc();
             print_r($ward);
        }
    }
    
    echo "Patient ID: " . $adm['patient_id'] . "\n";
    $user = $conn->query("SELECT * FROM users WHERE user_id = " . $adm['patient_id'])->fetch_assoc();
    print_r($user);
    
    if ($user) {
        echo "Registration ID: " . $user['registration_id'] . "\n";
        $reg = $conn->query("SELECT * FROM registrations WHERE registration_id = " . $user['registration_id'])->fetch_assoc();
        print_r($reg);
    }
}
?>
