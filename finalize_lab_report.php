<?php
session_start();
include 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $result_summary = $_POST['result_summary'];
    
    // Handle File Upload
    $report_path = null;
    if (isset($_FILES['report_pdf']) && $_FILES['report_pdf']['error'] == 0) {
        $target_dir = "uploads/lab_reports/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = "Report_" . $order_id . "_" . time() . ".pdf";
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES['report_pdf']['tmp_name'], $target_file)) {
            $report_path = $target_file;
        }
    }

    // Update Order Status
    $stmt = $conn->prepare("UPDATE lab_orders SET result_summary = ?, report_path = ?, order_status = 'Completed' WHERE order_id = ?");
    $stmt->bind_param("ssi", $result_summary, $report_path, $order_id);
    
    if ($stmt->execute()) {
        header("Location: staff_lab_staff_dashboard.php?msg=report_submitted");
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    header("Location: staff_lab_staff_dashboard.php");
}
?>
