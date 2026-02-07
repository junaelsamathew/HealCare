<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "healcare";
$port = 3306;

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Connect to MySQL with timeout
    $conn = mysqli_init();
    if (!$conn) {
        die("mysqli_init failed");
    }
    
    // Set connection timeout to 5 seconds
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    
    // Attempt connection
    if (!$conn->real_connect($servername, $username, $password, "", $port)) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Create database if not exists (minimal impact)
    $conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
    $conn->select_db($dbname);

/*
    // Check if the 'users' table exists to decide if we need to run setup
    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($table_check->num_rows == 0) {
        // Run initial setup only if tables are missing
        include_once __DIR__ . '/db_setup_logic.php';
    }
*/
    // Auto-cancel logic: If appointment date is passed and status is still 'Confirmed' or 'Pending', cancel it.
    $today_cancel = date('Y-m-d');
    $conn->query("UPDATE appointments SET status = 'Cancelled' WHERE appointment_date < '$today_cancel' AND status IN ('Confirmed', 'Pending', 'Scheduled')");

} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>

