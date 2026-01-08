<?php
include 'includes/db_connect.php';
$conn->query("UPDATE appointments SET status = 'Approved' WHERE status IS NULL OR status = '' OR TRIM(status) = ''");
echo "Updated " . $conn->affected_rows . " appointments.\n";
?>
