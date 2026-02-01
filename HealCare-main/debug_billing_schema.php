<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE billing");
echo "<pre>";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>
