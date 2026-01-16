<?php
include 'includes/db_connect.php';

// 1. Create lab_staff table
$sql = "CREATE TABLE IF NOT EXISTS lab_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lab_type VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'lab_staff' created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// 2. Assign existing staff to 'Blood / Pathology Lab' (Default)
// Find users with role 'staff' who are not in lab_staff
$sql_staff = "
    SELECT u.user_id 
    FROM users u 
    JOIN registrations r ON u.registration_id = r.registration_id 
    WHERE r.role = 'staff' 
    AND u.user_id NOT IN (SELECT user_id FROM lab_staff)
";
$res = $conn->query($sql_staff);

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $uid = $row['user_id'];
        // Assign to Blood Lab for now
        $type = 'Blood / Pathology Lab'; 
        $conn->query("INSERT INTO lab_staff (user_id, lab_type) VALUES ($uid, '$type')");
        echo "Assigned User ID $uid to $type.\n";
    }
} else {
    echo "No unassigned staff found.\n";
}

// 3. Ensure billing table has correct columns (just a check, not doing anything)
?>
