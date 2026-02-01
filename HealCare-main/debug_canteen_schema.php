<?php
include 'includes/db_connect.php';

echo "--- canteen tables ---\n";
$tables = ['canteen_menu', 'canteen_orders', 'canteen_items', 'food_items'];
foreach ($tables as $table) {
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res && $res->num_rows > 0) {
        echo "Table '$table' exists.\n";
        $res2 = $conn->query("DESCRIBE $table");
        while($row = $res2->fetch_assoc()) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table '$table' does not exist.\n";
    }
}
?>
