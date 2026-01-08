<?php
include 'includes/db_connect.php';
$search = 'augustie';
echo "Searching Registrations for '$search'...\n";
$res = $conn->query("SELECT * FROM registrations WHERE name LIKE '%$search%' OR email LIKE '%$search%'");
while($row = $res->fetch_assoc()) {
    echo "Reg ID: ".$row['registration_id']." | Name: ".$row['name']." | Email: ".$row['email']."\n";
}
echo "Searching Users for '$search'...\n";
$res = $conn->query("SELECT * FROM users WHERE username LIKE '%$search%' OR email LIKE '%$search%'");
while($row = $res->fetch_assoc()) {
    echo "User ID: ".$row['user_id']." | Uname: ".$row['username']." | Email: ".$row['email']." | RegID: ".$row['registration_id']."\n";
}
?>
