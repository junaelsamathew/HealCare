<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

<<<<<<< HEAD
// Determine staff type
$staff_type = '';

// 1. Check registrations table as primary source of truth
$st_res = $conn->query("SELECT r.staff_type FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.user_id = $user_id");
if($st_res && $st_row = $st_res->fetch_assoc()) {
    $st = strtolower($st_row['staff_type']);
    if (strpos($st, 'nurse') !== false) $staff_type = 'nurse';
    elseif (strpos($st, 'lab') !== false) $staff_type = 'lab_staff';
    elseif (strpos($st, 'pharm') !== false) $staff_type = 'pharmacist';
    elseif (strpos($st, 'recept') !== false) $staff_type = 'receptionist';
    elseif (strpos($st, 'canteen') !== false) $staff_type = 'canteen_staff';
}

// 2. Fallback to sub-table checks if still empty
if (!$staff_type) {
    $check_nurse = $conn->query("SELECT * FROM nurses WHERE user_id = $user_id");
    if ($check_nurse->num_rows > 0) $staff_type = 'nurse';
    
    if (!$staff_type) {
        $check_lab = $conn->query("SELECT * FROM lab_staff WHERE user_id = $user_id");
        if ($check_lab->num_rows > 0) $staff_type = 'lab_staff';
    }
    
    if (!$staff_type) {
        $check_pharm = $conn->query("SELECT * FROM pharmacists WHERE user_id = $user_id");
        if ($check_pharm->num_rows > 0) $staff_type = 'pharmacist';
    }
    
    if (!$staff_type) {
        $check_reception = $conn->query("SELECT * FROM receptionists WHERE user_id = $user_id");
        if ($check_reception->num_rows > 0) $staff_type = 'receptionist';
    }
    
    if (!$staff_type) {
        $check_canteen = $conn->query("SELECT * FROM canteen_staff WHERE user_id = $user_id");
        if ($check_canteen->num_rows > 0) $staff_type = 'canteen_staff';
    }
}
=======
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
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299

// Redirect to specific dashboard
if ($staff_type) {
    header("Location: staff_{$staff_type}_dashboard.php");
} else {
    // If not found in sub-tables, maybe show a generic error or fallback
    echo "Staff profile not complete. Please contact admin.";
}
exit();
?>