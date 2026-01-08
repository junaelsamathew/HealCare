<?php
include 'includes/db_connect.php';
$sql = "SELECT r.name, r.email, d.department FROM doctors d JOIN users u ON d.user_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id";
$res = $conn->query($sql);
while($row = $res->fetch_assoc()) {
    echo $row['name'] . " (" . $row['email'] . ") - " . $row['department'] . "\n";
}
?>
