<?php
include 'includes/db_connect.php';

echo "Wiping and Rebuilding Database (Correctly)...\n";

$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $conn->query("DROP TABLE IF EXISTS `" . $row[0] . "`");
}

// Run the full create script with FK check OFF
$sql_file = 'database/create_tables.sql';
$sql_content = file_get_contents($sql_file);
$queries = array_filter(array_map('trim', explode(';', $sql_content)));

foreach ($queries as $query) {
    if (empty($query)) continue;
    if (!$conn->query($query)) {
        echo "Error in query: " . $conn->error . "\n";
    }
}

// Add Custom Fee Columns
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 200.00 AFTER queue_number");
$conn->query("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 200.00");

// Re-create Admin
$admin_pass = password_hash("admin@Healcare", PASSWORD_DEFAULT);
$conn->query("INSERT INTO registrations (name, email, phone, password, user_type, status) VALUES ('Administrator', 'admin@gmail.com', '0000000000', '$admin_pass', 'admin', 'Approved')");
$reg_id = $conn->insert_id;
$conn->query("INSERT INTO users (registration_id, username, email, password, role, status) VALUES ($reg_id, 'admin', 'admin@gmail.com', '$admin_pass', 'admin', 'Active')");

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Database rebuilt. Now adding doctors...\n";
include 'add_req_doctors.php';
echo "All done.\n";
?>
