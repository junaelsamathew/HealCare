<?php
include 'includes/db_connect.php';

// 1. Alter admissions table to support requests and additional fields
// Check if columns exist first or just run ALTER IGNORE/try-catch
$alter_sql = [
    "ALTER TABLE admissions ADD COLUMN request_date DATETIME NULL DEFAULT NULL",
    "ALTER TABLE admissions ADD COLUMN reason TEXT NULL",
    "ALTER TABLE admissions ADD COLUMN ward_type_req VARCHAR(50) NULL",
    "ALTER TABLE admissions ADD COLUMN bill_id INT NULL", 
    "ALTER TABLE admissions MODIFY COLUMN status ENUM('Pending', 'Admitted', 'Discharged') DEFAULT 'Pending'",
    "ALTER TABLE admissions MODIFY COLUMN room_id INT NULL" // Make room_id nullable for pending requests
];

foreach ($alter_sql as $sql) {
    try {
        $conn->query($sql);
    } catch (Exception $e) {
        // Ignore errors if column exists
        // echo "Notice: " . $e->getMessage() . "<br>";
    }
}

// 2. Inpatient Treatment / Daily Records
$sql_daily = "CREATE TABLE IF NOT EXISTS inpatient_treatment (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    doctor_id INT NOT NULL,
    visit_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    daily_notes TEXT,
    treatment_plan TEXT,
    FOREIGN KEY (admission_id) REFERENCES admissions(admission_id)
)";
if ($conn->query($sql_daily)) echo "inpatient_treatment table created.<br>";
else echo "Error creating inpatient_treatment: " . $conn->error . "<br>";

// 3. Discharge Summaries
$sql_dis = "CREATE TABLE IF NOT EXISTS discharge_summaries (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    discharge_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    final_diagnosis TEXT,
    summary_notes TEXT,
    advice TEXT,
    follow_up_date DATE,
    FOREIGN KEY (admission_id) REFERENCES admissions(admission_id)
)";
if ($conn->query($sql_dis)) echo "discharge_summaries table created.<br>";
else echo "Error creating discharge_summaries: " . $conn->error . "<br>";

echo "Database schema updated for Inpatient Management.";
?>
