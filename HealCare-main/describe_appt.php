<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE appointments");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Key'] . "\n";
}
?>
