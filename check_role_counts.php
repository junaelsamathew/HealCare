<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.role as user_role, COUNT(*) as count FROM users u GROUP BY u.role");
while($row = $res->fetch_assoc()) {
    echo $row['user_role'] . ": " . $row['count'] . "\n";
}
?>
