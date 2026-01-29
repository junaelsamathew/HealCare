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
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .main-card {
            background: white;
            width: 100%;
            max-width: 480px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            position: relative;
        }
        
        /* Modern Header */
        .header {
            text-align: center;
            padding: 35px 30px 20px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
        }
        .logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 5px;
        }
        .logo-txt {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        /* Receipt Style Details */
        .bill-details {
            background: white;
            padding: 30px;
            position: relative;
        }
        .bill-details::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20px;
            right: 20px;
            height: 1px;
            background: #e2e8f0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 0.95rem;
            color: #64748b;
        }
        .val { 
            text-align: right; 
            color: #1e293b; 
            font-weight: 600; 
        }
        
        .row.total {
            margin-top: 20px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            align-items: center;
            margin-bottom: 0;
        }
        .row.total span:first-child {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .row.total .val {
            color: #2563eb;
            font-size: 1.6rem;
            font-weight: 700;
        }

        /* Payment Actions */
        .payment-options {
            padding: 30px;
            background: #fff;
        }
        
        /* Primary Pay Button */
        .pay-online-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 18px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            text-decoration: none;
        }
        .pay-online-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
        }

        /* Secondary Actions Grid */
        .secondary-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .sec-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 8px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            text-align: center;
        }
        .sec-btn i { font-size: 1.2rem; margin-bottom: 2px; }
        
        .sec-btn:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #334155;
        }

        /* Test Mode - Discreet */
        .test-mode-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            font-size: 0.8rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s;
        }
        .test-mode-link:hover { color: #f59e0b; }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .processing-overlay {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(255,255,255,0.8);
            display: none; justify-content:Center; align-items:center; z-index:999;
            flex-direction: column;
        }
    </style>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <div class="processing-overlay" id="loader">
        <i class="fas fa-circle-notch fa-spin fa-3x" style="color:#2563eb;"></i>
        <p style="margin-top:15px; color:#333; font-weight:600;">Contacting Payment Server...</p>
    </div>

    <div class="main-card">
        <div class="header">
            <div class="logo-area">
                <i class="fas fa-shield-alt" style="font-size:1.4rem;"></i>
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
            
            <!-- helper form -->
            <form id="verifyForm" action="verify_payment.php" method="POST" style="display:none;">
                <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                <input type="hidden" name="razorpay_payment_id" id="rzp_pid">
                <input type="hidden" name="razorpay_order_id" id="rzp_oid">
                <input type="hidden" name="razorpay_signature" id="rzp_sig">
            </form>

            <!-- Prominent Pay Button -->
            <a href="#" id="pay-online-btn" class="pay-online-btn">
                <i class="fas fa-lock"></i> Pay ₹<?php echo $amount; ?> Securely
            </a>

            <!-- Secondary Actions -->
            <div class="secondary-actions">
                <a href="process_payment_counter.php?bill_id=<?php echo $bill_id; ?>" class="sec-btn">
                    <i class="fas fa-money-bill-wave" style="color:#64748b;"></i> Pay Cash
                </a>
                <a href="billing.php" class="sec-btn">
                    <i class="fas fa-times" style="color:#ef4444;"></i> Cancel
                </a>
            </div>

            <div class="secure-badge">
                <i class="fas fa-shield-check" style="color:#10b981;"></i> 
                <span>256-bit SSL Secured via Razorpay</span>
            </div>

            <!-- Dev Debug Link -->
            <a href="test_payment_success.php?bill_id=<?php echo $bill_id; ?>" class="test-mode-link">
                <i class="fas fa-bug"></i> Simulate Success (Test Mode)
            </a>
        </div>
    </div>

    <!-- PAYMENT LOGIC -->
    <script>
        document.getElementById('pay-online-btn').onclick = function(e){
            e.preventDefault();
            
            var loader = document.getElementById('loader');
            loader.style.display = 'flex';

            var billId = "<?php echo $bill_id; ?>";
            var amount = "<?php echo $bill['total_amount']; ?>";
            var userPhone = "<?php echo $bill['phone'] ?? '9999999999'; ?>";
            var userEmail = "<?php echo $bill['email'] ?? 'test@example.com'; ?>";

            // 1. Create Order
            fetch('create_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amount, bill_id: billId })
            })
            .then(res => res.text()) // Get raw text first to debug PHP errors
            .then(text => {
                let order;
                try {
                    order = JSON.parse(text);
                } catch (e) {
                    throw new Error("Server Error: " + text); // Show the PHP error content
                }

                loader.style.display = 'none';
                
                if(order.error){
                    alert("Error: " + order.error);
                    return;
                }

                // 2. Open Razorpay
                var options = {
                    "key": order.key_id, 
                    "amount": order.amount,
                    "currency": "INR",
                    "name": "HealCare Hospital",
                    "description": "Bill Payment #" + billId,
                    "order_id": order.id, 
                    "handler": function (response){
                        // 3. Verify Payment
                        document.getElementById('rzp_pid').value = response.razorpay_payment_id;
                        document.getElementById('rzp_oid').value = response.razorpay_order_id;
                        document.getElementById('rzp_sig').value = response.razorpay_signature;
                        
                        document.getElementById('verifyForm').submit();
                    },
                    "prefill": {
                        "name": "<?php echo $patient_display; ?>",
                        "email": userEmail,
                        "contact": userPhone
                    },
                    "theme": { "color": "#2563eb" }
                };

                if (typeof Razorpay === 'undefined') {
                    alert('Error: Razorpay SDK not loaded. Please allow scripts and refresh.');
                    return;
                }

                var rzp1 = new Razorpay(options);
                rzp1.open();
                
                rzp1.on('payment.failed', function (response){
                        alert("Payment Failed: " + response.error.description);
                });
            })
            .catch(err => {
                loader.style.display = 'none';
                console.error("Payment Error:", err);
                alert("Payment initiation failed:\n" + err.message);
            });
        };
    </script></body>
</html>
