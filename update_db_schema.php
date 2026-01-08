<?php
include 'includes/db_connect.php';

// Check if verification_token exists
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'verification_token'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL AFTER status");
    echo "Added verification_token column.\n";
} else {
    echo "verification_token column already exists.\n";
}

// Check for status column
$check_status = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($check_status->num_rows > 0) {
    echo "Status column exists.\n";
} else {
    echo "Status column missing!\n"; 
}
?>
