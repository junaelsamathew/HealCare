<?php
include 'includes/db_connect.php';

// Enhanced manual_reports table with report_type and report_category
$sql = "CREATE TABLE IF NOT EXISTS manual_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    report_category VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    report_title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    report_date DATE NOT NULL,
    file_size INT,
    status VARCHAR(20) DEFAULT 'Pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table manual_reports created/updated successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create report_analytics table for extracted data
$sql_analytics = "CREATE TABLE IF NOT EXISTS report_analytics (
    analytics_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,2),
    metric_unit VARCHAR(50),
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES manual_reports(report_id) ON DELETE CASCADE
)";

if ($conn->query($sql_analytics) === TRUE) {
    echo "Table report_analytics created successfully.\n";
} else {
    echo "Error creating analytics table: " . $conn->error . "\n";
}

// Create uploads directory if not exists
$dir = 'uploads/reports';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
    echo "Directory $dir created.\n";
}

echo "\nEnhanced report system setup complete!\n";
?>
