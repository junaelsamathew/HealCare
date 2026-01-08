<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT user_id, username, email FROM users");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['user_id'] . " | UN: " . $row['username'] . " | EM: " . $row['email'] . "\n";
}
?>
