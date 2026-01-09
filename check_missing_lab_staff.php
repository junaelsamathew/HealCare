<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, u.username FROM users u WHERE u.role = 'staff' AND u.user_id NOT IN (SELECT user_id FROM lab_staff)");
echo "Staff users missing in lab_staff: " . $res->num_rows . "\n";
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['user_id'] . " | Username: " . $row['username'] . "\n";
}
?>
