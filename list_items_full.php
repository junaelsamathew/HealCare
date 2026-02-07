<?php
include 'includes/db_connect.php';

$result = $conn->query("SELECT menu_id, item_name, item_category, image_url FROM canteen_menu ORDER BY item_category, item_name");
if ($result) {
    echo "ID | Name | Category | Image URL\n";
    echo "---|---|---|---\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['menu_id']} | {$row['item_name']} | {$row['item_category']} | {$row['image_url']}\n";
    }
}
?>
