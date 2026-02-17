<?php
include 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS nursing_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT NOT NULL,
    note_type VARCHAR(50) NOT NULL,
    priority VARCHAR(20) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    status VARCHAR(20) DEFAULT 'active'
)";

if ($conn->query($sql) === TRUE) {
    echo "Table nursing_notes created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
