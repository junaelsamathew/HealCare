<?php
session_start();
include 'includes/db_connect.php';

// Find an admin user
$res = $conn->query("SELECT user_id, username FROM users WHERE role = 'admin' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $admin = $res->fetch_assoc();
    $_SESSION['user_id'] = $admin['user_id'];
    $_SESSION['username'] = $admin['username'];
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Admin Debug';
    echo "Admin session set successfully for " . $admin['username'] . ". Redirecting to dashboard...";
    header("Refresh: 2; url=admin_dashboard.php");
} else {
    echo "No admin user found in database.";
}
?>
