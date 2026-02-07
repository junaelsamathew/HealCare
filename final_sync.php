<?php
include 'includes/db_connect.php';
$email_gmail = 'augustinejoyaljose@gmail.com';
$email_mca = 'augustinejoyaljose2028@mca.ajce.in';

// Update REGID 10 to use the email the user logs in with
$conn->query("UPDATE registrations SET email = '$email_gmail' WHERE registration_id = 10");
echo "Updated Registration 10 to use gmail address.\n";

// Update all users (UID 11 and UID 19) to use the gmail address for consistency
$conn->query("UPDATE users SET email = '$email_gmail', registration_id = 10 WHERE user_id IN (11, 19)");
echo "Updated Users 11 and 19 to point to Registration 10 and use gmail address.\n";

// Final check on profile names
$conn->query("UPDATE patient_profiles SET name = 'Augustine Joyal Jose' WHERE user_id IN (11, 19)");
echo "Updated patient profile names.\n";
?>
