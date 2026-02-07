<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM registrations WHERE email LIKE '%augustie%'");
echo "REGISTRATIONS:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
$res = $conn->query("SELECT * FROM users WHERE email LIKE '%augustie%' OR username LIKE '%augustie%'");
echo "USERS:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
