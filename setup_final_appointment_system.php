<?php
include 'includes/db_connect.php';

echo "<h2>Starting Schema Update...</h2>";

// 1. Create external_patients table
$sql_ext_patients = "CREATE TABLE IF NOT EXISTS external_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    age INT,
    gender VARCHAR(20),
    phone VARCHAR(20),
    reason_for_visit TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_ext_patients)) {
    echo "✅ Table 'external_patients' confirmed.<br>";
} else {
    echo "❌ Error creating 'external_patients': " . $conn->error . "<br>";
}

// 2. Add columns to appointments table
$cols = [
    "external_patient_id INT NULL",
    "priority ENUM('Normal', 'Urgent', 'Emergency') DEFAULT 'Normal'",
    "visit_mode ENUM('OPD', 'IPD', 'Referral') DEFAULT 'OPD'",
    "created_by INT NULL",
    "referred_by_doctor_id INT NULL"
];

foreach ($cols as $col_def) {
    $col_name = explode(' ', trim($col_def))[0];
    $check = $conn->query("SHOW COLUMNS FROM appointments LIKE '$col_name'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE appointments ADD COLUMN $col_def")) {
            echo "✅ Column '$col_name' added to 'appointments'.<br>";
        } else {
            echo "❌ Error adding '$col_name': " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️ Column '$col_name' already exists.<br>";
    }
}

// Ensure appointment status can handle all types
$conn->query("ALTER TABLE appointments MODIFY COLUMN status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled', 'Requested') DEFAULT 'Pending'");

echo "<h3>Setup Complete!</h3>";
?>
