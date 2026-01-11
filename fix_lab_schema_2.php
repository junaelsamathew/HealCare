<?php
include 'includes/db_connect.php';

echo "Updating Lab Tests Schema (Part 2)...\n";

$conn->query("ALTER TABLE lab_tests ADD COLUMN IF NOT EXISTS instructions TEXT DEFAULT NULL AFTER test_name");

echo "Schema updated successfully.\n";
?>
