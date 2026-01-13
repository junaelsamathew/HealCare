<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate status
    $allowed_statuses = ['Processing', 'Pending', 'Cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        die("Invalid status");
    }

    $stmt = $conn->prepare("UPDATE lab_tests SET status = ? WHERE labtest_id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $redirect_section = ($status == 'Processing') ? 'processing' : 'dashboard';
        header("Location: staff_lab_staff_dashboard.php?section=$redirect_section&msg=status_updated");
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    header("Location: staff_lab_staff_dashboard.php");
}
?>
