<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE billing");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
