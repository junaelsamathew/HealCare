<?php
include 'includes/db_connect.php';

// 1. Nurse Table
$nurse_table = "CREATE TABLE IF NOT EXISTS nurses (
    nurse_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    department VARCHAR(100),
    shift VARCHAR(20),
    qualification VARCHAR(100),
    experience INT(11),
    date_of_join DATE,
    designation VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

// 2. Lab Staff Table
$lab_staff_table = "CREATE TABLE IF NOT EXISTS lab_staff (
    labstaff_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    lab_type VARCHAR(100),
    shift VARCHAR(20),
    qualification VARCHAR(100),
    experience INT(11),
    date_of_join DATE,
    designation VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

// 3. Pharmacist Table
$pharmacist_table = "CREATE TABLE IF NOT EXISTS pharmacists (
    pharmacist_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    qualification VARCHAR(100),
    experience INT(11),
    shift VARCHAR(20),
    date_of_join DATE,
    designation VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

// 4. Canteen Staff Table
$canteen_staff_table = "CREATE TABLE IF NOT EXISTS canteen_staff (
    canteenstaff_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    role VARCHAR(50),
    shift VARCHAR(20),
    date_of_join DATE,
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

// 5. Receptionist Table
$receptionist_table = "CREATE TABLE IF NOT EXISTS receptionists (
    receptionist_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    desk_no VARCHAR(20),
    shift VARCHAR(20),
    experience INT(11),
    qualification VARCHAR(100),
    date_of_join DATE,
    language_known VARCHAR(100),
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

$queries = [
    "Nurses" => $nurse_table,
    "Lab Staff" => $lab_staff_table,
    "Pharmacists" => $pharmacist_table,
    "Canteen Staff" => $canteen_staff_table,
    "Receptionists" => $receptionist_table
];

echo "<h2>Setting up Staff Tables...</h2>";

foreach ($queries as $name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✅ Table '$name' created successfully.<br>";
    } else {
        echo "❌ Error creating table '$name': " . $conn->error . "<br>";
    }
}

$conn->close();
?>
