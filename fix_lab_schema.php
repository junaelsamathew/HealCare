<?php
include 'includes/db_connect.php';

echo "Updating Lab Tests Schema...\n";

// Add appointment_id
$conn->query("ALTER TABLE lab_tests ADD COLUMN IF NOT EXISTS appointment_id INT DEFAULT NULL AFTER doctor_id");
$conn->query("ALTER TABLE lab_tests ADD INDEX idx_appointment (appointment_id)");

// Add report_path
$conn->query("ALTER TABLE lab_tests ADD COLUMN IF NOT EXISTS report_path VARCHAR(255) DEFAULT NULL AFTER result");

echo "Schema updated successfully.\n";
?>
