<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE lab_orders");
while($row = $res->fetch_assoc()) {
    echo "FIELD:" . $row['Field'] . "\n";
}
?>
