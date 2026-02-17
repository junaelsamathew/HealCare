<?php
include 'includes/db_connect.php';

echo "Setting up Blood Bank table...\n";

$sql = "CREATE TABLE IF NOT EXISTS blood_bank (
    blood_id INT AUTO_INCREMENT PRIMARY KEY,
    blood_group VARCHAR(5) NOT NULL UNIQUE,
    units_available INT DEFAULT 0,
    last_updated DATETIME,
    low_stock_threshold INT DEFAULT 5
)";

if ($conn->query($sql)) {
    echo "✅ Table blood_bank created successfully.\n";
    
    // Seed data if empty
    $check = $conn->query("SELECT COUNT(*) as c FROM blood_bank");
    if ($check->fetch_assoc()['c'] == 0) {
        $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        foreach ($groups as $group) {
            $conn->query("INSERT INTO blood_bank (blood_group, units_available, last_updated) VALUES ('$group', 10, NOW())");
        }
        echo "✅ Seeded initial blood groups.\n";
    }
} else {
    echo "❌ Error: " . $conn->error . "\n";
}
?>
