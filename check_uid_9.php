<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, u.registration_id, r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.user_id = 9");
print_r($res->fetch_assoc());
?>
