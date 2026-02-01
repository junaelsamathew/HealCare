<?php
include 'includes/db_connect.php';

try {
    $conn->query("ALTER TABLE billing ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL");
    echo "Column 'payment_method' added successfully to 'billing' table.";
} catch (Exception $e) {
    if ($conn->errno == 1060) {
        echo "Column 'payment_method' already exists.";
    } else {
        echo "Error adding column: " . $e->getMessage();
    }
}
?>
