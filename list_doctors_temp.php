<?php
include 'includes/db_connect.php';
$query = "SELECT r.name, d.department, r.profile_photo FROM doctors d JOIN users u ON d.user_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id LIMIT 10";
$res = $conn->query($query);
while($row = $res->fetch_assoc()) {
    echo "NAME: " . $row['name'] . " | DEPT: " . $row['department'] . " | PHOTO: " . $row['profile_photo'] . "\n";
}
?>
