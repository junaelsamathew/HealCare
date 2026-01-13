<?php
include 'includes/db_connect.php';

echo "Starting migration of manual_reports table...\n\n";

// Check if columns exist and add them if they don't
$columns_to_add = [
    "report_type VARCHAR(100) NOT NULL DEFAULT 'General Report'",
    "report_category VARCHAR(100) NOT NULL DEFAULT 'Uncategorized'",
    "file_size INT DEFAULT 0",
    "status VARCHAR(20) DEFAULT 'Pending'",
    "admin_notes TEXT"
];

foreach ($columns_to_add as $column_def) {
    // Extract column name
    $column_name = explode(' ', $column_def)[0];
    
    // Check if column exists
    $check_query = "SHOW COLUMNS FROM manual_reports LIKE '$column_name'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $alter_query = "ALTER TABLE manual_reports ADD COLUMN $column_def";
        if ($conn->query($alter_query) === TRUE) {
            echo "✓ Added column: $column_name\n";
        } else {
            echo "✗ Error adding column $column_name: " . $conn->error . "\n";
        }
    } else {
        echo "- Column $column_name already exists\n";
    }
}

echo "\n✓ Migration complete!\n";
echo "\nYou can now access the admin dashboard without errors.\n";

$conn->close();
?>
