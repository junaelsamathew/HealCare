<?php
include 'includes/db_connect.php';
$email = 'augustinejoyaljose@gmail.com';
$reg_id_correct = 10;

// Update all users with this email to point to the correct registration
$conn->query("UPDATE users SET registration_id = $reg_id_correct WHERE email = '$email'");
echo "Updated users table.\n";

// Update patient profiles to have the correct name
$correct_name = "Augustine Joyal Jose";
$res = $conn->query("SELECT user_id FROM users WHERE email = '$email'");
while($row = $res->fetch_assoc()){
    $uid = $row['user_id'];
    $conn->query("UPDATE patient_profiles SET name = '$correct_name' WHERE user_id = $uid");
    echo "Updated profile for UID $uid.\n";
}
?>
