<?php
include 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS health_packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(255) NOT NULL,
    description TEXT,
    included_tests TEXT,
    actual_price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) NOT NULL,
    discount_percent INT DEFAULT 0,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql)) {
    echo "Table 'health_packages' checked/created successfully.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Check if any packages exist, if not, add the ones from the image
$res = $conn->query("SELECT COUNT(*) as count FROM health_packages");
$row = $res->fetch_assoc();
if ($row['count'] == 0) {
    $packages = [
        ['Basic Health Checkup', 'Complete basic health screening', 'CBC, Blood Sugar, Blood Pressure, Urine Test', 1500, 1200, 20],
        ['Comprehensive Package', 'Advanced health screening with imaging', 'CBC, Lipid Profile, Kidney, Liver, ECG, X-Ray', 3500, 2625, 25],
        ['Diabetes Care Package', 'Complete diabetes monitoring', 'HbA1c, Fasting Sugar, PP Sugar, Kidney Function', 2000, 1700, 15]
    ];
    
    foreach ($packages as $p) {
        $stmt = $conn->prepare("INSERT INTO health_packages (package_name, description, included_tests, actual_price, discount_price, discount_percent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdii", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
        $stmt->execute();
    }
    echo "Default packages added.\n";
}
?>
