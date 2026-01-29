<?php
include 'includes/db_connect.php';

$sql_requests = "SELECT nvr.*, r.name as patient_name, u.user_id as patient_id, 
                        dr.name as doctor_name, w.ward_name, rm.room_number
                 FROM nurse_vitals_requests nvr
                 JOIN users u ON nvr.patient_id = u.user_id
                 JOIN registrations r ON u.registration_id = r.registration_id
                 JOIN users du ON nvr.doctor_id = du.user_id
                 JOIN registrations dr ON du.registration_id = dr.registration_id
                 LEFT JOIN admissions adm ON nvr.admission_id = adm.admission_id
                 LEFT JOIN rooms rm ON adm.room_id = rm.room_id
                 LEFT JOIN wards w ON rm.ward_id = w.ward_id
                 WHERE nvr.status = 'Pending'
                 ORDER BY nvr.request_date ASC";

echo "Running Query...\n";
$res = $conn->query($sql_requests);

if ($res) {
    echo "Rows: " . $res->num_rows . "\n";
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
