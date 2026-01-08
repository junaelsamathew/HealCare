<?php
include 'includes/db_connect.php';
$search = 'august';
$res = $conn->query("SELECT u.user_id, u.username, u.registration_id as user_reg_id, r.registration_id as reg_table_id, r.name 
                     FROM users u 
                     LEFT JOIN registrations r ON u.registration_id = r.registration_id 
                     WHERE u.email LIKE '%$search%'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
