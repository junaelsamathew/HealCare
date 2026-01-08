<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW DATABASES");
echo "Databases found:\n";
while($row = $res->fetch_array()) {
    echo "- " . $row[0] . "\n";
}
?>
