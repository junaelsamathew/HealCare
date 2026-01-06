-- =====================================================
-- HEALCARE DATABASE - TABLE CREATION SCRIPT
-- =====================================================
-- This script creates all necessary tables for the HealCare system
-- Run this in phpMyAdmin or MySQL command line

-- Use the healcare database
USE healcare;

-- =====================================================
-- 1. APPOINTMENT TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    appointment_type VARCHAR(20) DEFAULT 'Walk-in',
    status ENUM('Requested', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Requested',
    queue_number INT DEFAULT NULL,
    payment_status VARCHAR(20) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT DEFAULT NULL,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. PRESCRIPTION TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    prescription_date DATE NOT NULL,
    medicine_details TEXT NOT NULL,
    dosage TEXT DEFAULT NULL,
    duration VARCHAR(50) DEFAULT NULL,
    instructions TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_prescription_date (prescription_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. MEDICAL RECORD TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    diagnosis TEXT DEFAULT NULL,
    treatment TEXT DEFAULT NULL,
    prescription_id INT DEFAULT NULL,
    lab_test_required VARCHAR(10) DEFAULT 'No',
    follow_up_date DATE DEFAULT NULL,
    record_status VARCHAR(20) DEFAULT 'Open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_appointment (appointment_id),
    INDEX idx_record_status (record_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. LAB TEST TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS lab_tests (
    labtest_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_type VARCHAR(50) DEFAULT NULL COMMENT 'Blood / X-Ray / Scan / Pathology',
    sample_collected VARCHAR(10) DEFAULT 'No',
    test_date DATE DEFAULT NULL,
    result TEXT DEFAULT NULL,
    report_date DATE DEFAULT NULL,
    labstaff_id INT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Pending' COMMENT 'Pending / In Progress / Completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (labstaff_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_status (status),
    INDEX idx_test_date (test_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. BILLING TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS billing (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    bill_type VARCHAR(50) DEFAULT 'OP' COMMENT 'OP / IP / Lab / Pharmacy',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    doctor_id INT DEFAULT NULL,
    payment_mode VARCHAR(30) DEFAULT NULL COMMENT 'Cash / Card / UPI / Online',
    payment_status ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
    bill_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_appointment (appointment_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_bill_date (bill_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. PAYMENT TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    patient_id INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) DEFAULT NULL COMMENT 'Cash / Card / UPI / Net Banking / Insurance',
    payment_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('Success', 'Pending', 'Failed') DEFAULT 'Pending',
    transaction_id VARCHAR(100) DEFAULT NULL COMMENT 'Bank or payment gateway transaction reference',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES billing(bill_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_bill (bill_id),
    INDEX idx_patient (patient_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. REPORT TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    report_type VARCHAR(100) DEFAULT NULL COMMENT 'Lab / Medical / Radiology / Pathology / Prescription',
    generated_date DATE NOT NULL,
    doctor_id INT DEFAULT NULL COMMENT 'Doctor who requested/generated the report',
    lab_id INT DEFAULT NULL COMMENT 'References Lab(lab_id), if it is a lab-related report',
    diagnosis TEXT DEFAULT NULL COMMENT 'Diagnosis mentioned in the report (if applicable)',
    status VARCHAR(50) DEFAULT 'Pending' COMMENT 'Pending / Completed / Reviewed',
    report_file VARCHAR(255) DEFAULT NULL COMMENT 'File path or URL if the report is stored digitally',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_report_type (report_type),
    INDEX idx_status (status),
    INDEX idx_generated_date (generated_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. PHARMACY STOCK TABLE (for admin dashboard alerts)
-- =====================================================
CREATE TABLE IF NOT EXISTS pharmacy_stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(100) NOT NULL,
    medicine_type VARCHAR(50) DEFAULT NULL COMMENT 'Tablet / Syrup / Injection / Capsule',
    manufacturer VARCHAR(100) DEFAULT NULL,
    batch_number VARCHAR(50) DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 0,
    minimum_stock INT DEFAULT 50 COMMENT 'Minimum stock level for alerts',
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    location VARCHAR(50) DEFAULT NULL COMMENT 'Shelf location',
    last_restocked_date DATE DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_medicine_name (medicine_name),
    INDEX idx_quantity (quantity),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. CANTEEN MENU TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS canteen_menu (
    menu_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    item_category VARCHAR(50) DEFAULT NULL COMMENT 'Breakfast / Lunch / Dinner / Snacks / Beverages',
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    availability VARCHAR(20) DEFAULT 'Available' COMMENT 'Available / Out of Stock',
    diet_type VARCHAR(30) DEFAULT NULL COMMENT 'Veg / Non-Veg / Vegan / Liquid',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_category (item_category),
    INDEX idx_availability (availability)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. CANTEEN ORDERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS canteen_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT DEFAULT 1,
    order_date DATE NOT NULL,
    order_time TIME NOT NULL,
    delivery_location VARCHAR(100) DEFAULT NULL COMMENT 'Ward / Bed number',
    order_status VARCHAR(20) DEFAULT 'Pending' COMMENT 'Pending / Preparing / Delivered / Cancelled',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES canteen_menu(menu_id) ON DELETE CASCADE,
    INDEX idx_patient (patient_id),
    INDEX idx_order_date (order_date),
    INDEX idx_order_status (order_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. HEALTH PACKAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS health_packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(100) NOT NULL,
    package_description TEXT DEFAULT NULL,
    included_tests TEXT DEFAULT NULL COMMENT 'List of tests included in the package',
    original_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_percentage INT DEFAULT 0,
    discounted_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    validity_days INT DEFAULT 30 COMMENT 'Package validity in days',
    status VARCHAR(20) DEFAULT 'Active' COMMENT 'Active / Inactive',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_package_name (package_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. AMBULANCE CONTACTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS ambulance_contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    driver_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    vehicle_type VARCHAR(50) DEFAULT NULL COMMENT 'Basic / Advanced Life Support',
    availability VARCHAR(20) DEFAULT 'Available' COMMENT 'Available / On Duty / Off Duty',
    location VARCHAR(100) DEFAULT NULL COMMENT 'Current location or base location',
    emergency_level VARCHAR(20) DEFAULT 'Standard' COMMENT 'Standard / Critical',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_availability (availability),
    INDEX idx_phone_number (phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. COMPLAINT LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS complaint_logs (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    complaint_type VARCHAR(50) DEFAULT NULL COMMENT 'Service / Staff / Facility / Medical / Billing',
    complaint_subject VARCHAR(200) NOT NULL,
    complaint_description TEXT NOT NULL,
    complaint_date DATE NOT NULL,
    assigned_to INT DEFAULT NULL COMMENT 'Admin or staff member handling the complaint',
    status VARCHAR(20) DEFAULT 'Open' COMMENT 'Open / In Progress / Resolved / Closed',
    resolution TEXT DEFAULT NULL,
    resolved_date DATE DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_complaint_type (complaint_type),
    INDEX idx_status (status),
    INDEX idx_complaint_date (complaint_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT SAMPLE DATA (Optional)
-- =====================================================

-- Sample Pharmacy Stock (to show alerts)
INSERT INTO pharmacy_stock (medicine_name, medicine_type, manufacturer, quantity, minimum_stock, unit_price) VALUES
('Paracetamol 500mg', 'Tablet', 'Cipla', 25, 100, 2.50),
('Amoxicillin 250mg', 'Capsule', 'Sun Pharma', 30, 100, 5.00),
('Ibuprofen 400mg', 'Tablet', 'Dr. Reddy', 15, 50, 3.75);

-- Sample Canteen Menu Items
INSERT INTO canteen_menu (item_name, item_category, description, price, diet_type) VALUES
('Oatmeal Porridge', 'Breakfast', 'Healthy oatmeal with fruits', 80.00, 'Veg'),
('Veg Clear Soup', 'Lunch', 'Light vegetable soup', 60.00, 'Veg'),
('Brown Rice', 'Lunch', 'Steamed brown rice', 50.00, 'Veg'),
('Grilled Chicken', 'Lunch', 'Grilled chicken breast', 150.00, 'Non-Veg'),
('Fresh Fruit Juice', 'Beverages', 'Seasonal fresh juice', 40.00, 'Veg');

-- Sample Health Packages
INSERT INTO health_packages (package_name, package_description, included_tests, original_price, discount_percentage, discounted_price) VALUES
('Basic Health Checkup', 'Complete basic health screening', 'CBC, Blood Sugar, Blood Pressure, Urine Test', 1500.00, 20, 1200.00),
('Comprehensive Health Package', 'Advanced health screening with imaging', 'CBC, Lipid Profile, Kidney Function, Liver Function, ECG, X-Ray', 3500.00, 25, 2625.00),
('Diabetes Care Package', 'Complete diabetes monitoring', 'HbA1c, Fasting Sugar, Post-Prandial Sugar, Kidney Function', 2000.00, 15, 1700.00);

-- Sample Ambulance Contacts
INSERT INTO ambulance_contacts (driver_name, phone_number, vehicle_number, vehicle_type, availability) VALUES
('Rajesh Kumar', '9876543210', 'KA-01-AB-1234', 'Advanced Life Support', 'Available'),
('Suresh Babu', '9876543211', 'KA-02-CD-5678', 'Basic', 'Available'),
('Mahesh Reddy', '9876543212', 'KA-03-EF-9012', 'Advanced Life Support', 'On Duty');

-- =====================================================
-- SCRIPT COMPLETION MESSAGE
-- =====================================================
SELECT 'All tables created successfully!' AS Status;
