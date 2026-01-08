<?php
include 'includes/db_connect.php';
$conn->query("UPDATE appointments SET status = 'Requested' WHERE status = '' OR status IS NULL");
echo "Rows updated: " . $conn->affected_rows;
?>
