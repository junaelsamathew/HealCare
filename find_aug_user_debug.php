<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, r.name, u.email, u.role, u.status 
                     FROM users u 
                     JOIN registrations r ON u.registration_id = r.registration_id 
                     WHERE r.name LIKE '%Augustine%' OR u.email LIKE '%Augustine%'");
while($row = $res->fetch_assoc()) {
    var_dump($row);
    echo "-------------------\n";
}
?>
