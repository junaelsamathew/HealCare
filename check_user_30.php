<?php
include 'includes/db_connect.php';
$uid = 30; // Mini Jose
$tables = ['nurses', 'lab_staff', 'pharmacists', 'receptionists', 'canteen_staff'];
foreach($tables as $t) {
    $res = $conn->query("SELECT * FROM $t WHERE user_id = $uid");
    echo "Table $t: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
}
?>
