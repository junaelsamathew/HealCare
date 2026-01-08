<?php
include 'includes/db_connect.php';
$email = 'augustinejoyaljose@gmail.com';
$res = $conn->query("SELECT user_id, email, username, registration_id FROM users WHERE email = '$email'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
