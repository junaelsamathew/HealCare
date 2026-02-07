<?php
include 'includes/db_connect.php';

echo "--- Ciya John ---\n";
$res1 = $conn->query("SELECT u.user_id, r.name, u.user_role FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Ciya John%'");
while($row = $res1->fetch_assoc()) print_r($row);

echo "--- Gigi Tony ---\n";
$res2 = $conn->query("SELECT u.user_id, r.name, u.user_role FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Gigi Tony%'");
while($row = $res2->fetch_assoc()) print_r($row);
?>
