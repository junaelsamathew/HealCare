<?php
include 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS patient_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    experience VARCHAR(50),
    doctor_rating VARCHAR(50),
    cleanliness INT,
    staff_response VARCHAR(50),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table patient_feedback created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
