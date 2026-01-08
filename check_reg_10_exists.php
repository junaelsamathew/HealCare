<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT registration_id, name, email FROM registrations WHERE registration_id = 10");
if($row = $res->fetch_assoc()) {
    echo "ID: " . $row['registration_id'] . " | NAME: " . $row['name'] . " | EMAIL: [" . $row['email'] . "]\n";
} else {
    echo "REGID 10 NOT FOUND\n";
}
?>
