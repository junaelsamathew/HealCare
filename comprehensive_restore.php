<?php
include 'includes/db_connect.php';

echo "Starting Comprehensive Table Recovery...\n";

// 1. Run core logic
echo "Running db_setup_logic.php...\n";
include 'includes/db_setup_logic.php';

// 2. Run staff tables
echo "Running setup_staff_tables.php...\n";
// We need to bypass the 'echo' in those files if they are meant for browser, 
// but in CLI it's fine.
include 'setup_staff_tables.php';

// 3. Run reports setup
echo "Running setup_reports_db.php...\n";
include 'setup_reports_db.php';

// 4. Run doctor schedules setup
echo "Running fix_doctor_schema.php...\n";
include 'fix_doctor_schema.php';

// 5. Create doctor_leaves table
echo "Creating doctor_leaves table...\n";
$conn->query("CREATE TABLE IF NOT EXISTS doctor_leaves (
    leave_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status VARCHAR(20) DEFAULT 'Pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

// 6. Create patient_vitals table
echo "Creating patient_vitals table...\n";
$conn->query("CREATE TABLE IF NOT EXISTS patient_vitals (
    vital_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    heart_rate VARCHAR(20),
    blood_pressure_systolic VARCHAR(20),
    blood_pressure_diastolic VARCHAR(20),
    temperature VARCHAR(20),
    spo2 VARCHAR(20),
    weight VARCHAR(20),
    height VARCHAR(20),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

// 7. Create canteen_cart table (Double Check)
echo "Creating canteen_cart table...\n";
$conn->query("CREATE TABLE IF NOT EXISTS canteen_cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES canteen_menu(menu_id) ON DELETE CASCADE
)");

// 8. Ensure medical_records has the right structure (it might be different in create_tables.sql)
// patient_dashboard uses medical_records table.
// create_tables.sql defines medical_records.
// db_setup_logic defines patient_medical_records.
// We should probably ensure both or unify them. 
// For now, let's see which one is more used.

echo "Table Recovery Complete.\n";
?>
