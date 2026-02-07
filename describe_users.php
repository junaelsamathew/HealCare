<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE users");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
