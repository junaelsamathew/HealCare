<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'staff') {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    
    // Update Lab Test Payment Status
    $stmt = $conn->prepare("UPDATE lab_tests SET payment_status = 'Paid' WHERE labtest_id = ?");
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        // Optional: Create a billing record here if needed, but for now just marking as paid as per specific request flow
        echo "Success";
        header("Location: staff_lab_staff_dashboard.php?section=dashboard"); 
    } else {
        echo "Error";
    }
}
?>
