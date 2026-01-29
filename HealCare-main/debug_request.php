<?php
include 'includes/db_connect.php';

$res = $conn->query("SELECT * FROM nurse_vitals_requests WHERE request_id = 1");
$req = $res->fetch_assoc();
print_r($req);

$pid = $req['patient_id'];
$did = $req['doctor_id'];

echo "Patient User ($pid):\n";
$p_user = $conn->query("SELECT * FROM users WHERE user_id = $pid")->fetch_assoc();
print_r($p_user);

if ($p_user) {
    echo "Patient Reg (" . $p_user['registration_id'] . "):\n";
    $p_reg = $conn->query("SELECT * FROM registrations WHERE registration_id = " . $p_user['registration_id'])->fetch_assoc();
    print_r($p_reg);
}

echo "Doctor User ($did):\n";
$d_user = $conn->query("SELECT * FROM users WHERE user_id = $did")->fetch_assoc();
print_r($d_user);

if ($d_user) {
    echo "Doctor Reg (" . $d_user['registration_id'] . "):\n";
    $d_reg = $conn->query("SELECT * FROM registrations WHERE registration_id = " . $d_user['registration_id'])->fetch_assoc();
    print_r($d_reg);
}
?>
