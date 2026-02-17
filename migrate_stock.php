<?php
include 'includes/db_connect.php';
// Set a default stock for existing available items to prevent immediate shutout
$conn->query("UPDATE canteen_menu SET stock_quantity = 50 WHERE availability = 'Available' AND stock_quantity = 0");
echo "Updated stock for existing items.";
?>
