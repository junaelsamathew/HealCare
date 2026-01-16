<?php
include 'includes/db_connect.php';

// Add Age
try {
    $conn->query("ALTER TABLE patient_profiles ADD COLUMN age INT DEFAULT NULL");
    echo "Added age column.\n";
} catch (Exception $e) {
    echo "Age column might already exist or error: " . $e->getMessage() . "\n";
}

// Add Blood Group
try {
    $conn->query("ALTER TABLE patient_profiles ADD COLUMN blood_group VARCHAR(10) DEFAULT NULL");
    echo "Added blood_group column.\n";
} catch (Exception $e) {
    echo "Blood group column might already exist or error: " . $e->getMessage() . "\n";
}

// Update the specific record for the user in the screenshot to have some data, if we can find them.
// "Augustine Joyal Jose" -> ID: HC-P-2026-7922 (from screenshot)
$conn->query("UPDATE patient_profiles SET age = 24, blood_group = 'B+', address = 'Kerala, India' WHERE name LIKE '%Augustine%'");

echo "Done.";
?>
