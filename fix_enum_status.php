<?php
include 'includes/db_connect.php';
$conn->query("ALTER TABLE appointments MODIFY COLUMN status ENUM('Requested', 'Approved', 'Scheduled', 'Checked-In', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Requested'");
echo "Fixed status ENUM definition.\n";
$conn->query("UPDATE appointments SET status = 'Approved' WHERE status = '' OR status IS NULL");
echo "Updated empty statuses to Approved.\n";
?>
