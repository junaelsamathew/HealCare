<?php
include 'includes/db_connect.php';
$email = 'augustinejoyaljose@gmail.com';
$res = $conn->query("SELECT u.user_id, u.email, u.registration_id, r.name FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id WHERE u.email = '$email'");
while($row = $res->fetch_assoc()){
    echo "UID: " . $row['user_id'] . " | EMAIL: " . $row['email'] . " | REGID: " . $row['registration_id'] . " | NAME: " . $row['name'] . "\n";
}
?>
