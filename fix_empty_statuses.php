<?php
include 'includes/db_connect.php';
$conn->query("UPDATE appointments SET status = 'Approved' WHERE status = '' OR status IS NULL");
echo "Updated empty statuses to Approved.\n";
?>
