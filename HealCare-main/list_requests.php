<?php
include 'includes/db_connect.php';

echo "=== NURSE REQUESTS ===\n";
$res = $conn->query("SELECT * FROM nurse_vitals_requests");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
