<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.email, u.role, r.staff_type FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id");
while($row = $res->fetch_assoc()) {
    $role_name = $row['role'];
    if ($role_name == 'staff') $role_name = $row['staff_type'];
    echo "ROLE: " . $role_name . " | EMAIL: " . $row['email'] . "\n";
}
?>
