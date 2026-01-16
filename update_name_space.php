<?php
include 'includes/db_connect.php';
$id = 36;
$new_name = "Nandana Pramod";
if ($conn->query("UPDATE registrations SET name = '$new_name' WHERE registration_id = $id")) {
    echo "Successfully updated name to: $new_name\n";
} else {
    echo "Error updating name: " . $conn->error . "\n";
}
// Also update session if they relogin, but currently logged in session won't change until relogin unless I can tell the user to relogin or it's just for display next time.
?>
