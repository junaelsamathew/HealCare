<?php
include 'includes/db_connect.php';
// Delete the corrupted registration record
if ($conn->query("DELETE FROM registrations WHERE registration_id = 18")) {
    echo "Deleted corrupted Registration ID 18 (Juna with Augustine's email).\n";
} else {
    echo "Error deleting REGID 18: " . $conn->error . "\n";
}

// Ensure UID 19 and anyone else with Augustine's email points to REGID 10
$conn->query("UPDATE users SET registration_id = 10 WHERE email = 'augustinejoyaljose@gmail.com'");
echo "Ensured users point to REGID 10.\n";

// Final check on all profiles with Augustine's name/email
$conn->query("UPDATE patient_profiles SET name = 'Augustine Joyal Jose' WHERE user_id IN (SELECT user_id FROM users WHERE email = 'augustinejoyaljose@gmail.com')");
echo "Updated profile names.\n";
?>
