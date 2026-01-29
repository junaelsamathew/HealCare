<?php
include 'includes/db_connect.php';

echo "Adding appointment_id to prescriptions...\n";
$res = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'appointment_id'");
if ($res->num_rows == 0) {
    if ($conn->query("ALTER TABLE prescriptions ADD COLUMN appointment_id INT DEFAULT NULL AFTER doctor_id")) {
        echo "✅ Added appointment_id to prescriptions.\n";
    } else {
        echo "❌ Error adding appointment_id: " . $conn->error . "\n";
    }
} else {
    echo "Column appointment_id already exists in prescriptions.\n";
}
?>
