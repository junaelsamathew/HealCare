<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, u.role, r.name, r.staff_type FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Nandana%'");
while($row = $res->fetch_assoc()) {
    echo "User ID: " . $row['user_id'] . " | Role: " . $row['role'] . " | Name: " . $row['name'] . " | Staff Type: " . $row['staff_type'] . "\n";
}
?>
