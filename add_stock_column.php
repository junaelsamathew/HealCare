<?php
include 'includes/db_connect.php';

// Check if column exists
$check = $conn->query("SHOW COLUMNS FROM canteen_menu LIKE 'stock_quantity'");
if ($check->num_rows == 0) {
    // Add column
    if ($conn->query("ALTER TABLE canteen_menu ADD COLUMN stock_quantity INT NOT NULL DEFAULT 0 AFTER price")) {
        echo "Column 'stock_quantity' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'stock_quantity' already exists.";
}
?>
