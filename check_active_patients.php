<?php
include 'includes/db_connect.php';

$today = date('Y-m-d');
echo "Checking active patients for date: $today\n\n";

echo "=== APPOINTMENTS TODAY ===\n";
$sql = "SELECT a.appointment_id, a.patient_id, r.name, a.status 
        FROM appointments a 
        JOIN users u ON a.patient_id = u.user_id 
        JOIN registrations r ON u.registration_id = r.registration_id 
        WHERE a.appointment_date = '$today'";
$res = $conn->query($sql);
if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No appointments found for today.\n";
}

echo "\n=== ADMITTED PATIENTS ===\n";
$sql_adm = "SELECT a.admission_id, a.patient_id, r.name, a.status 
            FROM admissions a 
            JOIN users u ON a.patient_id = u.user_id 
            JOIN registrations r ON u.registration_id = r.registration_id 
            WHERE a.status = 'Admitted'";
$res_adm = $conn->query($sql_adm);
if ($res_adm->num_rows > 0) {
    while($row = $res_adm->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No currently admitted patients found.\n";
}
?>
