<?php
include 'includes/db_connect.php';
echo "Adding medical_history to patient_profiles...\n";
if ($conn->query("ALTER TABLE patient_profiles ADD COLUMN medical_history TEXT DEFAULT NULL AFTER date_of_birth")) {
    echo "✅ Success.\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}
?>
