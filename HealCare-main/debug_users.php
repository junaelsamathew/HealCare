<?php
include 'includes/db_connect.php';

echo "--- Users Table Content ---\n";
$res = $conn->query("SELECT user_id, username, email, role, status FROM users");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "ID: {$row['user_id']} | Username: {$row['username']} | Email: {$row['email']} | Role: {$row['role']} | Status: {$row['status']}\n";
    }
} else {
    echo "Error: " . $conn->error;
}

echo "\n--- Registrations Table Content (Recent) ---\n";
$res = $conn->query("SELECT registration_id, name, email, user_type, status FROM registrations ORDER BY registration_id DESC LIMIT 10");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "ID: {$row['registration_id']} | Name: {$row['name']} | Email: {$row['email']} | Type: {$row['user_type']} | Status: {$row['status']}\n";
    }
}
?>
