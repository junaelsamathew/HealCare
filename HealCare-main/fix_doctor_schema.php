<?php
include 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS doctor_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('Available', 'Not Available') DEFAULT 'Available',
    UNIQUE KEY (doctor_id, day_of_week),
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql)) {
    echo "Table 'doctor_schedules' created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Also ensure doctors table has 'department' if it doesn't (it does from previous DESCRIBE)
// Let's check if 'availability_status' exists in doctors table
$res = $conn->query("SHOW COLUMNS FROM doctors LIKE 'availability_status'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE doctors ADD COLUMN availability_status ENUM('Available', 'On Leave', 'Busy') DEFAULT 'Available'");
    echo "Added availability_status to doctors table.\n";
}
?>
