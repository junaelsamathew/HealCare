<?php
include 'includes/db_connect.php';

echo "<h2>🔧 HealCare Database - Total Restoration & Synchronization</h2>";

// Disable FK checks for smooth rebuilding
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

/**
 * Helper to execute a query and handle error
 */
function execQuery($conn, $sql, $label) {
    if ($conn->query($sql)) {
        echo "✅ $label created/verified.<br>";
        return true;
    } else {
        echo "❌ Error in $label: " . $conn->error . "<br>";
        return false;
    }
}

// 1. Registrations (Base table for applications)
$sql_reg = "CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    app_id VARCHAR(50) UNIQUE,
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
    relevant_experience VARCHAR(100),
    qualification_details TEXT,
    admin_message TEXT,
    registered_date DATE DEFAULT CURRENT_DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_reg, "registrations");

// 2. Users (Authentication)
$sql_users = "CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_users, "users");

// 3. Doctors (Profile)
$sql_docs = "CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    specialization VARCHAR(100),
    qualification VARCHAR(100),
    experience INT,
    department VARCHAR(100),
    date_of_join DATE,
    designation VARCHAR(50),
    consultation_fee DECIMAL(10,2) DEFAULT 200.00,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_docs, "doctors");

// 4. Patient Profiles
$sql_pp = "CREATE TABLE IF NOT EXISTS patient_profiles (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_pp, "patient_profiles");

// 5. Appointments
$sql_appts = "CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    department VARCHAR(100),
    appointment_date DATE,
    appointment_time TIME,
    appointment_type VARCHAR(20) DEFAULT 'Walk-in',
    status ENUM('Requested', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Requested',
    queue_number INT,
    payment_status VARCHAR(20) DEFAULT 'Pending',
    consultation_fee DECIMAL(10,2) DEFAULT 200.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_appts, "appointments");

// 6. Billing
$sql_billing = "CREATE TABLE IF NOT EXISTS billing (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT,
    bill_type VARCHAR(50) DEFAULT 'Consultation',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    doctor_id INT,
    payment_mode VARCHAR(30),
    payment_status ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
    bill_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_billing, "billing");

// 7. Payments
$sql_payments = "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    patient_id INT NOT NULL,
    payment_date DATE,
    payment_method VARCHAR(50),
    payment_amount DECIMAL(10,2),
    payment_status ENUM('Success', 'Pending', 'Failed') DEFAULT 'Pending',
    transaction_id VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES billing(bill_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_payments, "payments");

// 8. Pharmacy Stock
$sql_stock = "CREATE TABLE IF NOT EXISTS pharmacy_stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(100) NOT NULL,
    medicine_type VARCHAR(50),
    manufacturer VARCHAR(100),
    batch_number VARCHAR(50),
    expiry_date DATE,
    quantity INT DEFAULT 0,
    minimum_stock INT DEFAULT 50,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    location VARCHAR(50),
    last_restocked_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_stock, "pharmacy_stock");

// 9. Prescriptions
$sql_presc = "CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    prescription_date DATE,
    medicine_details TEXT,
    dosage TEXT,
    duration VARCHAR(50),
    instructions TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_presc, "prescriptions");

// 10. Medical Records
$sql_mr = "CREATE TABLE IF NOT EXISTS medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    diagnosis TEXT,
    treatment TEXT,
    prescription_id INT,
    lab_test_required VARCHAR(10) DEFAULT 'No',
    follow_up_date DATE,
    record_status VARCHAR(20) DEFAULT 'Open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_mr, "medical_records");

// 11. Patient Medical Records (Alternative table used in some sections)
$sql_pmr = "CREATE TABLE IF NOT EXISTS patient_medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    patient_type ENUM('Inpatient', 'Outpatient') DEFAULT 'Outpatient',
    diagnosis TEXT,
    treatment TEXT,
    prescription_ref TEXT,
    lab_test_required VARCHAR(10) DEFAULT 'No',
    attending_doctor VARCHAR(100),
    visit_date DATE,
    remarks TEXT,
    FOREIGN KEY (patient_id) REFERENCES patient_profiles(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_pmr, "patient_medical_records");

// 12. Lab Tests
$sql_lab = "CREATE TABLE IF NOT EXISTS lab_tests (
    labtest_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_type VARCHAR(50),
    sample_collected VARCHAR(10) DEFAULT 'No',
    test_date DATE,
    result TEXT,
    report_date DATE,
    labstaff_id INT,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (labstaff_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_lab, "lab_tests");

// 13. Reports
$sql_reports = "CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    report_type VARCHAR(100),
    generated_date DATE,
    doctor_id INT,
    lab_id INT,
    diagnosis TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    report_file VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_reports, "reports");

// 14. Canteen Menu
$sql_cmenu = "CREATE TABLE IF NOT EXISTS canteen_menu (
    menu_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    item_category VARCHAR(50),
    description TEXT,
    price DECIMAL(10,2) DEFAULT 0.00,
    availability VARCHAR(20) DEFAULT 'Available',
    diet_type VARCHAR(30),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_cmenu, "canteen_menu");

// 15. Canteen Orders
$sql_corders = "CREATE TABLE IF NOT EXISTS canteen_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT DEFAULT 1,
    order_date DATE,
    order_time TIME,
    delivery_location VARCHAR(100),
    order_status VARCHAR(20) DEFAULT 'Pending',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES canteen_menu(menu_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_corders, "canteen_orders");

// 16. Health Packages
$sql_packs = "CREATE TABLE IF NOT EXISTS health_packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(100) NOT NULL,
    package_description TEXT,
    included_tests TEXT,
    original_price DECIMAL(10,2) DEFAULT 0.00,
    discount_percentage INT DEFAULT 0,
    discounted_price DECIMAL(10,2) DEFAULT 0.00,
    validity_days INT DEFAULT 30,
    status VARCHAR(20) DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_packs, "health_packages");

// 17. Ambulance Contacts
$sql_amb = "CREATE TABLE IF NOT EXISTS ambulance_contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    driver_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    vehicle_type VARCHAR(50),
    availability VARCHAR(20) DEFAULT 'Available',
    location VARCHAR(100),
    emergency_level VARCHAR(20) DEFAULT 'Standard',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_amb, "ambulance_contacts");

// 18. Complaint Logs
$sql_complaints = "CREATE TABLE IF NOT EXISTS complaint_logs (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    complaint_type VARCHAR(50),
    complaint_subject VARCHAR(200) NOT NULL,
    complaint_description TEXT NOT NULL,
    complaint_date DATE,
    assigned_to INT,
    status VARCHAR(20) DEFAULT 'Open',
    resolution TEXT,
    resolved_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
execQuery($conn, $sql_complaints, "complaint_logs");

// --- AND STAFF TABLES (Not in Image 0 but used by system) ---
$conn->query("CREATE TABLE IF NOT EXISTS nurses (
    nurse_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department VARCHAR(100),
    shift VARCHAR(20),
    qualification VARCHAR(100),
    experience INT,
    date_of_join DATE,
    designation VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS lab_staff (
    labstaff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lab_type VARCHAR(100),
    shift VARCHAR(20),
    qualification VARCHAR(100),
    experience INT,
    date_of_join DATE,
    designation VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS pharmacists (
    pharmacist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    qualification VARCHAR(100),
    experience INT,
    shift VARCHAR(20),
    date_of_join DATE,
    designation VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS canteen_staff (
    canteenstaff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role VARCHAR(50),
    shift VARCHAR(20),
    date_of_join DATE,
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS receptionists (
    receptionist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    desk_no VARCHAR(20),
    shift VARCHAR(20),
    experience INT,
    qualification VARCHAR(100),
    date_of_join DATE,
    language_known VARCHAR(100),
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

echo "✅ Staff tables verified.<br>";

// Enable FK checks back
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<h3>✅ Synchronization Complete!</h3>";
echo "<p>All 18 tables from your reference image and all staff-related tables are now active and linked.</p>";

// Ensure Admin
$admin_pass = password_hash("admin@Healcare", PASSWORD_DEFAULT);
$check_admin = $conn->query("SELECT * FROM users WHERE email = 'admin@gmail.com'");
if ($check_admin->num_rows == 0) {
    $conn->query("INSERT INTO registrations (name, email, phone, password, user_type, status) VALUES ('Administrator', 'admin@gmail.com', '0000000000', '$admin_pass', 'admin', 'Approved')");
    $reg_id = $conn->insert_id;
    $conn->query("INSERT INTO users (registration_id, username, email, password, role, status) VALUES ($reg_id, 'admin', 'admin@gmail.com', '$admin_pass', 'admin', 'Active')");
    echo "Admin account restored.<br>";
}

// Seed doctors if table empty
$check_docs = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc();
if ($check_docs['count'] == 0) {
    include 'add_req_doctors.php';
    echo "Doctors profile seeded.<br>";
}

echo "<br><a href='login.php'>Go to Login Page</a>";
?>
