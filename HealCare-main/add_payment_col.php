<?php
include 'includes/db_connect.php';

try {
    $conn->query("ALTER TABLE lab_tests ADD COLUMN payment_status ENUM('Pending', 'Paid') DEFAULT 'Pending'");
    echo "Column added.";
} catch (Exception $e) {
    echo "Column might already exist: " . $e->getMessage();
}
?>
