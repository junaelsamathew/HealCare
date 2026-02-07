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
                 // Mark appointment as Requested
                 $conn->query("UPDATE appointments SET status = 'Requested' WHERE appointment_id = $appt_id AND status = 'Pending'");
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

            if ($bill_data['bill_type'] == 'Health Package') {
                $conn->commit();
                header("Location: patient_dashboard.php?msg=package_booked&txn=$payment_id");
                exit();
            }
        }
    
        $conn->commit();
    
        // 4. Redirect to Success Page
        // Re-using the logic from test_payment_success to find details
        $res = $conn->query("SELECT b.*, a.queue_number, a.appointment_date, a.appointment_time, 
                             COALESCE(rd.name, ud.username) as doc_name, 
                             COALESCE(pp.name, rp.name, p.username) as pat_name, p.email
                             FROM billing b 
                             LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
                             LEFT JOIN users ud ON b.doctor_id = ud.user_id
                             LEFT JOIN registrations rd ON ud.registration_id = rd.registration_id
                             LEFT JOIN users p ON b.patient_id = p.user_id
                             LEFT JOIN patient_profiles pp ON b.patient_id = pp.user_id
                             LEFT JOIN registrations rp ON p.registration_id = rp.registration_id
                             WHERE b.bill_id = $bill_id");
                             
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $bk_id = "BK-" . $row['appointment_id'];
            $token = $row['queue_number'] ?? '00';
            $raw_doc_p = $row['doc_name'];
            $doc = (stripos($raw_doc_p, 'Dr.') === 0) ? $raw_doc_p : 'Dr. ' . $raw_doc_p;
            $date = $row['appointment_date'];
            $time = $row['appointment_time'];
            $pat = $row['pat_name'];
            $fee = $row['total_amount'];
            $pat_email = $row['email'];

            // --- SEND CONFIRMATION EMAIL ---
            if (!empty($pat_email)) {
                require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
                require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
                require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

                try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
                $mail->Password   = 'yiuwcrykatkfzdwv';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

                $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare Hospital');
                $mail->addAddress($pat_email, $pat);

                $email_booking_number = date('Y', strtotime($date)) . "/" . str_pad($row['appointment_id'], 6, '0', STR_PAD_LEFT);
                $display_date = date('d M, Y', strtotime($date));
                $display_time = date('h:i A', strtotime($time));

                $email_body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; color: #1e293b;'>
                    <div style='background: #3b82f6; padding: 30px; text-align: center;'>
                        <h1 style='color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px;'>+ HEALCARE</h1>
                        <p style='color: #d1e1ff; margin: 10px 0 0; font-size: 14px;'>Medical Excellence & Compassionate Care</p>
                    </div>
                    <div style='padding: 40px 30px;'>
                        <h2 style='color: #0f172a; font-size: 20px; margin-bottom: 20px;'>Appointment Confirmed</h2>
                        <p style='font-size: 16px; line-height: 1.5;'>Hello <strong style='color: #3b82f6;'>" . strtoupper(htmlspecialchars($pat)) . "</strong>,</p>
                        <p style='font-size: 15px; color: #64748b; margin-bottom: 30px;'>Your appointment has been successfully scheduled. Please find your booking details below:</p>
                        
                        <div style='background: #f8fafc; border-radius: 12px; padding: 25px; border: 1px solid #f1f5f9;'>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 10px 0; color: #94a3b8; font-size: 13px; text-transform: uppercase;'>Booking ID</td>
                                    <td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>$bk_id</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px 0; color: #94a3b8; font-size: 13px; text-transform: uppercase;'>Doctor</td>
                                    <td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>" . htmlspecialchars($doc) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px 0; color: #94a3b8; font-size: 13px; text-transform: uppercase;'>Date & Time</td>
                                    <td style='padding: 10px 0; text-align: right; font-weight: 700; color: #0f172a;'>$display_date at $display_time</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px 0; color: #94a3b8; font-size: 13px; text-transform: uppercase;'>Token Number</td>
                                    <td style='padding: 10px 0; text-align: right; font-weight: 800; color: #3b82f6; font-size: 22px;'>#$token</td>
                                </tr>
                            </table>
                        </div>

                        <div style='margin-top: 30px; padding: 15px; background: #fffbeb; border-radius: 8px; border: 1px solid #fef3c7;'>
                            <p style='margin: 0; font-size: 13px; color: #b45309;'>
                                <i style='margin-right: 5px;'>&#9432;</i> The token time is an estimate and may vary based on emergency cases.
                            </p>
                        </div>

                        <div style='margin-top: 40px; text-align: center;'>
                            <p style='font-size: 14px; color: #94a3b8;'>Thank you for choosing HealCare Hospital.</p>
                        </div>
                    </div>
                    <div style='background: #f1f5f9; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0;'>
                        <p style='margin: 0; font-size: 12px; color: #64748b;'>Kanjirapally, Kottayam, Kerala - 686507</p>
                        <p style='margin: 5px 0 0; font-size: 12px; color: #64748b;'>Emergency: (+91) 953 904 5609 | Web: www.healcare.com</p>
                    </div>
                </div>
                ";

                $mail->isHTML(true);
                $mail->Subject = 'Confirmed: Appointment with ' . $doc . ' - HealCare Hospital';
                $mail->Body = $email_body;
                $mail->AltBody = "Hello $pat, Your appointment with $doc is confirmed for $display_date at $display_time. Token: $token. Booking ID: $bk_id.";
                $mail->send();
                } catch (Exception $e) { /* Log error if needed */ }
            }
            
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
