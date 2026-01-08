<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'includes/db_connect.php';

$email_gmail = 'augustinejoyaljose@gmail.com';

// 1. Delete Corrupted Reg 18 if still there
$conn->query("DELETE FROM registrations WHERE registration_id = 18");

// 2. Update Reg 10
$sql1 = "UPDATE registrations SET email = '$email_gmail' WHERE registration_id = 10";
if ($conn->query($sql1)) {
    echo "Reg 10 Updated\n";
} else {
    echo "Reg 10 Error: " . $conn->error . "\n";
}

// 3. Update Users
$sql2 = "UPDATE users SET email = '$email_gmail', registration_id = 10 WHERE user_id IN (11, 19)";
if ($conn->query($sql2)) {
    echo "Users 11, 19 Updated\n";
} else {
    echo "Users Error: " . $conn->error . "\n";
}

// 4. Update Profiles
$sql3 = "UPDATE patient_profiles SET name = 'Augustine Joyal Jose' WHERE user_id IN (11, 19)";
if ($conn->query($sql3)) {
    echo "Profiles Updated\n";
} else {
    echo "Profiles Error: " . $conn->error . "\n";
}
?>
