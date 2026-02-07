<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM canteen_menu");
if ($res) {
    echo "ID | Name | Current Image\n";
    while($row = $res->fetch_assoc()) {
        echo $row['menu_id'] . " | " . $row['item_name'] . " | " . ($row['image_url'] ?? 'NULL') . "\n";
    }
}
?>
