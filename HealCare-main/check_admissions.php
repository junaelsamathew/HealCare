<?php
include 'includes/db_connect.php';

echo "=== ADMISSIONS TABLE ===\n";
$res = $conn->query("SELECT * FROM admissions");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== ROOMS TABLE ===\n";
$res = $conn->query("SELECT * FROM rooms");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
