<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW COLUMNS FROM appointments");
echo "<pre>";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>
