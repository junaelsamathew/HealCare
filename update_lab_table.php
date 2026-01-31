<?php
include 'includes/db_connect.php';

$queries = [
    "ALTER TABLE lab_tests ADD COLUMN appointment_id INT DEFAULT NULL",
    "ALTER TABLE lab_tests ADD COLUMN instructions TEXT DEFAULT NULL",
    "ALTER TABLE lab_tests ADD COLUMN report_path VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE lab_tests ADD COLUMN report_file VARCHAR(255) DEFAULT NULL" // Just in case I use this name
];

foreach ($queries as $q) {
    try {
        $conn->query($q);
        echo "Executed: $q\n";
    } catch (Exception $e) {
        echo "Failed/Exists: $q - " . $e->getMessage() . "\n";
    }
}
echo "Done.";
?>
