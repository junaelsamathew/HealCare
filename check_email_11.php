<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT email FROM users WHERE user_id = 11");
echo "UID 11 Email: [" . $row = $res->fetch_assoc()['email'] . "]\n";
?>
