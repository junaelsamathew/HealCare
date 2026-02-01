<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM registrations ORDER BY registration_id DESC LIMIT 10");
echo "RECENT REGISTRATIONS:\n";
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['registration_id'] . " | Name: " . $row['name'] . " | Email: " . $row['email'] . " | Type: " . $row['user_type'] . "\n";
}
$res = $conn->query("SELECT * FROM users ORDER BY user_id DESC LIMIT 10");
echo "\nRECENT USERS:\n";
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['user_id'] . " | Uname: " . $row['username'] . " | Email: " . $row['email'] . " | RegID: " . $row['registration_id'] . "\n";
}
?>
