<?php
session_start();
include 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status']; // e.g., 'Processing'
    
    $stmt = $conn->prepare("UPDATE lab_tests SET status = ?, updated_at = NOW() WHERE labtest_id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        header("Location: staff_lab_staff_dashboard.php?section=conducted&msg=Status+Updated");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
