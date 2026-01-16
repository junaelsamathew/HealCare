<?php
ob_start();
session_start();
include 'includes/db_connect.php';

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
    if (!$bill) {
        die("Invalid Invoice ID.");
    }

    $amount = $_POST['amount'];
    $patient_id = $_POST['patient_id'];
    $bill_type = $_POST['bill_type'];
    
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
        } elseif ($bill_type == 'Canteen') {
            // Update Canteen Orders to 'Placed' for this patient for today that were pending payment
            // Note: This is a simplification. Ideally, we'd link orders nicely.
            $stmt = $conn->prepare("UPDATE canteen_orders SET order_status = 'Placed' WHERE patient_id = ? AND order_status = 'Pending Payment' AND order_date = CURDATE()");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            $redirect_url = "canteen.php?msg=Payment+Successful!+Your+order+is+being+prepared.";
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
