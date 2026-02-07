<?php
include 'includes/db_connect.php';
echo "--- DOCTORS TABLE ---\n";
$res = $conn->query("DESCRIBE doctors");
while($row = $res->fetch_assoc()) echo $row['Field'] . " | " . $row['Type'] . "\n";
echo "--- APPOINTMENTS TABLE ---\n";
$res = $conn->query("DESCRIBE appointments");
while($row = $res->fetch_assoc()) echo $row['Field'] . " | " . $row['Type'] . "\n";
?>
