<?php
include 'includes/db_connect.php';

echo "=== NURSE_VITALS_REQUESTS ===\n";
$res = $conn->query("DESCRIBE nurse_vitals_requests");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Table nurse_vitals_requests does not exist.\n";
}
?>
