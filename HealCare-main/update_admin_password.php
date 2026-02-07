<?php
// This script updates the admin password in the database
include 'includes/db_connect.php';

$new_password = "admin@Healcare";
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the admin password
$sql = "UPDATE users SET password = '$hashed_password' WHERE email = 'admin@gmail.com' AND role = 'admin'";

if ($conn->query($sql)) {
    echo "Admin password updated successfully to 'admin@Healcare'!<br>";
    echo "You can now login with:<br>";
    echo "Email: admin@gmail.com<br>";
    echo "Password: admin@Healcare<br>";
} else {
    echo "Error updating password: " . $conn->error;
}

$conn->close();
?>
