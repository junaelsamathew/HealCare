<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    $role = $_POST['role'];
    $email = $_POST['email'];
    
    // Simulate Authentication
    $_SESSION['user_role'] = $role;
    $_SESSION['user_email'] = $email;
    $_SESSION['logged_in'] = true;

    // Redirect to Dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // If accessed directly, go back to login
    header("Location: login.php");
    exit();
}
