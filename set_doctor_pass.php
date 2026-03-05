<?php
include 'includes/db_connect.php';
$email = 'mary.mariam@healcare.com';
$pass = password_hash('Zoom#2023', PASSWORD_DEFAULT);
$update = $conn->query("UPDATE users SET password='$pass' WHERE email='$email'");
if ($update) {
    echo "Password updated for $email to Zoom#2023\n";
    $res = $conn->query("SELECT role, status FROM users WHERE email='$email'");
    $user = $res->fetch_assoc();
    echo "Current Role: " . $user['role'] . "\n";
    echo "Current Status: " . $user['status'] . "\n";
} else {
    echo "Failed to update password: " . $conn->error . "\n";
}
?>
