<?php
include 'includes/db_connect.php';
$result = $conn->query("SHOW CREATE TABLE appointments");
if ($row = $result->fetch_row()) {
    print_r($row[1]);
}
?>
