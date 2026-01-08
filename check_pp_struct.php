<?php
include 'includes/db_connect.php';
$result = $conn->query("SHOW COLUMNS FROM patient_profiles");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
