<?php
include 'includes/db_connect.php';
$conn->query("UPDATE appointments SET status = 'Approved' WHERE appointment_id = 1");
echo "Updated appointment 1.\n";
$res = $conn->query("SELECT status FROM appointments WHERE appointment_id = 1");
echo "New Status: [" . $res->fetch_assoc()['status'] . "]\n";
?>
