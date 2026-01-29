<?php
ob_start();
session_start();
include 'includes/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

// Fetch bill data
$bill_id = isset($_REQUEST['bill_id']) ? (int)$_REQUEST['bill_id'] : 0;
$bill = null;
$patient_name = "Valued Patient";
$service_name = "Service";

if ($bill_id > 0) {
    $res = $conn->query("SELECT b.*, r.name as patient_name 
                         FROM billing b 
                         JOIN users u ON b.patient_id = u.user_id 
                         JOIN registrations r ON u.registration_id = r.registration_id 
                         WHERE b.bill_id = $bill_id");
    if ($res && $res->num_rows > 0) {
        $bill = $res->fetch_assoc();
        $patient_name = $bill['patient_name'];
        $service_name = ($bill['bill_type'] == 'Canteen') ? 'Canteen Order' : 'Consultation';
    }
}

// Payment Processing Logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $debug_log_file = 'c:/xampp/htdocs/HealCare/email_debug.txt';
    file_put_contents($debug_log_file, "------------------------------------------------\n", FILE_APPEND);
    file_put_contents($debug_log_file, date('Y-m-d H:i:s') . " - POST Request Received\n", FILE_APPEND);
    
    if (!$bill) {
        file_put_contents($debug_log_file, "ERROR: Invalid Invoice ID\n", FILE_APPEND);
        die("Invalid Invoice ID.");
    }

    $amount = $_POST['amount'];
    $patient_id = $_POST['patient_id'];
    $bill_type = trim($_POST['bill_type']);
    
    file_put_contents($debug_log_file, "Bill Type: [$bill_type]\n", FILE_APPEND);
    file_put_contents($debug_log_file, "Bill ID: [$bill_id]\n", FILE_APPEND);
    file_put_contents($debug_log_file, "Patient ID: [$patient_id]\n", FILE_APPEND);
    
    // Check for Razorpay Success
    if (isset($_POST['razorpay_payment_id'])) {
        $method = 'Razorpay';
        $transaction_id = $_POST['razorpay_payment_id'];
    } else {
        // Fallback or other methods
        $method = $_POST['payment_method'] ?? 'Unknown';
        $transaction_id = "TXN-" . rand(100000, 999999);
    }
    
    // Fake server-side verification for demo:
    // In production, verify signature with Razorpay Secret.

    $conn->begin_transaction();

    try {
        // 1. Update Billing Table
        $stmt = $conn->prepare("UPDATE billing SET payment_mode = ?, payment_status = 'Paid' WHERE bill_id = ?");
        $stmt->bind_param("si", $method, $bill_id);
        $stmt->execute();

        // 2. Handle Specifics based on Type
        if ($bill_type == 'Consultation') {
            $appt_id = $bill['appointment_id'];
            if ($appt_id) {
                $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'Paid' WHERE appointment_id = ?");
                $stmt->bind_param("i", $appt_id);
                $stmt->execute();
            }
            $redirect_url = "booking_success.php?booking_id=BK-$appt_id&paid=true&txn=$transaction_id";

            // --- SEND CONFIRMATION EMAIL (MOVED FROM PROCESS_BOOKING) ---
            file_put_contents('email_debug.log', date('Y-m-d H:i:s') . " - Starting Email Logic for Appt: $appt_id\n", FILE_APPEND);
            try {
                // 1. Get Appointment Details
                $stmt_a = $conn->prepare("SELECT appointment_date, appointment_time, queue_number, doctor_id, patient_id FROM appointments WHERE appointment_id = ?");
                $stmt_a->bind_param("i", $appt_id);
                $stmt_a->execute();
                $res_a = $stmt_a->get_result();
                
                if ($res_a->num_rows > 0) {
                    $appt_row = $res_a->fetch_assoc();
                    $date_str = $appt_row['appointment_date'];
                    $token_num = $appt_row['queue_number'];
                    $doc_id = $appt_row['doctor_id'];
                    $pat_id = $appt_row['patient_id'];
                    
                    file_put_contents('email_debug.log', date('Y-m-d H:i:s') . " - Found Appt Data. PatID: $pat_id, DocID: $doc_id\n", FILE_APPEND);

                    // 2. Get Doctor Name
                    $doc_name = "Doctor";
                    $q_doc = $conn->query("SELECT name FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE u.user_id = $doc_id");
                    if ($q_doc && $q_doc->num_rows > 0) {
                        $doc_name = $q_doc->fetch_assoc()['name'];
                    }

                    // 3. Get Patient Details (Name & Email)
                    $pat_name = "Patient";
                    $pat_email = "";
                    
                    $q_pp = $conn->query("SELECT name FROM patient_profiles WHERE user_id = $pat_id");
                    if ($q_pp && $q_pp->num_rows > 0) {
                        $pat_name = $q_pp->fetch_assoc()['name'];
                    } else {
                         $q_pr = $conn->query("SELECT name FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE u.user_id = $pat_id");
                         if ($q_pr && $q_pr->num_rows > 0) $pat_name = $q_pr->fetch_assoc()['name'];
                    }

                    // Get Email from Users table
                    $q_u = $conn->query("SELECT email FROM users WHERE user_id = $pat_id");
                    if ($q_u && $q_u->num_rows > 0) {
                        $pat_email = $q_u->fetch_assoc()['email'];
                    }
                    
                    file_put_contents('email_debug.log', date('Y-m-d H:i:s') . " - Patient Email Found: [$pat_email]\n", FILE_APPEND);

                    // 4. Send Email if we have email
                    if (!empty($pat_email)) {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
                        $mail->Password   = 'yiuwcrykatkfzdwv';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;
                        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

                        $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare Hospital');
                        $mail->addAddress($pat_email, $pat_name);

                        $email_booking_number = date('Y', strtotime($date_str)) . "/" . str_pad($appt_id, 6, '0', STR_PAD_LEFT);

                        $email_body = "
                        <p>Greetings from HealCare Hospital</p>
                        <p>Hello <strong>" . strtoupper(htmlspecialchars($pat_name)) . "</strong>,</p>
                        <p>Your appointment with <strong>" . htmlspecialchars($doc_name) . "</strong> is confirmed on <strong>" . htmlspecialchars($date_str) . "</strong>.</p>
                        <p>Token Number: <strong>" . htmlspecialchars($token_num) . "</strong></p>
                        <p>The token time may change according to the emergency.</p>
                        <p>Booking number: <strong>" . $email_booking_number . "</strong></p>
                        <p>For Bookings contact us at 04828-201300, 04828-201400 or visit us at<br>
                        </p>
                        <br>
                        <p>Thank you for trusting HealCare Hospital.</p>
                        ";

                        $mail->isHTML(true);
                        $mail->Subject = 'Appointment Confirmation - HealCare Hospital';
                        $mail->Body = $email_body;
                        $mail->AltBody = strip_tags($email_body);
                        $mail->send();
                        file_put_contents('email_debug.log', date('Y-m-d H:i:s') . " - Email SENT Successfully.\n", FILE_APPEND);
                    } else {
                        file_put_contents('email_debug.log', date('Y-m-d H:i:s') . " - SKIPPING: No Email Found for Pat ID $pat_id\n", FILE_APPEND);
                    }
                } else {
                    file_put_contents('email_debug.log', date('Y-m-d H:i:s') . " - ERROR: Appointment ID $appt_id not found in DB.\n", FILE_APPEND);
                }
            } catch (Exception $e) {
                file_put_contents('email_debug.log', date('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . " | " . $mail->ErrorInfo . "\n", FILE_APPEND);
            }
            // ------------------------------------------------------------
            // ------------------------------------------------------------
        } elseif ($bill_type == 'Canteen') {
            // Update Canteen Orders to 'Placed' for this patient for today that were pending payment
            // Note: This is a simplification. Ideally, we'd link orders nicely.
            $stmt = $conn->prepare("UPDATE canteen_orders SET order_status = 'Placed' WHERE patient_id = ? AND order_status = 'Pending Payment' AND order_date = CURDATE()");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            $redirect_url = "canteen.php?msg=Payment+Successful!+Your+order+is+being+prepared.";
        } elseif (strpos($bill_type, 'Lab Test') === 0) {
            // Update Lab Test Payment Status
            $stmt = $conn->prepare("UPDATE lab_tests SET payment_status = 'Paid' WHERE appointment_id = ?");
            // Use appointment_id to link bill to lab request
            $stmt->bind_param("i", $bill['appointment_id']);
            $stmt->execute();
            
            $redirect_url = "patient_dashboard.php?msg=Lab+Test+Payment+Successful";
        } else {
            // Generic fallback
            $redirect_url = "patient_dashboard.php?msg=Payment+Successful";
        }

        // 3. Record Payment Log
        $payment_date = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO payments (bill_id, patient_id, payment_date, payment_method, payment_amount, payment_status, transaction_id) VALUES (?, ?, ?, ?, ?, 'Success', ?)");
        $stmt->bind_param("iissds", $bill_id, $patient_id, $payment_date, $method, $amount, $transaction_id);
        $stmt->execute();

        $conn->commit();

        // Redirect
        header("Location: " . $redirect_url);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "<div style='color:red; text-align:center; padding:20px;'>Payment failed: " . $e->getMessage() . "</div>";
    }
} else {
    // Render Page
    if (!$bill) {
        die("Invalid or missing Invoice ID.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .payment-container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0; color: #1e293b; }
        .bill-summary { background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; color: #64748b; }
        .summary-row.total { border-top: 1px solid #e2e8f0; padding-top: 10px; font-weight: 700; color: #0f172a; font-size: 1.1rem; }
        .razorpay-btn { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="header">
            <h2 style="color: #3b82f6;"><i class="fas fa-shield-alt"></i> HealCare PAY</h2>
            <p style="color:#64748b; font-size:14px;">Secure Payment Gateway</p>
        </div>

        <div class="bill-summary">
            <div class="summary-row"><span>Patient</span> <span><?php echo htmlspecialchars($patient_name); ?></span></div>
            <div class="summary-row"><span>Service</span> <span><?php echo $service_name; ?></span></div>
            <div class="summary-row"><span>Invoice</span> <span>#INV-<?php echo str_pad($bill['bill_id'], 4, '0', STR_PAD_LEFT); ?></span></div>
            <div class="summary-row total"><span>Total Payable</span> <span>₹<?php echo number_format($bill['total_amount'], 2); ?></span></div>
        </div>

        <!-- Payment Options -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
            <div onclick="document.getElementById('rzp-button1').click()" style="padding: 20px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; transition:0.3s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <img src="https://cdn.iconscout.com/icon/free/png-256/free-razorpay-1649771-1399875.png" height="30" alt="Razorpay">
                    <div>
                        <div style="font-weight: 600; color: #0f172a;">Pay Online</div>
                        <div style="font-size: 12px; color: #64748b;">UPI, Cards, NetBanking</div>
                    </div>
                </div>
                <i class="fas fa-chevron-right" style="color: #cbd5e1;"></i>
            </div>
            <!-- Test Mode (For Dev Only) -->
            <form action="payment_process.php" method="POST" id="testForm">
                <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                <input type="hidden" name="amount" value="<?php echo $bill['total_amount']; ?>">
                <input type="hidden" name="patient_id" value="<?php echo $bill['patient_id']; ?>">
                <input type="hidden" name="bill_type" value="<?php echo $bill['bill_type']; ?>">
                <input type="hidden" name="payment_method" value="UPI"> <!-- Simulate UPI -->
                <input type="hidden" name="razorpay_payment_id" value="pay_TestMode<?php echo rand(10000,99999); ?>">
                
                <div onclick="document.getElementById('testForm').submit();" style="padding: 20px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; background: #fffbe6; transition:0.3s;" onmouseover="this.style.background='#fff7c2'" onmouseout="this.style.background='#fffbe6'">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 30px; height: 30px; background: #f59e0b; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;"><i class="fas fa-bug"></i></div>
                        <div>
                            <div style="font-weight: 600; color: #b45309;">Test Payment Success</div>
                            <div style="font-size: 12px; color: #d97706;">Simulate successful Razorpay callback</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #d97706;"></i>
                </div>
            </form>

            <form action="payment_process.php" method="POST" id="cashForm">
                <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                <input type="hidden" name="amount" value="<?php echo $bill['total_amount']; ?>">
                <input type="hidden" name="patient_id" value="<?php echo $bill['patient_id']; ?>">
                <input type="hidden" name="bill_type" value="<?php echo $bill['bill_type']; ?>">
                <input type="hidden" name="payment_method" value="Cash">
                
                <div onclick="if(confirm('Confirm payment at hospital counter?')) document.getElementById('cashForm').submit();" style="padding: 20px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition:0.3s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 30px; height: 30px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b;"><i class="fas fa-money-bill-wave"></i></div>
                        <div>
                            <div style="font-weight: 600; color: #0f172a;">Pay at Counter</div>
                            <div style="font-size: 12px; color: #64748b;">Cash payment at hospital</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #cbd5e1;"></i>
                </div>
            </form>
        </div>

        <!-- Actual Razorpay Form (Hidden) -->
        <form action="payment_process.php" method="POST" id="razorpayForm">
            <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $bill['total_amount']; ?>">
            <input type="hidden" name="patient_id" value="<?php echo $bill['patient_id']; ?>">
            <input type="hidden" name="bill_type" value="<?php echo $bill['bill_type']; ?>">
            <!-- Razorpay Script -->
            <script
                src="https://checkout.razorpay.com/v1/checkout.js"
                data-key="rzp_test_PL1234567890MO" 
                data-amount="<?php echo $bill['total_amount'] * 100; ?>" 
                data-currency="INR"
                data-id="<?php echo 'OID' . rand(1000, 9999); ?>"
                data-buttontext="Pay Now"
                data-name="HealCare Hospital"
                data-description="Payment for <?php echo $service_name; ?>"
                data-image="assets/images/logo.png"
                data-prefill.name="<?php echo htmlspecialchars($patient_name); ?>"
                data-prefill.email="patient@example.com"
                data-theme.color="#3b82f6"
            ></script>
            <input type="hidden" custom="Hidden Element" name="hidden">
        </form>

        <!-- Hide default button and trigger via custom UI -->
        <style>
            .razorpay-payment-button { display: none; } 
        </style>
        <button id="rzp-button1" style="display:none;" onclick="document.querySelector('.razorpay-payment-button').click();">Pay</button>

        <p style="text-align: center; margin-top: 30px; font-size: 12px; color: #94a3b8;">
            <i class="fas fa-lock"></i> Secured by Razorpay
        </p>
    </div>
</body>
</html>
