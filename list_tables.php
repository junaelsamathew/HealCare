<?php
include 'includes/db_connect.php';
<<<<<<< HEAD
$result = $conn->query("SHOW TABLES");
while($row = $result->fetch_array()) {
=======
$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_row()) {
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
    echo $row[0] . "\n";
}
?>
