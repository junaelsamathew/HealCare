<?php
include 'includes/db_connect.php';
$emails = ['elsamathewjuna@gmail.com', 'abnersam910@gmail.com', 'alanthomas20@gmail.com', 'junemaryantony25@gmail.com', 'krishnanmanoj67@gmail.com', 'jacobmathew456@gmail.com', 'leenajose45@gmail.com', 'marymariam209@gmail.com', 'ancyjames78@gmail.com']; 
$users = ['elsa.mathew@healcare.com', 'june.antony@healcare.com', 'krishnan.manoj@healcare.com', 'jacob.mathew@healcare.com', 'leena.jose@healcare.com', 'ariam@healcare.com', 'mary.mariam@healcare.com', 'ancy.james@healcare.com']; 
$q = "SELECT r.name, r.email, u.username, d.department, r.profile_photo FROM registrations r JOIN users u ON r.registration_id = u.registration_id LEFT JOIN doctors d ON u.user_id = d.user_id WHERE r.email IN ('" . implode("','", $emails) . "') OR u.username IN ('" . implode("','", $users) . "')"; 
$res = $conn->query($q); 
while($row=$res->fetch_assoc()){ 
    echo '|' . $row['name'] . '|' . ($row['department']??'NULL') . '|' . $row['profile_photo'] . "\n"; 
}
?>
