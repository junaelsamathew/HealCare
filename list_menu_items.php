<?php
include 'includes/db_connect.php';

$sql = "SELECT item_category, item_name, price FROM canteen_menu ORDER BY item_category, item_name";
$result = $conn->query($sql);

$current_cat = '';

if ($result->num_rows > 0) {
    echo "### Canteen Menu List\n\n";
    while($row = $result->fetch_assoc()) {
        if ($current_cat != $row['item_category']) {
            $current_cat = $row['item_category'];
            echo "\n**" . strtoupper($current_cat) . "**\n";
            echo "------------------------\n";
        }
        echo "- " . $row['item_name'] . " (₹" . intval($row['price']) . ")\n";
    }
} else {
    echo "No items found.";
}
?>
