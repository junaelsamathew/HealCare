<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['bill_id'])) {
    die("Invalid Request");
}

$bill_id = intval($_GET['bill_id']);
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch Bill Details
$sql = "SELECT b.*, u.username as patient_username, p.name as patient_name, u.email,
        r.name as doctor_name, a.department, p.patient_code 
        FROM billing b 
        LEFT JOIN users u ON b.patient_id = u.user_id 
        LEFT JOIN patient_profiles p ON u.user_id = p.user_id
        LEFT JOIN users ud ON b.doctor_id = ud.user_id
        LEFT JOIN registrations r ON ud.registration_id = r.registration_id
        LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
        WHERE b.bill_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    die("Bill not found.");
}

$bill = $res->fetch_assoc();
$invoice_id = "INV-" . str_pad($bill['bill_id'], 4, '0', STR_PAD_LEFT);
$patient_display = $bill['patient_name'] ?? $bill['patient_username'] ?? 'Guest';
$service = ($bill['bill_type'] ?? 'Consultation');
$amount = number_format($bill['total_amount'], 2);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Need Payment - HealCare PAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .main-card {
            background: white;
            width: 100%;
            max-width: 450px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            position: relative;
        }
        .header {
            text-align: center;
            padding: 30px 20px 20px;
        }
        .logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        .logo-txt {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2563eb;
        }
        .subtitle {
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .bill-details {
            background: #f8fafc;
            margin: 0 20px 20px;
            padding: 20px;
            border-radius: 8px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #64748b;
        }
        .row.total {
            margin-top: 15px;
            margin-bottom: 0;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e1;
            font-weight: 700;
            color: #0f172a;
            font-size: 1.1rem;
        }
        .val { text-align: right; color: #334155; font-weight: 500; }
        .row.total .val { color: #0f172a; }

        .payment-options {
            padding: 0 20px 30px;
        }
        .option-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 15px 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            cursor: pointer;
            text-decoration: none;
            color: #334155;
            transition: all 0.2s;
        }
        .option-btn:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }
        .opt-left { display: flex; align-items: center; gap: 15px; }
        .opt-icon { width: 40px; text-align: center; font-size: 1.2rem; }
        .opt-text strong { display: block; font-size: 0.95rem; color: #0f172a; }
        .opt-text span { font-size: 0.8rem; color: #64748b; }

        .secure-badge {
            text-align: center;
            padding: 15px;
            font-size: 0.8rem;
            color: #94a3b8;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }

        /* Test Success Style */
        .option-btn.test-success {
            background: #fffbeb; 
            border-color: #fcd34d;
        }
        .option-btn.test-success .opt-text strong { color: #b45309; }
        .option-btn.test-success .opt-icon { color: #d97706; }

    </style>
</head>
<body>

    <div class="main-card">
        <div class="header">
            <div class="logo-area">
                <i class="fas fa-shield-alt" style="color:#2563eb; font-size:1.4rem;"></i>
                <span class="logo-txt">HealCare PAY</span>
            </div>
            <div class="subtitle">Secure Payment Gateway</div>
        </div>

        <div class="bill-details">
            <div class="row">
                <span>Patient</span>
                <span class="val"><?php echo htmlspecialchars($patient_display); ?></span>
            </div>
            <div class="row">
                <span>Service</span>
                <span class="val"><?php echo htmlspecialchars($service); ?></span>
            </div>
            <div class="row">
                <span>Invoice</span>
                <span class="val">#<?php echo $invoice_id; ?></span>
            </div>
            <div class="row total">
                <span>Total Payable</span>
                <span class="val">₹<?php echo $amount; ?></span>
            </div>
        </div>

        <div class="payment-options">
            <a href="#" style="text-decoration:none;" onclick="alert('This triggers standard Razorpay standard checkout flow (Not implemented without API keys). Use Test Success below.');">
                <div class="option-btn">
                    <div class="opt-left">
                        <div class="opt-icon" style="color:#2563eb;"><i class="fab fa-cc-visa"></i></div>
                        <div class="opt-text">
                            <strong>Pay Online</strong>
                            <span>UPI, Cards, NetBanking</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#cbd5e1;"></i>
                </div>
            </a>

            <a href="test_payment_success.php?bill_id=<?php echo $bill_id; ?>" style="text-decoration:none;">
                <div class="option-btn test-success">
                    <div class="opt-left">
                        <div class="opt-icon"><i class="fas fa-bug"></i></div>
                        <div class="opt-text">
                            <strong>Test Payment Success</strong>
                            <span>Simulate successful Razorpay callback</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#d97706;"></i>
                </div>
            </a>

            <a href="process_payment_counter.php?bill_id=<?php echo $bill_id; ?>" style="text-decoration:none;">
                <div class="option-btn">
                    <div class="opt-left">
                        <div class="opt-icon" style="color:#64748b;"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="opt-text">
                            <strong>Pay at Counter</strong>
                            <span>Cash payment at hospital</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#cbd5e1;"></i>
                </div>
            </a>
            
             <a href="billing.php" style="display:block; text-align:center; color:#94a3b8; font-size:0.85rem; margin-top:10px;">Cancel Transaction</a>
        </div>

        <div class="secure-badge">
            <i class="fas fa-lock"></i> Secured by Razorpay
        </div>
    </div>

</body>
</html>
