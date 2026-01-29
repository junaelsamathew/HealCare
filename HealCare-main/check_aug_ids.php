<?php
include 'includes/db_connect.php';
$email = 'augustinejoyaljose@gmail.com';
$res = $conn->query("SELECT user_id, username, registration_id FROM users WHERE email = '$email'");
while($row = $res->fetch_assoc()){
    echo "UID: " . $row['user_id'] . " | REGID: " . $row['registration_id'] . " | UNAME: " . $row['username'] . "\n";
}
?>
