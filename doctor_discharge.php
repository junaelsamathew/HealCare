<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

if (!isset($_GET['admission_id'])) {
    die("Invalid Admission ID");
}

$adm_id = intval($_GET['admission_id']);
$doctor_id = $_SESSION['user_id'];

// Fetch Admission Details
$sql = "SELECT a.*, r.room_number, w.ward_name, w.ward_type, u.username as patient_name, u.user_id as patient_id, reg.name as real_name
        FROM admissions a 
        LEFT JOIN rooms r ON a.room_id = r.room_id 
        LEFT JOIN wards w ON r.ward_id = w.ward_id
        JOIN users u ON a.patient_id = u.user_id
        JOIN registrations reg ON u.registration_id = reg.registration_id
        WHERE a.admission_id = ? AND a.status = 'Admitted'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adm_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    die("Admission record not found or patient already discharged.");
}

$adm = $res->fetch_assoc();
$admission_date = new DateTime($adm['admission_date']);
$today = new DateTime();
$days = $today->diff($admission_date)->days;
if ($days == 0) $days = 1; // Minimum 1 day charge

// Ward Rates (Hardcoded for MVP - Ideally from DB)
$rates = [
    'General' => 500,
    'Semi-Private' => 1500,
    'Private' => 3000,
    'ICU' => 5000,
    'Emergency' => 2000
];
$ward_type = $adm['ward_type'] ?? 'General';
$rate = $rates[$ward_type] ?? 1000;
$room_charge = $days * $rate;
$doc_charge = $days * 500; // 500 per day doctor visit

$patient_id = intval($adm['patient_id']);
$adm_start = $adm['admission_date'];

// 1. Fetch existing PENDING bills (Lab/Pharmacy already billed)
$other_bills_res = $conn->query("SELECT bill_id, bill_type, total_amount, description FROM billing WHERE patient_id = $patient_id AND payment_status = 'Pending' AND bill_date >= DATE('$adm_start')");
$existing_charges = [];
$existing_total = 0;
while ($row = $other_bills_res->fetch_assoc()) {
    $existing_charges[] = $row;
    $existing_total += $row['total_amount'];
}

// 2. Automated Unbilled Lab Calculation
$lab_q = $conn->query("
    SELECT COUNT(*) as count, GROUP_CONCAT(test_name SEPARATOR ', ') as tests
    FROM lab_tests 
    WHERE patient_id = $patient_id 
    AND (created_at >= '$adm_start' OR test_date >= DATE('$adm_start'))
    AND payment_status = 'Pending'
    AND labtest_id NOT IN (SELECT reference_id FROM billing WHERE bill_type LIKE 'Lab%' AND patient_id = $patient_id)
");
$unbilled_labs = $lab_q->fetch_assoc();
$unbilled_lab_count = $unbilled_labs['count'] ?? 0;
$unbilled_lab_charge = $unbilled_lab_count * 500; // 500 per test standard

// 3. Automated Unbilled Medicine Calculation
$med_charge = 0;
$med_list = [];
$presc_q = $conn->query("
    SELECT * FROM prescriptions 
    WHERE patient_id = $patient_id 
    AND prescription_date >= DATE('$adm_start')
    AND (status IS NULL OR status = 'Pending')
    AND prescription_id NOT IN (SELECT reference_id FROM billing WHERE bill_type LIKE 'Pharmacy%' AND patient_id = $patient_id)
");

// Logic from generate_bill.php to estimate cost
$stock_q = $conn->query("SELECT medicine_name, unit_price FROM pharmacy_stock");
$stocks = [];
while($s = $stock_q->fetch_assoc()) $stocks[] = $s;

while($presc = $presc_q->fetch_assoc()) {
    $text = strtolower($presc['medicine_details']);
    $p_cost = 0;
    foreach($stocks as $stock) {
        $name = strtolower($stock['medicine_name']);
        if (strpos($text, $name) !== false) {
            $price = floatval($stock['unit_price']);
            $p_days = 5; 
            if (preg_match('/(\d+)\s*days?/i', $text, $matches)) $p_days = intval($matches[1]);
            $per_day = 2;
            if (preg_match('/(\d+)-(\d+)-(\d+)/', $text, $f_matches)) $per_day = intval($f_matches[1]) + intval($f_matches[2]) + intval($f_matches[3]);
            $p_cost += ($p_days * $per_day * $price);
            $med_list[] = $stock['medicine_name'];
        }
    }
    if ($p_cost == 0) $p_cost = 200; // Flat fee if not matched
    $med_charge += $p_cost;
}

$total_est = $room_charge + $doc_charge + $existing_total + $unbilled_lab_charge + $med_charge;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $diagnosis = $_POST['final_diagnosis'];
    $summary_notes = $_POST['summary_notes'];
    $advice = $_POST['advice'];
    $follow_up = $_POST['follow_up_date'];
    
    $conn->begin_transaction();
    try {
        // 1. Create Final Master Bill (Marked as Paid for immediate settlement)
        $bill_date = date('Y-m-d');
        $desc = "Inpatient Final Settlement: Room ($days Days), Doctor rounds, Medicine, Labs.";
        $stmt_bill = $conn->prepare("INSERT INTO billing (patient_id, doctor_id, bill_type, description, total_amount, payment_status, payment_mode, bill_date) VALUES (?, ?, 'Inpatient Settlement', ?, ?, 'Paid', 'Cash', ?)");
        $stmt_bill->bind_param("iisds", $patient_id, $doctor_id, $desc, $total_est, $bill_date);
        $stmt_bill->execute();
        $bill_id = $conn->insert_id;
        
        // 2. Create Discharge Summary
        $stmt_ds = $conn->prepare("INSERT INTO discharge_summaries (admission_id, final_diagnosis, summary_notes, advice, follow_up_date) VALUES (?, ?, ?, ?, ?)");
        $stmt_ds->bind_param("issss", $adm_id, $diagnosis, $summary_notes, $advice, $follow_up);
        $stmt_ds->execute();
        
        // 3. Update Admission
        $stmt_upd = $conn->prepare("UPDATE admissions SET status = 'Discharged', discharge_date = NOW(), bill_id = ? WHERE admission_id = ?");
        $stmt_upd->bind_param("ii", $bill_id, $adm_id);
        $stmt_upd->execute();
        
        // 4. Mark merged bills as Paid (Merged)
        if (!empty($existing_charges)) {
            $bill_ids = array_column($existing_charges, 'bill_id');
            $ids_str = implode(',', $bill_ids);
            $conn->query("UPDATE billing SET payment_status = 'Paid', payment_mode = 'Merged', transaction_ref = 'Merged into settlement Bill #$bill_id' WHERE bill_id IN ($ids_str)");
        }

        // 5. Mark unbilled items as Paid (Merged)
        $conn->query("UPDATE lab_tests SET payment_status = 'Paid' WHERE patient_id = $patient_id AND (created_at >= '$adm_start' OR test_date >= DATE('$adm_start')) AND payment_status = 'Pending'");
        $conn->query("UPDATE prescriptions SET status = 'Dispensed' WHERE patient_id = $patient_id AND prescription_date >= DATE('$adm_start') AND (status IS NULL OR status = 'Pending')");
        
        // 6. Free Room
        $rid = $adm['room_id'] ?? null;
        if ($rid) {
            $conn->query("UPDATE rooms SET status = 'Available' WHERE room_id = $rid");
        }
        
        $conn->commit();
        header("Location: doctor_dashboard.php?msg=Discharged successfully. Final Bill #$bill_id Paid Successfully.&discharged_id=$adm_id");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Process failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discharge Patient - HealCare Specialist</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent: #f59e0b;
            --accent-glow: rgba(245, 158, 11, 0.2);
            --border: rgba(255, 255, 255, 0.05);
            --text-main: #f1f5f9;
            --text-dim: #94a3b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg-deep); color: var(--text-main); font-family: 'Outfit', sans-serif; min-height: 100vh; overflow-x: hidden; }

        .dashboard-container { display: grid; grid-template-columns: 1fr 400px; gap: 30px; max-width: 1400px; margin: 40px auto; padding: 0 40px; }

        .main-workflow { background: var(--bg-card); border-radius: 24px; padding: 40px; border: 1px solid var(--border); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .sidebar-bill { position: sticky; top: 40px; }

        h1 { font-size: 32px; font-weight: 800; letter-spacing: -1px; margin-bottom: 8px; }
        .subtitle { color: var(--text-dim); margin-bottom: 40px; font-size: 15px; }

        .section-card { background: rgba(255,255,255,0.02); border-radius: 16px; padding: 25px; margin-bottom: 30px; border: 1px solid var(--border); }
        .section-title { font-size: 14px; font-weight: 700; text-transform: uppercase; color: var(--accent); letter-spacing: 1px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .form-grid { display: grid; grid-template-columns: 1fr; gap: 25px; }
        .input-group { display: flex; flex-direction: column; gap: 8px; }
        label { font-size: 13px; font-weight: 600; color: var(--text-dim); }
        input, textarea, select { background: #020617; border: 1px solid #1e293b; border-radius: 12px; padding: 14px 18px; color: #fff; font-size: 15px; transition: 0.3s; width: 100%; }
        input:focus, textarea:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 0 4px var(--accent-glow); }

        /* Billing Aesthetics */
        .bill-card { background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 24px; padding: 30px; border: 1px solid var(--border); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .bill-header { border-bottom: 1px dashed var(--border); padding-bottom: 20px; margin-bottom: 20px; }
        .bill-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 14px; color: var(--text-dim); }
        .bill-item .val { color: var(--text-main); font-weight: 600; }
        .bill-total { border-top: 1px solid var(--border); margin-top: 20px; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 14px; font-weight: 800; color: var(--accent); text-transform: uppercase; }
        .total-val { font-size: 28px; font-weight: 900; color: #10b981; }

        .btn-discharge { background: var(--accent); color: #000; border: none; width: 100%; padding: 18px; border-radius: 16px; font-size: 16px; font-weight: 800; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 25px; }
        .btn-discharge:hover { transform: translateY(-3px); box-shadow: 0 10px 20px var(--accent-glow); background: #fbbf24; }

        .patient-strip { display: flex; align-items: center; gap: 20px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px; margin-bottom: 30px; }
        .avatar { width: 50px; height: 50px; background: var(--accent); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #000; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .main-workflow { animation: fadeIn 0.6s ease-out; }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <!-- Main Workflow -->
        <div class="main-workflow">
            <a href="doctor_inpatient_chart.php?admission_id=<?php echo $adm_id; ?>" style="color: var(--text-dim); text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 25px;"><i class="fas fa-chevron-left"></i> Back to Patient Chart</a>
            
            <h1>Discharge Protocol</h1>
            <p class="subtitle">Complete clinical notes and finalize settlement for <?php echo htmlspecialchars($adm['real_name']); ?>.</p>

            <div class="patient-strip">
                <div class="avatar"><i class="fas fa-user-injured"></i></div>
                <div>
                    <h4 style="font-size: 18px;"><?php echo htmlspecialchars($adm['real_name']); ?></h4>
                    <p style="font-size: 12px; color: var(--text-dim); font-weight: 500;">
                        <i class="fas fa-bed"></i> <?php echo htmlspecialchars($adm['ward_name']); ?> • Room <?php echo htmlspecialchars($adm['room_number']); ?>
                    </p>
                </div>
                <div style="margin-left: auto; text-align: right;">
                    <span style="font-size: 11px; color: var(--accent); font-weight: 800; text-transform: uppercase;">Stay Duration</span>
                    <p style="font-size: 16px; font-weight: 700;"><?php echo $days; ?> Days</p>
                </div>
            </div>

            <?php if(isset($error)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 14px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-clipboard-check"></i> Clinical Conclusion</div>
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Final Diagnosis</label>
                            <input type="text" name="final_diagnosis" required placeholder="e.g. Acute Bronchitis - Post Treatment Review">
                        </div>
                        <div class="input-group">
                            <label>Stay Summary & Progress</label>
                            <textarea name="summary_notes" rows="6" required placeholder="Outline clinical progress, major treatments, and patient condition at time of discharge..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title"><i class="fas fa-prescription"></i> Post-Discharge Care</div>
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Home Care Advice & Medication</label>
                            <textarea name="advice" rows="4" required placeholder="Specify diet, restricted activities, and home medication regime..."></textarea>
                        </div>
                        <div class="input-group">
                            <label>Scheduled Follow-up</label>
                            <input type="date" name="follow_up_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 250px;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 20px; align-items: center;">
                    <p style="color: var(--text-dim); font-size: 13px;"><i class="fas fa-info-circle"></i> This will finalize all bills and lock clinical notes.</p>
                    <button type="submit" class="btn-discharge" style="width: auto; padding: 15px 40px; margin: 0;">Finalize Settlement & Discharge</button>
                </div>
            </form>
        </div>

        <!-- Sidebar: Real-time Billing -->
        <div class="sidebar-bill">
            <div class="bill-card">
                <div class="bill-header">
                    <h3 style="font-size: 20px; margin-bottom: 5px;">Unified Invoice</h3>
                    <p style="font-size: 12px; color: var(--text-dim);">Live estimation of stay costs</p>
                </div>

                <div class="bill-item">
                    <span>Room (<?php echo $days; ?>d @ ₹<?php echo $rate; ?>)</span>
                    <span class="val">₹<?php echo number_format($room_charge); ?></span>
                </div>
                <div class="bill-item">
                    <span>Doctor Visits (<?php echo $days; ?>d)</span>
                    <span class="val">₹<?php echo number_format($doc_charge); ?></span>
                </div>

                <?php if($unbilled_lab_charge > 0): ?>
                <div class="bill-item">
                    <span>Unbilled Labs (<?php echo $unbilled_lab_count; ?>)</span>
                    <span class="val">₹<?php echo number_format($unbilled_lab_charge); ?></span>
                </div>
                <?php endif; ?>

                <?php if($med_charge > 0): ?>
                <div class="bill-item">
                    <span>Medicine Charges</span>
                    <span class="val">₹<?php echo number_format($med_charge); ?></span>
                </div>
                <?php endif; ?>

                <?php foreach($existing_charges as $oc): ?>
                <div class="bill-item">
                    <span><?php echo htmlspecialchars($oc['bill_type']); ?></span>
                    <span class="val">₹<?php echo number_format($oc['total_amount']); ?></span>
                </div>
                <?php endforeach; ?>

                <div class="bill-total">
                    <div>
                        <span class="total-label">Grand Total</span>
                        <p style="font-size: 10px; color: var(--text-dim); margin-top: 2px;">Inclusive of all services</p>
                    </div>
                    <span class="total-val">₹<?php echo number_format($total_est); ?></span>
                </div>

                <div style="margin-top: 30px; background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 12px; border: 1px dashed rgba(16, 185, 129, 0.3);">
                    <h5 style="color: #10b981; font-size: 12px; margin-bottom: 5px;"><i class="fas fa-shield-alt"></i> Settlement Security</h5>
                    <p style="font-size: 11px; color: var(--text-dim); line-height: 1.4;">Finalizing this protocol will consolidate all pending charges into a single master bill. Direct payments for lab/pharmacy during stay will be merged.</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
