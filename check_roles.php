<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT DISTINCT user_role FROM users");
while($row = $res->fetch_assoc()) echo $row['user_role'] . "\n";
?>
