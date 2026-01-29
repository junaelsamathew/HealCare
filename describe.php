<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE users");
if($res) {
    while($row = $res->fetch_assoc()) print_r($row);
} else {
    echo "users does not exist\n";
}
$res = $conn->query("DESCRIBE registrations");
if($res) {
    while($row = $res->fetch_assoc()) print_r($row);
} else {
    echo "registrations does not exist\n";
}
?>
