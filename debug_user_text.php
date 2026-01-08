<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, u.username, u.email, u.registration_id, r.name FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id");
while($row = $res->fetch_assoc()) {
    echo "UID: " . $row['user_id'] . " | UNAME: " . $row['username'] . " | NAME: " . $row['name'] . "\n";
}
?>
