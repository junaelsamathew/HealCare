<?php
include 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS manual_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    department VARCHAR(100),
    report_title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    report_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table manual_reports created successfully or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create uploads directory if not exists
$dir = 'uploads/reports';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
    echo "Directory $dir created.\n";
}
?>
