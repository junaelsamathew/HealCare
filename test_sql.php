<?php
include 'includes/db_connect.php';

$category_filter = 'Lunch'; // Testing Lunch as in screenshot
$sql = "SELECT * FROM canteen_menu WHERE availability = 'Available'";
if ($category_filter != 'All Items') {
    $sql .= " AND item_category = '" . $conn->real_escape_string($category_filter) . "'";
}

echo "SQL: $sql\n";
$res = $conn->query($sql);
echo "Rows: " . $res->num_rows . "\n";

while($row = $res->fetch_assoc()) {
    echo "- " . $row['item_name'] . "\n";
}
?>
