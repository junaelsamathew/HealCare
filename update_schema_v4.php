<?php
include 'includes/db_connect.php';

$queries = [
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS external_gender VARCHAR(10) NULL",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS visit_type ENUM('OPD', 'IPD') DEFAULT 'OPD'",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS payment_status ENUM('Paid', 'Pending') DEFAULT 'Pending'",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS payment_mode VARCHAR(50) NULL",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS clinical_notes TEXT NULL",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS appointment_category ENUM('Normal', 'Follow-up', 'Emergency') DEFAULT 'Normal'"
];

foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        echo "Error detail: " . $conn->error . "\n";
    }
}

echo "Schema updated for comprehensive appointment system.";
?>
