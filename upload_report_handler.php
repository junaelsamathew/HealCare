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
    $dept = mysqli_real_escape_string($conn, $_POST['department'] ?? 'General');
    $report_date = $_POST['report_date'] ?? date('Y-m-d');

    // Handle Staff sub-roles for mapping
    if ($role == 'staff') {
        $c = $conn->query("SELECT 'nurse' as t FROM nurses WHERE user_id=$user_id UNION SELECT 'lab_staff' FROM lab_staff WHERE user_id=$user_id UNION SELECT 'pharmacist' FROM pharmacists WHERE user_id=$user_id UNION SELECT 'receptionist' FROM receptionists WHERE user_id=$user_id UNION SELECT 'canteen_staff' FROM canteen_staff WHERE user_id=$user_id");
        if($c && $r = $c->fetch_assoc()) $role = $r['t'];
    }

    // File handling
    $target_dir = "uploads/reports/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_extension = strtolower(pathinfo($_FILES["report_file"]["name"], PATHINFO_EXTENSION));
    
    if ($file_extension != "pdf") {
        echo "<script>alert('Only PDF files are allowed.'); window.history.back();</script>";
        exit();
    }

    $file_name = "REP_" . $role . "_" . time() . "_" . rand(1000, 9999) . ".pdf";
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
        // Save to DB
        $stmt = $conn->prepare("INSERT INTO manual_reports (user_id, user_role, department, report_title, file_path, report_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $_SESSION['user_id'], $role, $dept, $title, $target_file, $report_date);
        
        if ($stmt->execute()) {
            echo "<script>alert('Report uploaded successfully!'); window.location.href='reports_manager.php?view=repository';</script>";
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
