<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM patient_profiles WHERE user_id = 19");
if ($row = $res->fetch_assoc()) {
    echo "Profile for UID 19: " . $row['name'] . "\n";
} else {
    echo "No profile found for UID 19.\n";
}
?>
