<?php
include 'includes/db_connect.php';

echo "--- Searching for Ciya ---\n";
$res1 = $conn->query("SELECT u.user_id, r.name, u.user_role FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Ciya%'");
if ($res1) {
    while($row = $res1->fetch_assoc()) print_r($row);
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "--- Searching for Gigi ---\n";
$res2 = $conn->query("SELECT u.user_id, r.name, u.user_role FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Gigi%'");
if ($res2) {
    while($row = $res2->fetch_assoc()) print_r($row);
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
