<?php
include 'includes/db_connect.php';

echo "--- Lab Tests Table ---\n";
$res = $conn->query("DESCRIBE lab_tests");
while($row = $res->fetch_assoc()) { print_r($row); }

echo "\n--- Registrations Table ---\n";
$res = $conn->query("DESCRIBE registrations");
while($row = $res->fetch_assoc()) { print_r($row); }

echo "\n--- Staff Sample ---\n";
$res = $conn->query("SELECT * FROM registrations WHERE role='staff' LIMIT 5");
while($row = $res->fetch_assoc()) { print_r($row); }
?>
