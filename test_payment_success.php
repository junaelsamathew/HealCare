<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['bill_id'])) {
    die("Invalid Request");
}

$bill_id = intval($_GET['bill_id']);

// Simulate Razorpay Callback Processing
sleep(2); // Fake processing delay

$conn->begin_transaction();

try {
    // 1. Update Bill Status
    $stmt = $conn->prepare("UPDATE billing SET payment_status = 'Paid', payment_method = 'Online (Razorpay)' WHERE bill_id = ?");
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();

    // 2. Update Appointment Status if linked
    // If it was a consultation bill, we might want to mark appointment as 'Confirmed' if it was 'Pending'
    // First get appointment ID
    $check = $conn->query("SELECT appointment_id FROM billing WHERE bill_id = $bill_id");
    if ($check->num_rows > 0) {
        $appt_id = $check->fetch_assoc()['appointment_id'];
        if ($appt_id) {
             $conn->query("UPDATE appointments SET status = 'Confirmed' WHERE appointment_id = $appt_id AND status = 'Pending'");
        }
    }

    $conn->commit();

    // Redirect back to Success Page with Paid flag
    // We need to fetch booking id or details to render success page nicely?
    // Actually booking_success.php expects parameters.
    
    // Let's forward to booking_success if it was a booking flow, or billing.php if just paying a bill.
    // For now, let's redirect to a generic success page or modify booking_success to handle bill_id only.

    // Check bill type to determine redirection
    $b_check = $conn->query("SELECT bill_type FROM billing WHERE bill_id = $bill_id");
    $b_type = ($b_check->num_rows > 0) ? $b_check->fetch_assoc()['bill_type'] : 'General';

    if ($b_type == 'Inpatient Final') {
        // For Inpatient bills, redirect to billing page directly with success message
        header("Location: billing.php?msg=payment_success&type=discharge");
        exit();
    }

    // For Appointments, Redirect to Booking Success
    $res = $conn->query("SELECT b.*, a.queue_number, a.appointment_date, a.appointment_time, 
                         ud.username as doc_name, p.name as pat_name
                         FROM billing b 
                         LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
                         LEFT JOIN users ud ON b.doctor_id = ud.user_id
                         LEFT JOIN users p ON b.patient_id = p.user_id
                         WHERE b.bill_id = $bill_id");
                         
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $bk_id = "BK-" . $row['appointment_id'];
        $token = $row['queue_number'] ?? '00';
        $doc = $row['doc_name'];
        $date = $row['appointment_date'];
        $time = $row['appointment_time'];
        $pat = $row['pat_name'];
        $fee = $row['total_amount'];
        
        header("Location: booking_success.php?booking_id=$bk_id&token=$token&doctor=$doc&date=$date&time=$time&patient=$pat&fee=$fee&paid=1");
    } else {
        header("Location: billing.php?msg=payment_success");
    }
    
} catch (Exception $e) {
    $conn->rollback();
    die("Error processing payment: " . $e->getMessage());
}
?>
