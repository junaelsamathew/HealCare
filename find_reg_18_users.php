<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT user_id, username, email FROM users WHERE registration_id = 18");
while($row = $res->fetch_assoc()){
    echo "UID: " . $row['user_id'] . " | UNAME: " . $row['username'] . " | EMAIL: " . $row['email'] . "\n";
}
?>
