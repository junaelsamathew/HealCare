<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE appointments");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
