<?php
include 'includes/db_connect.php';

echo "--- Canteen Menu Items ---\n";
$res = $conn->query("SELECT * FROM canteen_menu");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "ID: " . $row['item_id'] . " | Name: " . $row['item_name'] . " | Image: " . ($row['image_path'] ?? 'NULL') . "\n";
    }
} else {
    echo "Error: canteen_menu table not found or empty.\n";
}
?>
