<?php
include 'includes/db_connect.php';
$uname = 'augustienjoyaljose';
$res = $conn->query("SELECT p.name as profile_name, r.name as reg_name, u.username 
                     FROM users u 
                     LEFT JOIN registrations r ON u.registration_id = r.registration_id 
                     LEFT JOIN patient_profiles p ON u.user_id = p.user_id 
                     WHERE u.username = '$uname' OR u.email LIKE '%$uname%'");
if($row = $res->fetch_assoc()) {
    echo "Names for $uname:\n";
    echo "Reg Table: " . $row['reg_name'] . "\n";
    echo "Profile Table: " . $row['profile_name'] . "\n";
    echo "Username: " . $row['username'] . "\n";
} else {
    echo "User $uname not found.\n";
}
?>
