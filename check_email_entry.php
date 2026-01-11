<?php
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['email'])) {
    echo json_encode(['error' => 'No email provided']);
    exit;
}

$email = trim($_GET['email']);
$email = mysqli_real_escape_string($conn, $email);

// Check in users table
$check_users = $conn->query("SELECT user_id FROM users WHERE email = '$email'");
$user_exists = $check_users->num_rows > 0;

// Check in registrations table
$check_regs = $conn->query("SELECT registration_id FROM registrations WHERE email = '$email'");
$reg_exists = $check_regs->num_rows > 0;

echo json_encode([
    'exists' => ($user_exists || $reg_exists),
    'email' => $email
]);
?>
