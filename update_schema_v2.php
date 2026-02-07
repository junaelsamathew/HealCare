<?php
include 'includes/db_connect.php';
$conn->query("ALTER TABLE appointments ADD COLUMN referred_by INT NULL");
// Also ensure notifications table exists
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50),
    icon VARCHAR(50),
    color VARCHAR(20),
    title VARCHAR(100),
    message TEXT,
    url VARCHAR(255),
    priority VARCHAR(10),
    unread TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "Schema updated.";
?>
