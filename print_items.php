<?php
include 'includes/db_connect.php';

$res = $conn->query("SELECT item_name, item_category, availability FROM canteen_menu");
while($row = $res->fetch_assoc()) {
    echo "[" . $row['item_category'] . "] " . $row['item_name'] . " - " . $row['availability'] . "\n";
}
?>
