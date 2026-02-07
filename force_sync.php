<?php
include 'includes/db_connect.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS external_patients (id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(255) NOT NULL, age INT, gender VARCHAR(20), phone VARCHAR(20), reason_for_visit TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS external_patient_id INT NULL",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS priority ENUM('Normal', 'Urgent', 'Emergency') DEFAULT 'Normal'",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS visit_mode ENUM('OPD', 'IPD', 'Referral') DEFAULT 'OPD'",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS created_by INT NULL",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS referred_by_doctor_id INT NULL",
    "ALTER TABLE doctors ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 500.00",
    "ALTER TABLE appointments MODIFY COLUMN status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled', 'Requested') DEFAULT 'Pending'"
];

foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        echo "❌ Error: " . $conn->error . " in: $sql\n";
    } else {
        echo "✅ Success: " . substr($sql, 0, 40) . "...\n";
    }
}
?>
