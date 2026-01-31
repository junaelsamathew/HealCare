<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT l.*, r.name FROM lab_staff l JOIN users u ON l.user_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
