<?php
include 'includes/db_connect.php';

// 1. Identify all users with Augustine's email
$email = 'augustinejoyaljose@gmail.com';
$res = $conn->query("SELECT user_id, registration_id FROM users WHERE email = '$email'");
$uids = [];
while($row = $res->fetch_assoc()) {
    $uids[] = $row['user_id'];
}

echo "Found UIDs: " . implode(", ", $uids) . "\n";

if (count($uids) > 1) {
    // Keep the first one, delete the rest
    $keep_uid = $uids[0];
    for ($i = 1; $i < count($uids); $i++) {
        $del_uid = $uids[$i];
        echo "Deleting duplicate UID: $del_uid\n";
        $conn->query("DELETE FROM users WHERE user_id = $del_uid");
        $conn->query("DELETE FROM patient_profiles WHERE user_id = $del_uid");
    }
} else {
    $keep_uid = $uids[0] ?? null;
}

if ($keep_uid) {
    // Ensure this user points to the correct registration
    // Check registrations for this email
    $res_reg = $conn->query("SELECT registration_id, name FROM registrations WHERE email = '$email'");
    $reg_ids = [];
    while($row = $res_reg->fetch_assoc()) {
        echo "Found Registration: ID=" . $row['registration_id'] . " | Name=" . $row['name'] . "\n";
        $reg_ids[] = $row['registration_id'];
    }
    
    // We should only have one registration for this person
    // If we have multiple, find the one with the correct name "Augustine Joyal Jose"
    $correct_reg_id = 10; // We know 10 is Augustine from previous steps
    
    // Update user 11 to point to 10
    $conn->query("UPDATE users SET registration_id = 10 WHERE user_id = $keep_uid");
    echo "Linked UID $keep_uid to Registration 10.\n";
    
    // Make sure Registration 10 has the correct name
    $conn->query("UPDATE registrations SET name = 'Augustine Joyal Jose', email = '$email' WHERE registration_id = 10");
    echo "Updated Registration 10 name and email.\n";
    
    // Update Profile
    $conn->query("UPDATE patient_profiles SET name = 'Augustine Joyal Jose' WHERE user_id = $keep_uid");
    echo "Updated Patient Profile name.\n";
}

// Also check if Juna (Reg 8) somehow has Augustine's email in users table
$conn->query("UPDATE registrations SET name = 'Juna Elsa Mathew', email = 'junaelsamathew@gmail.com' WHERE registration_id = 8");
echo "Cleaned up Juna's registration.\n";

?>
