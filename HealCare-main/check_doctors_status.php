<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT count(*) as cnt FROM doctors");
$row = $res->fetch_assoc();
echo "Doctors count: " . $row['cnt'] . "\n";

$res = $conn->query("SHOW COLUMNS FROM doctors");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
