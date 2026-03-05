<?php
session_start();

// Intercept requests across the entire application and enforce login

// Exclude certain pages from this check to prevent infinite loops and allow public access
$current_page = basename($_SERVER['PHP_SELF']);
$excluded_pages = [
    'login.php', 
    'logout.php', 
    'signup.php', 
    'auth_handler.php', 
    'verify_code.php', 
    'index.php', 
    'home.php', 
    'about.php', 
    'contact.php', 
    'services.php'
];

if (!in_array($current_page, $excluded_pages)) {
    // If user is not logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Save the requested URL
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login
        header("Location: login.php");
        exit();
    }
}
?>
