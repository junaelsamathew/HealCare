<?php
include 'includes/db_connect.php';

echo "Final Rebuild & Restore...\n";

$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// 1. Create missing tables
$conn->query("CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255), 
    user_type VARCHAR(20) NOT NULL,
    staff_type VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Pending',
    address TEXT,
    profile_photo VARCHAR(255),
    highest_qualification VARCHAR(255),
    total_experience VARCHAR(100),
    certifications TEXT,
    resume_path VARCHAR(255),
    specialization VARCHAR(100),
    license_number VARCHAR(100),
    dept_preference VARCHAR(100),
    designation VARCHAR(100),
    computer_knowledge VARCHAR(10),
    languages_known VARCHAR(255),
    front_desk_exp VARCHAR(10),
    food_handling VARCHAR(10),
    shift_preference VARCHAR(20),
    canteen_job_role VARCHAR(100),
    date_of_joining DATE,
    additional_details TEXT,
    registered_date DATE DEFAULT CURRENT_DATE,
    app_id VARCHAR(50) UNIQUE,
    admin_message TEXT,
    relevant_experience VARCHAR(100),
    qualification_details TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    permissions TEXT, 
    force_password_change TINYINT(1) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'Active',
    verification_token VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    FOREIGN KEY (registration_id) REFERENCES registrations(registration_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    specialization VARCHAR(100),
    qualification VARCHAR(100),
    experience INT,
    department VARCHAR(100),
    date_of_join DATE,
    designation VARCHAR(50),
    consultation_fee DECIMAL(10,2) DEFAULT 200.00,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Run the rest from the file
$sql_file = 'database/create_tables.sql';
if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    $queries = array_filter(array_map('trim', explode(';', $sql_content)));
    foreach ($queries as $query) {
        if (empty($query) || stripos($query, 'USE ') === 0) continue;
        $conn->query($query);
    }
}

// 2. Ensure Admin Exists
$admin_pass = password_hash("admin@Healcare", PASSWORD_DEFAULT);
$check_admin = $conn->query("SELECT * FROM users WHERE email = 'admin@gmail.com'");
if ($check_admin->num_rows == 0) {
    $conn->query("INSERT INTO registrations (name, email, phone, password, user_type, status) VALUES ('Administrator', 'admin@gmail.com', '0000000000', '$admin_pass', 'admin', 'Approved')");
    $reg_id = $conn->insert_id;
    $conn->query("INSERT INTO users (registration_id, username, email, password, role, status) VALUES ($reg_id, 'admin', 'admin@gmail.com', '$admin_pass', 'admin', 'Active')");
    echo "Admin created.\n";
}

// 3. Add Doctors if table empty
$check_docs = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc();
if ($check_docs['count'] == 0) {
    include 'add_req_doctors.php';
    echo "Doctors seeded.\n";
}

// 4. Ensure extra columns
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 200.00 AFTER queue_number");

$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "Restore complete.\n";
?>
