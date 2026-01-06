<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Determine staff type by checking sub-tables
$staff_type = '';
$check_nurse = $conn->query("SELECT * FROM nurses WHERE user_id = $user_id");
if ($check_nurse->num_rows > 0) $staff_type = 'nurse';

$check_lab = $conn->query("SELECT * FROM lab_staff WHERE user_id = $user_id");
if ($check_lab->num_rows > 0) $staff_type = 'lab_staff';

$check_pharm = $conn->query("SELECT * FROM pharmacists WHERE user_id = $user_id");
if ($check_pharm->num_rows > 0) $staff_type = 'pharmacist';

$check_reception = $conn->query("SELECT * FROM receptionists WHERE user_id = $user_id");
if ($check_reception->num_rows > 0) $staff_type = 'receptionist';

$check_canteen = $conn->query("SELECT * FROM canteen_staff WHERE user_id = $user_id");
if ($check_canteen->num_rows > 0) $staff_type = 'canteen_staff';

// Redirect to specific dashboard
if ($staff_type) {
    header("Location: staff_{$staff_type}_dashboard.php");
} else {
    // If not found in sub-tables, maybe show a generic error or fallback
    echo "Staff profile not complete. Please contact admin.";
}
exit();
?>