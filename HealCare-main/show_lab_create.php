<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW CREATE TABLE lab_orders");
if($row = $res->fetch_assoc()) {
    echo $row['Create Table'];
}
?>
