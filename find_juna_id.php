<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Juna%'");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['user_id'] . " | Name: " . $row['name'] . "\n";
}
?>
