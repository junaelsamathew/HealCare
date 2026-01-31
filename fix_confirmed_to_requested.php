<?php
include 'includes/db_connect.php';
$conn->query("UPDATE appointments SET status = 'Requested' WHERE status = 'Confirmed' AND appointment_date >= CURDATE()");
echo "Updated recent Confirmed appointments to Requested.\n";
?>
