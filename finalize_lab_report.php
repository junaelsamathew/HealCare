<?php
session_start();
include 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $order_id = intval($_POST['order_id']);
    $result_summary = $_POST['result_summary'];
    $staff_id = $_SESSION['user_id'];
    
    // File Upload Handling
    $report_path = null;
    if (isset($_FILES['report_pdf']) && $_FILES['report_pdf']['error'] == 0) {
        $upload_dir = 'uploads/reports/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = "LAB_" . $order_id . "_" . time() . ".pdf";
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['report_pdf']['tmp_name'], $target_file)) {
            $report_path = $target_file;
        } else {
            echo "Error uploading file.";
            exit();
        }
    }
    
    // Update Database
    $stmt = $conn->prepare("UPDATE lab_tests SET status = 'Completed', result = ?, report_path = ?, labstaff_id = ?, report_date = CURDATE(), updated_at = NOW() WHERE labtest_id = ?");
    $stmt->bind_param("ssii", $result_summary, $report_path, $staff_id, $order_id);
    
    if ($stmt->execute()) {
        header("Location: staff_lab_staff_dashboard.php?section=completed&msg=Report+Finalized");
    } else {
        echo "Error updating record: " . $conn->error;
    }
} else {
    header("Location: index.php");
}
?>
