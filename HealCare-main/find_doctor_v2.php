<?php
include 'includes/db_connect.php';

$res = $conn->query("SELECT * FROM registrations WHERE name LIKE '%Abner Sam Jose%'");
while($row = $res->fetch_assoc()) {
    echo "Reg ID: " . $row['registration_id'] . " | Name: " . $row['name'] . "\n";
    $reg_id = $row['registration_id'];
    $u_res = $conn->query("SELECT * FROM users WHERE registration_id = $reg_id");
    while($u_row = $u_res->fetch_assoc()) {
        echo "  User ID: " . $u_row['user_id'] . " | Role: " . $u_row['role'] . "\n";
        $user_id = $u_row['user_id'];
        $d_res = $conn->query("SELECT * FROM doctors WHERE user_id = $user_id");
        if($d_row = $d_res->fetch_assoc()) {
            echo "    Dept: " . $d_row['department'] . " | Specialization: " . $d_row['specialization'] . "\n";
        } else {
            echo "    No record in doctors table.\n";
        }
    }
}
?>
