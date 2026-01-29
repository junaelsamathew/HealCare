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
        // Create a billing record so patient can see the receipt
        $q_get = $conn->query("SELECT l.*, r.name as doctor_name 
                               FROM lab_tests l 
                               LEFT JOIN users u ON l.doctor_id = u.user_id 
                               LEFT JOIN registrations r ON u.registration_id = r.registration_id 
                               WHERE l.labtest_id = $order_id");
        if ($q_get && $q_get->num_rows > 0) {
            $lab = $q_get->fetch_assoc();
            $patient_id = $lab['patient_id'];
            $doctor_id = $lab['doctor_id'];
            $appt_id = $lab['appointment_id'];
            $test_name = $lab['test_name'];
            $cat_id = $lab['category_id'];
            
            // Try to fetch exact price from catalog
            $cost = 500; // Default flat fee
            $stmt_price = $conn->prepare("SELECT test_price FROM lab_test_catalog WHERE category_id = ? AND test_name = ? LIMIT 1");
            $stmt_price->bind_param("is", $cat_id, $test_name);
            $stmt_price->execute();
            $res_price = $stmt_price->get_result();
            if ($p_row = $res_price->fetch_assoc()) {
                $cost = $p_row['test_price'];
            } else {
                // If the test name from lab_tests contains multiple comma-separated tests, calculate sum
                $test_array = explode(', ', $test_name);
                if (count($test_array) > 1) {
                    $total_sum = 0;
                    foreach ($test_array as $t_part) {
                        $stmt_p = $conn->prepare("SELECT test_price FROM lab_test_catalog WHERE category_id = ? AND test_name = ? LIMIT 1");
                        $stmt_p->bind_param("is", $cat_id, $t_part);
                        $stmt_p->execute();
                        $rp = $stmt_p->get_result();
                        if ($rr = $rp->fetch_assoc()) $total_sum += $rr['test_price'];
                        else $total_sum += 500;
                    }
                    $cost = $total_sum;
                }
            }
            
            $bill_type = "Lab Test: " . $test_name;
            $bill_date = date('Y-m-d');
            
            $stmt_bill = $conn->prepare("INSERT INTO billing (patient_id, doctor_id, appointment_id, reference_id, bill_type, total_amount, payment_status, payment_mode, bill_date) VALUES (?, ?, ?, ?, ?, ?, 'Paid', 'Cash/QR Offline', ?)");
            $stmt_bill->bind_param("iiiiisd", $patient_id, $doctor_id, $appt_id, $order_id, $bill_type, $cost, $bill_date);
            $stmt_bill->execute();
        }

        echo "Success";
        header("Location: staff_lab_staff_dashboard.php?section=dashboard&msg=Lab+Marked+as+Paid"); 
    } else {
        echo "Error";
    }
}
?>
