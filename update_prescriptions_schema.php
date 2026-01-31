<?php
include 'includes/db_connect.php';

// Check if diagnosis column exists in prescriptions
$check = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'diagnosis'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE prescriptions ADD COLUMN diagnosis TEXT AFTER doctor_id")) {
        echo "Column 'diagnosis' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'diagnosis' already exists.";
}

$conn->close();
?>
