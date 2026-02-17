<?php
include 'includes/db_connect.php';

echo "Setting up Insurance Module...\n";

// 1. Create Insurance Policies Table
$sql1 = "CREATE TABLE IF NOT EXISTS insurance_policies (
    policy_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    provider_name VARCHAR(255) NOT NULL,
    policy_number VARCHAR(100) NOT NULL,
    coverage_limit DECIMAL(10,2) NOT NULL,
    coverage_percentage INT NOT NULL DEFAULT 0,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    status ENUM('Active', 'Inactive', 'Expired') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
if ($conn->query($sql1) === TRUE) {
    echo "Table insurance_policies created or exists.\n";
} else {
    echo "Error creating insurance_policies: " . $conn->error . "\n";
}

// 2. Create Insurance Claims Table
$sql2 = "CREATE TABLE IF NOT EXISTS insurance_claims (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT,
    policy_id INT,
    patient_id INT,
    total_bill_amount DECIMAL(10,2),
    covered_amount DECIMAL(10,2),
    patient_payable DECIMAL(10,2),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    admin_comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (policy_id) REFERENCES insurance_policies(policy_id),
    FOREIGN KEY (patient_id) REFERENCES users(user_id)
)";
if ($conn->query($sql2) === TRUE) {
    echo "Table insurance_claims created or exists.\n";
} else {
    echo "Error creating insurance_claims: " . $conn->error . "\n";
}

// 3. Update Billing Table (Add columns)
function addColumnIfNotExists($conn, $table, $column, $definition) {
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($result->num_rows == 0) {
        if ($conn->query("ALTER TABLE $table ADD COLUMN $column $definition")) {
             echo "Added $column to $table.\n";
        } else {
             echo "Error adding $column: " . $conn->error . "\n";
        }
    } else {
        echo "Column $column already exists in $table.\n";
    }
}

addColumnIfNotExists($conn, 'billing', 'insurance_claim_id', 'INT DEFAULT NULL');
addColumnIfNotExists($conn, 'billing', 'insurance_amount', 'DECIMAL(10,2) DEFAULT 0.00');
addColumnIfNotExists($conn, 'billing', 'patient_payable_amount', 'DECIMAL(10,2) DEFAULT 0.00');

echo "Database setup complete.\n";
?>
