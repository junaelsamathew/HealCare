<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "healcare";
$port = 3306; // <--- CHANGE THIS IF XAMPP SAYS 3307 or 3308

// Connect to MySQL Server (without specifying DB yet, to create it)
$conn = new mysqli($servername, $username, $password, "", $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT,
    username VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    permissions TEXT, -- Added for role-based access control
    status VARCHAR(20) DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
)");

$conn->query("CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255), -- Now optional at application stage
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
    registered_date DATE DEFAULT CURRENT_DATE
)");

// Dynamic update for existing tables
$columns = $conn->query("SHOW COLUMNS FROM registrations");
$existing_cols = [];
while($c = $columns->fetch_assoc()) { $existing_cols[] = $c['Field']; }

$new_cols = [
    'address' => 'TEXT',
    'profile_photo' => 'VARCHAR(255)',
    'staff_type' => 'VARCHAR(50)',
    'highest_qualification' => 'VARCHAR(255)',
    'total_experience' => 'VARCHAR(100)',
    'certifications' => 'TEXT',
    'resume_path' => 'VARCHAR(255)',
    'specialization' => 'VARCHAR(100)',
    'license_number' => 'VARCHAR(100)',
    'dept_preference' => 'VARCHAR(100)',
    'designation' => 'VARCHAR(100)',
    'computer_knowledge' => 'VARCHAR(10)',
    'languages_known' => 'VARCHAR(255)',
    'front_desk_exp' => 'VARCHAR(10)',
    'food_handling' => 'VARCHAR(10)',
    'shift_preference' => 'VARCHAR(20)',
    'canteen_job_role' => 'VARCHAR(100)',
    'date_of_joining' => 'DATE',
    'relevant_experience' => 'VARCHAR(100)',
    'qualification_details' => 'TEXT',
    'additional_details' => 'TEXT',
    'app_id' => 'VARCHAR(50) UNIQUE',
    'admin_message' => 'TEXT'
];

foreach ($new_cols as $col => $type) {
    if (!in_array($col, $existing_cols)) {
        $conn->query("ALTER TABLE registrations ADD COLUMN $col $type");
    }
}

// User Table Updates
$u_columns = $conn->query("SHOW COLUMNS FROM users");
$existing_u_cols = [];
while($uc = $u_columns->fetch_assoc()) { $existing_u_cols[] = $uc['Field']; }
if (!in_array('permissions', $existing_u_cols)) {
    $conn->query("ALTER TABLE users ADD COLUMN permissions TEXT AFTER role");
}
if (!in_array('force_password_change', $existing_u_cols)) {
    $conn->query("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 0 AFTER permissions");
}


// Patient Profiles Table (Personal Details)
$conn->query("CREATE TABLE IF NOT EXISTS patient_profiles (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    patient_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(10),
    date_of_birth DATE,
    blood_group VARCHAR(10),
    phone VARCHAR(15),
    address TEXT,
    registered_date DATE DEFAULT CURRENT_DATE,
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

// Patient Medical Records Table (Medical Details)
$conn->query("CREATE TABLE IF NOT EXISTS patient_medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    patient_type ENUM('Inpatient', 'Outpatient') DEFAULT 'Outpatient',
    diagnosis TEXT,
    treatment TEXT,
    prescription_ref TEXT,
    lab_test_required VARCHAR(10) DEFAULT 'No',
    attending_doctor VARCHAR(100),
    visit_date DATE DEFAULT CURRENT_DATE,
    remarks TEXT,
    FOREIGN KEY (patient_id) REFERENCES patient_profiles(patient_id) ON DELETE CASCADE
)");


// Check if admin exists, if not create
$admin_email = "admin@gmail.com";
$admin_pass = password_hash("admin@Healcare", PASSWORD_DEFAULT);
$check_admin = $conn->query("SELECT * FROM users WHERE email='$admin_email'");
if ($check_admin->num_rows == 0) {
    $conn->query("INSERT INTO users (username, email, password, role, status) VALUES ('admin', '$admin_email', '$admin_pass', 'admin', 'Active')");
}

// Appointments Table
$conn->query("CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT, -- Can be NULL for manual walk-ins with just name
    patient_name VARCHAR(100) NOT NULL,
    doctor_id INT, 
    department VARCHAR(50),
    appointment_date DATETIME,
    reason VARCHAR(255),
    token_no VARCHAR(50),
    status VARCHAR(50) DEFAULT 'Scheduled', -- Scheduled, Checked-In, Completed, Cancelled
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

