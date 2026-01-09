<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, u.username, ls.lab_type FROM users u LEFT JOIN lab_staff ls ON u.user_id = ls.user_id WHERE u.role = 'staff'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
