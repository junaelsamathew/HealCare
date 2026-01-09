<?php
include 'includes/db_connect.php';
function desc($t, $conn) {
    echo "\n$t:\n";
    $r = $conn->query("DESC $t");
    while($w = $r->fetch_assoc()) echo $w['Field'] . " (" . $w['Type'] . ") ";
    echo "\n";
}
desc('canteen_menu', $conn);
desc('canteen_orders', $conn);
?>
