<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM billing WHERE payment_status = 'Pending' LIMIT 10");
echo "<pre>";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>
