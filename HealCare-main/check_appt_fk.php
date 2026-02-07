<?php
include 'includes/db_connect.php';
$result = $conn->query("SHOW CREATE TABLE appointments");
if ($row = $result->fetch_assoc()) {
    echo $row['Create Table'];
}
?>
