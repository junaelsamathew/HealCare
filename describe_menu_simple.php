<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE canteen_menu");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
