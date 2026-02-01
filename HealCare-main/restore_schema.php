<?php
include 'includes/db_connect.php';

$sql_file = 'database/create_tables.sql';
if (!file_exists($sql_file)) {
    die("SQL file not found.");
}

$sql_content = file_get_contents($sql_file);

// Split SQL by semicolon, but be careful with triggers/procedures if any
// This is a simple script, might need more robust splitting if SQL is complex
$queries = array_filter(array_map('trim', explode(';', $sql_content)));

$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$success = 0;
$total = count($queries);
$errors = [];

foreach ($queries as $query) {
    if (empty($query)) continue;
    if ($conn->query($query)) {
        $success++;
    } else {
        $errors[] = $conn->error;
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Executed $success / $total queries successfully.\n";
if (!empty($errors)) {
    echo "First 5 Errors:\n";
    foreach (array_slice($errors, 0, 5) as $err) {
        echo "- $err\n";
    }
}

// Re-add my custom columns if missing
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 200.00 AFTER queue_number");
$conn->query("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 200.00");
$conn->query("UPDATE doctors SET consultation_fee = 200.00 WHERE consultation_fee IS NULL OR consultation_fee = 0");

echo "Database schema restored and updated.\n";
?>
