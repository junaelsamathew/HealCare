<?php
include 'includes/db_connect.php';

$result = $conn->query("SELECT menu_id, item_name, image_url, item_category FROM canteen_menu");
if ($result) {
    echo "ID | Name | Category | Image URL\n";
    echo "---|---|---|---\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['menu_id']} | {$row['item_name']} | {$row['item_category']} | {$row['image_url']}\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
