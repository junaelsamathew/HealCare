<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_row()) echo $row[0] . "\n";
echo "---DESCRIBE appointments---\n";
$res = $conn->query("DESCRIBE appointments");
while($row = $res->fetch_assoc()) echo $row['Field'] . " | " . $row['Type'] . "\n";
?>
