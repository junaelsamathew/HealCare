<?php
session_start();
include 'includes/db_connect.php';

// --- RAZORPAY CONFIG ---
$key_secret = "2ISLOGjYRAekJBSbyBEiJt6V";
// -----------------------

if (!isset($_POST['razorpay_payment_id']) || !isset($_POST['razorpay_order_id']) || !isset($_POST['razorpay_signature'])) {
    die("Invalid Access: Missing Payment Parameters");
}

$bill_id = intval($_POST['bill_id']);
$payment_id = $_POST['razorpay_payment_id'];
$order_id = $_POST['razorpay_order_id'];
$signature = $_POST['razorpay_signature'];

// 1. Verify Signature (HMAC SHA256)
// Formula: hmac_sha256(order_id + "|" + payment_id, secret)
$generated_signature = hash_hmac('sha256', $order_id . "|" . $payment_id, $key_secret);

if ($generated_signature == $signature) {
    // === VALID PAYMENT: PROCESS ORDER ===
    
    $conn->begin_transaction();

    try {
        // 1. Update Bill Status
        $stmt = $conn->prepare("UPDATE billing SET payment_status = 'Paid', payment_method = 'Online (Razorpay)', transaction_ref = ? WHERE bill_id = ?");
        $stmt->bind_param("si", $payment_id, $bill_id);
        $stmt->execute();
    
        // 2. Update Appointment Status if linked
        $check = $conn->query("SELECT appointment_id, bill_type FROM billing WHERE bill_id = $bill_id");
        if ($check->num_rows > 0) {
            $bill_data = $check->fetch_assoc();
            $appt_id = $bill_data['appointment_id'];

            if ($appt_id) {
                 // Mark appointment as Confirmed
                 $conn->query("UPDATE appointments SET status = 'Confirmed' WHERE appointment_id = $appt_id AND status = 'Pending'");
            }

            // 3. Handle Inpatient/Pharmacy/Lab Redirection
            if ($bill_data['bill_type'] == 'Inpatient Final' || strpos($bill_data['bill_type'], 'Pharmacy') !== false || strpos($bill_data['bill_type'], 'Clinic Bill') !== false || strpos($bill_data['bill_type'], 'Lab') !== false || strpos($bill_data['bill_type'], 'Medicine') !== false) {
                
                // If it's a pharmacy/complete bill, update prescription status to 'Ready'
                $ref_id = $bill_data['reference_id'] ?? null;
                if ($ref_id && (strpos($bill_data['bill_type'], 'Pharmacy') !== false || strpos($bill_data['bill_type'], 'Clinic Bill') !== false)) {
                    $conn->query("UPDATE prescriptions SET status = 'Awaiting Payment' WHERE prescription_id = $ref_id");
                }
                
                $conn->commit();
                header("Location: billing.php?msg=payment_success&txn=$payment_id");
                exit();
            }
        }
    
        $conn->commit();
    
        // 4. Redirect to Success Page
        // Re-using the logic from test_payment_success to find details
        $res = $conn->query("SELECT b.*, a.queue_number, a.appointment_date, a.appointment_time, 
                             ud.username as doc_name, COALESCE(pp.name, rp.name, p.username) as pat_name
                             FROM billing b 
                             LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
                             LEFT JOIN users ud ON b.doctor_id = ud.user_id
                             LEFT JOIN users p ON b.patient_id = p.user_id
                             LEFT JOIN patient_profiles pp ON b.patient_id = pp.user_id
                             LEFT JOIN registrations rp ON p.registration_id = rp.registration_id
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
            // Generall Fallback
            header("Location: billing.php?msg=payment_success&txn=$payment_id");
        }

    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing internal order: " . $e->getMessage());
    }

} else {
    // === INVALID SIGNATURE ===
    die("Payment Verification Failed! Security Check Not Passed.");
}
?>
