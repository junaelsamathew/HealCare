<?php
include 'includes/db_connect.php';

echo "Updating Database Schema...\n";

// 1. Add special_notes to medical_records
$res = $conn->query("SHOW COLUMNS FROM medical_records LIKE 'special_notes'");
if ($res->num_rows == 0) {
    if ($conn->query("ALTER TABLE medical_records ADD COLUMN special_notes TEXT DEFAULT NULL AFTER treatment")) {
        echo "✅ Added special_notes to medical_records.\n";
    } else {
        echo "❌ Error adding special_notes: " . $conn->error . "\n";
    }
}

// 2. Create lab_orders table if not exists
$lab_orders_sql = "CREATE TABLE IF NOT EXISTS lab_orders (
    lab_order_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    lab_category VARCHAR(100),
    test_name VARCHAR(255),
    instructions TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
if ($conn->query($lab_orders_sql)) {
    echo "✅ lab_orders table ensured.\n";
} else {
    echo "❌ Error creating lab_orders: " . $conn->error . "\n";
}

// 3. Ensure billing table has all columns
$res = $conn->query("SHOW COLUMNS FROM billing LIKE 'bill_type'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE billing ADD COLUMN bill_type VARCHAR(50) DEFAULT 'General' AFTER doctor_id");
}

echo "Schema update complete.\n";
?>
