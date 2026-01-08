<?php
include 'includes/db_connect.php';
$uid = 11;
$res = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $uid");
if ($row = $res->fetch_assoc()) {
    echo "Profile for UID 11:\n";
    print_r($row);
    if ($row['name'] == 'Juna Elsa Mathew') {
        echo "\nMismatch found! Profile name is Juna.\n";
    }
} else {
    echo "No profile found for UID 11.\n";
}
?>
