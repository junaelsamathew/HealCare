<?php
include 'includes/db_connect.php';
$conn->query("ALTER TABLE appointments MODIFY COLUMN status VARCHAR(50) DEFAULT 'Pending'");
echo "Appointments status column updated to VARCHAR(50).";
?>
