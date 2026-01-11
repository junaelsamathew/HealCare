<?php
include 'includes/db_connect.php';
$conn->query("ALTER TABLE canteen_menu ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) DEFAULT NULL");
echo "Added image_url column to canteen_menu.\n";
?>
