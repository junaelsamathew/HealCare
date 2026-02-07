<?php
include 'includes/db_connect.php';
$email = 'augustinejoyaljose@gmail.com';
$res = $conn->query("SELECT * FROM registrations WHERE email = '$email'");
while($row = $res->fetch_assoc()){
    echo "ID: " . $row['registration_id'] . " | NAME: " . $row['name'] . "\n";
}
?>
