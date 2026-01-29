<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT user_id, email, registration_id FROM users WHERE email LIKE 'august%'");
while($row = $res->fetch_assoc()){
    echo "UID: " . $row['user_id'] . " | EMAIL: " . $row['email'] . " | REGID: " . $row['registration_id'] . "\n";
}
?>
