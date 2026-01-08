<?php
include 'includes/db_connect.php';
$email = 'augustinejoyaljose@gmail.com';

$res = $conn->query("SELECT user_id, registration_id FROM users WHERE email = '$email'");
while($row = $res->fetch_assoc()){
    $uid = $row['user_id'];
    $rid = $row['registration_id'];
    
    $r_name = $conn->query("SELECT name FROM registrations WHERE registration_id = $rid")->fetch_assoc()['name'];
    $p_name = "-";
    $p_res = $conn->query("SELECT name FROM patient_profiles WHERE user_id = $uid");
    if($p_row = $p_res->fetch_assoc()) $p_name = $p_row['name'];
    
    echo "UID: $uid | REGID: $rid | REG_NAME: $r_name | PROF_NAME: $p_name\n";
}

$res = $conn->query("SELECT registration_id, name FROM registrations WHERE email = '$email'");
while($row = $res->fetch_assoc()){
    echo "EXTRA REGID: " . $row['registration_id'] . " | NAME: " . $row['name'] . "\n";
}
?>
