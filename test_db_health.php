<?php
$start = microtime(true);
echo "Testing connection...<br>";
include 'includes/db_connect.php';
echo "Connected in " . (microtime(true) - $start) . " seconds.<br>";

$start = microtime(true);
$res = $conn->query("SHOW TABLES");
echo "Query 'SHOW TABLES' took " . (microtime(true) - $start) . " seconds.<br>";
while($row = $res->fetch_array()) {
    echo $row[0] . "<br>";
}
echo "Done.";
?>
