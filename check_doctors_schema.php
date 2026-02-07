<?php
require 'includes/db_connect.php';
$res = $conn->query('DESCRIBE doctors');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
