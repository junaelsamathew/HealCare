<?php
include 'includes/db_connect.php';
// Fix user 11 (augustine) who was linked to Juna's registration
if ($conn->query("UPDATE users SET registration_id = 10 WHERE user_id = 11")) {
    echo "Successfully updated User 11 to point to Registration 10.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Also let's update the session check to be more robust in the dashboard
?>
