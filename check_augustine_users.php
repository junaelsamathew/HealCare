<?php
include 'includes/db_connect.php';
$email = 'augustinejoyaljose@gmail.com';
$res = $conn->query("SELECT * FROM users WHERE email = '$email'");
echo "Users with email $email:\n";
while($row = $res->fetch_assoc()){
    print_r($row);
}
?>
