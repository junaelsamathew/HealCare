<?php
ob_start();
session_start();
include 'includes/db_connect.php';

// Fetch bill data regardless of method if ID is present
$bill_id = isset($_REQUEST['bill_id']) ? (int)$_REQUEST['bill_id'] : 0;
$bill = null;

if ($bill_id > 0) {
    $res = $conn->query("SELECT b.*, r.name as patient_name 
                         FROM billing b 
                         JOIN users u ON b.patient_id = u.user_id 
                         JOIN registrations r ON u.registration_id = r.registration_id 
                         WHERE b.bill_id = $bill_id");
    if ($res && $res->num_rows > 0) {
        $bill = $res->fetch_assoc();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$bill) {
        die("Invalid Invoice ID.");
    }

    $method = $_POST['payment_method'];
    $amount = $_POST['amount'];
    $patient_id = $_POST['patient_id'];
    $appt_id = $_POST['appt_id'];

    $conn->begin_transaction();

    try {
        // 1. Update Billing Table
        $stmt = $conn->prepare("UPDATE billing SET payment_mode = ?, payment_status = 'Paid' WHERE bill_id = ?");
        $stmt->bind_param("si", $method, $bill_id);
        $stmt->execute();

        // 2. Update Appointment Table
        $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'Paid', status = 'Approved' WHERE appointment_id = ?");
        $stmt->bind_param("i", $appt_id);
        $stmt->execute();

        // 3. Record Payment
        $transaction_id = "TXN-" . rand(100000, 999999);
        $payment_date = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO payments (bill_id, patient_id, payment_date, payment_method, payment_amount, payment_status, transaction_id) VALUES (?, ?, ?, ?, ?, 'Success', ?)");
        $stmt->bind_param("iissds", $bill_id, $patient_id, $payment_date, $method, $amount, $transaction_id);
        $stmt->execute();

        $conn->commit();

        // Redirect back to booking success
        header("Location: booking_success.php?booking_id=BK-$appt_id&paid=true&txn=$transaction_id");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "<div style='color:red; text-align:center; padding:20px;'>Payment failed: " . $e->getMessage() . "</div>";
    }
} else {
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
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .payment-container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0; color: #1e293b; }
        .bill-summary { background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; color: #64748b; }
        .summary-row.total { border-top: 1px solid #e2e8f0; padding-top: 10px; font-weight: 700; color: #0f172a; font-size: 1.1rem; }
        .payment-methods { margin-bottom: 25px; }
        .method-option { display: flex; align-items: center; padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: 0.3s; }
        .method-option:hover { border-color: #00aeef; background: #f0f9ff; }
        .method-option input { margin-right: 15px; }
        .btn-pay { width: 100%; background: #00aeef; color: white; border: none; padding: 15px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-pay:hover { background: #008ec3; transform: translateY(-2px); }
        .cards { display: flex; gap: 10px; margin-top: 5px; }
        .cards img { height: 20px; opacity: 0.6; }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="header">
            <img src="assets/images/logo.png" alt="HealCare" style="height: 40px; margin-bottom: 10px;" onerror="this.style.display='none'">
            <h2>Secure Payment</h2>
        </div>

        <div class="bill-summary">
            <div class="summary-row"><span>Patient</span> <span><?php echo $bill['patient_name']; ?></span></div>
            <div class="summary-row"><span>Service</span> <span>Consultation</span></div>
            <div class="summary-row"><span>Invoice</span> <span>#INV-<?php echo str_pad($bill['bill_id'], 4, '0', STR_PAD_LEFT); ?></span></div>
            <div class="summary-row total"><span>Amount to Pay</span> <span>₹<?php echo number_format($bill['total_amount'], 2); ?></span></div>
        </div>

        <form action="payment_process.php" method="POST">
            <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $bill['total_amount']; ?>">
            <input type="hidden" name="patient_id" value="<?php echo $bill['patient_id']; ?>">
            <input type="hidden" name="appt_id" value="<?php echo $bill['appointment_id']; ?>">

            <div class="payment-methods">
                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #475569;">Select Payment Method</label>
                
                <label class="method-option">
                    <input type="radio" name="payment_method" value="UPI" checked>
                    <div>
                        <strong>UPI (GPay, PhonePe, Paytm)</strong>
                        <div style="font-size: 0.8rem; color: #94a3b8;">Instant & Zero Fee</div>
                    </div>
                </label>

                <label class="method-option">
                    <input type="radio" name="payment_method" value="Card">
                    <div>
                        <strong>Credit / Debit Card</strong>
                        <div class="cards">
                           <small>Visa, Mastercard, RuPay</small>
                        </div>
                    </div>
                </label>

                <label class="method-option">
                    <input type="radio" name="payment_method" value="Cash">
                    <div>
                        <strong>Pay at Hospital Desk</strong>
                        <div style="font-size: 0.8rem; color: #94a3b8;">Confirm & pay on arrival</div>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn-pay">Pay ₹<?php echo number_format($bill['total_amount'], 0); ?> Now</button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-size: 0.85rem; color: #94a3b8;">
            <i class="fas fa-lock"></i> Your transaction is secured by HealCare Pay Encrytion
        </p>
    </div>
</body>
</html>
