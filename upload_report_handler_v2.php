<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    $title = mysqli_real_escape_string($conn, $_POST['report_title']);
    $report_type = mysqli_real_escape_string($conn, $_POST['report_type']);
    $report_date = $_POST['report_date'] ?? date('Y-m-d');
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');

    // Map staff sub-roles
    $staff_type = '';
    if ($role == 'staff') {
        $c = $conn->query("SELECT 'nurse' as t FROM nurses WHERE user_id=$user_id 
                          UNION SELECT 'lab_staff' FROM lab_staff WHERE user_id=$user_id 
                          UNION SELECT 'pharmacist' FROM pharmacists WHERE user_id=$user_id 
                          UNION SELECT 'receptionist' FROM receptionists WHERE user_id=$user_id 
                          UNION SELECT 'canteen_staff' FROM canteen_staff WHERE user_id=$user_id");
        if($c && $r = $c->fetch_assoc()) {
            $staff_type = $r['t'];
            $role = $staff_type;
        }
    }

    // Define role-specific report categories
    $report_categories = [
        'canteen_staff' => 'Canteen Revenue',
        'lab_staff' => 'Laboratory Revenue',
        'pharmacist' => 'Pharmacy Sales',
        'receptionist' => 'Appointment & Patient Visit',
        'nurse' => 'Department Revenue',
        'doctor' => 'Consultation Revenue',
        'admin' => 'Administrative'
    ];

    $report_category = $report_categories[$role] ?? 'General';
    $department = $report_category;

    // File handling
    $target_dir = "uploads/reports/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_extension = strtolower(pathinfo($_FILES["report_file"]["name"], PATHINFO_EXTENSION));
    
    if ($file_extension != "pdf") {
        echo "<script>alert('Only PDF files are allowed.'); window.history.back();</script>";
        exit();
    }

    $file_size = $_FILES["report_file"]["size"];
    $file_name = "REP_" . strtoupper($role) . "_" . date('Ymd') . "_" . time() . ".pdf";
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
        // Save to DB
        $stmt = $conn->prepare("INSERT INTO manual_reports 
            (user_id, user_role, report_type, report_category, department, report_title, file_path, report_date, file_size, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted')");
        $stmt->bind_param("isssssssi", $user_id, $role, $report_type, $report_category, $department, $title, $target_file, $report_date, $file_size);
        
        if ($stmt->execute()) {
            $report_id = $conn->insert_id;
            
            // Optional: Extract basic metrics from PDF (placeholder for future enhancement)
            // You can integrate PDF parsing libraries here
            
            // Redirect based on role
            $redirect_map = [
                'canteen_staff' => 'staff_canteen_staff_dashboard.php',
                'lab_staff' => 'staff_lab_staff_dashboard.php',
                'pharmacist' => 'staff_pharmacist_dashboard.php',
                'receptionist' => 'staff_receptionist_dashboard.php',
                'nurse' => 'staff_nurse_dashboard.php',
                'doctor' => 'doctor_dashboard.php',
                'admin' => 'admin_dashboard.php?section=reports'
            ];
            
            $redirect_url = (!empty($_POST['redirect_url'])) ? $_POST['redirect_url'] : ($redirect_map[$role] ?? 'reports_manager.php?view=repository');
            
            echo "<script>
                alert('Report uploaded successfully! Report ID: #$report_id');
                window.location.href='$redirect_url';
            </script>";
        } else {
            echo "<script>alert('Database error: " . $conn->error . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('File upload failed.'); window.history.back();</script>";
    }
} else {
    header("Location: reports_manager.php");
}
?>
