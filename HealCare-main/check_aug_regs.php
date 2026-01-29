<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT registration_id, name, email FROM registrations WHERE email LIKE '%august%'");
while($row = $res->fetch_assoc()){
    echo "REGID: " . $row['registration_id'] . " | NAME: " . $row['name'] . " | EMAIL: " . $row['email'] . "\n";
}
?>
