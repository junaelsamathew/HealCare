<?php
include 'includes/db_connect.php';

echo "Wiping and Rebuilding Database...\n";

$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $conn->query("DROP TABLE `" . $row[0] . "`");
    echo "Dropped " . $row[0] . "\n";
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Run the full create script
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
$conn->query("ALTER TABLE appointments ADD COLUMN consultation_fee DECIMAL(10,2) DEFAULT 200.00 AFTER queue_number");
$conn->query("ALTER TABLE doctors ADD COLUMN consultation_fee DECIMAL(10,2) DEFAULT 200.00");
$conn->query("UPDATE doctors SET consultation_fee = 200.00 WHERE consultation_fee IS NULL OR consultation_fee = 0");

// Re-create Admin correctly linked to registrations if needed
// Actually, auth_handler.php uses fixed check for admin@gmail.com, 
// but it also looks in DB. Let's create it in DB properly.
$admin_pass = password_hash("admin@Healcare", PASSWORD_DEFAULT);
$conn->query("INSERT INTO registrations (name, email, phone, password, user_type, status) VALUES ('Administrator', 'admin@gmail.com', '0000000000', '$admin_pass', 'admin', 'Approved')");
$reg_id = $conn->insert_id;
$conn->query("INSERT INTO users (registration_id, username, email, password, role, status) VALUES ($reg_id, 'admin', 'admin@gmail.com', '$admin_pass', 'admin', 'Active')");

echo "Database rebuilt successfully.\n";
?>
