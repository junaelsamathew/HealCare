<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
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

// Ward Rates (Hardcoded for MVP)
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

// Fetch other pending bills during this admission (Lab, Pharmacy, etc.)
$patient_id = intval($adm['patient_id']);
$adm_start = $adm['admission_date'];
$other_bills_res = $conn->query("SELECT bill_id, bill_type, total_amount FROM billing WHERE patient_id = $patient_id AND payment_status = 'Pending' AND bill_date >= DATE('$adm_start')");
$other_charges = [];
$other_total = 0;
if ($other_bills_res) {
    while ($row = $other_bills_res->fetch_assoc()) {
        $other_charges[] = $row;
        $other_total += $row['total_amount'];
    }
}

$total_est = $room_charge + $doc_charge + $other_total;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $diagnosis = $_POST['final_diagnosis'];
    $summary_notes = $_POST['summary_notes'];
    $advice = $_POST['advice'];
    $follow_up = $_POST['follow_up_date'];
    
    $conn->begin_transaction();
    try {
        // 1. Create Bill
        $bill_date = date('Y-m-d');
        // Use NULL for appointment_id as this is an inpatient split bill or aggregate bill
        $stmt_bill = $conn->prepare("INSERT INTO billing (patient_id, doctor_id, appointment_id, bill_type, total_amount, payment_status, bill_date) VALUES (?, ?, NULL, 'Inpatient Final', ?, 'Pending', ?)");
        $stmt_bill->bind_param("iids", $adm['patient_id'], $doctor_id, $total_est, $bill_date);
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
        if (!empty($other_charges)) {
            $bill_ids = array_column($other_charges, 'bill_id');
            $ids_str = implode(',', $bill_ids);
            $conn->query("UPDATE billing SET payment_status = 'Paid', payment_mode = 'Merged', transaction_ref = 'Merged into Bill #$bill_id' WHERE bill_id IN ($ids_str)");
        }
        
        // 5. Free Room
        $rid = $adm['room_id'] ?? null;
        if ($rid) {
            $conn->query("UPDATE rooms SET status = 'Available' WHERE room_id = $rid");
        }
        
        $conn->commit();
        header("Location: doctor_dashboard.php?msg=Patient Discharged Successfully");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error processing discharge: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Discharge Patient - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        body { background: #0f172a; color: white; font-family: 'Poppins', sans-serif; }
        .container { max-width: 800px; margin: 40px auto; padding: 30px; background: #1e293b; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); }
        h1 { color: #f59e0b; margin-bottom: 5px; }
        .summary-box { background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-size: 13px; font-weight: 500; }
        input, textarea, select { width: 100%; background: #0f172a; border: 1px solid #334155; padding: 12px; border-radius: 8px; color: white; font-family: inherit; }
        .btn-submit { background: #f59e0b; color: white; border: none; padding: 15px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; font-size: 16px; transition: 0.3s; }
        .btn-submit:hover { background: #d97706; }
        .bill-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .total-row { display: flex; justify-content: space-between; padding: 15px 0; border-top: 1px solid rgba(255,255,255,0.1); font-weight: 700; font-size: 18px; color: #10b981; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Discharge Protocol</h1>
        <p style="color: #64748b; margin-bottom: 30px;">Finalize treatment and generate discharge summary</p>
        
        <?php if(isset($error)) echo "<p style='color:red; background:rgba(255,0,0,0.1); padding:10px; border-radius:8px;'>$error</p>"; ?>

        <div class="summary-box">
            <h3 style="margin-bottom: 15px; font-size: 16px;">Patient Details</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 14px;">
                <div>
                    <span style="color:#94a3b8;">Patient Name:</span><br>
                    <strong><?php echo htmlspecialchars($adm['real_name']); ?></strong>
                </div>
                <div>
                    <span style="color:#94a3b8;">Admitted Location:</span><br>
                    <strong><?php echo htmlspecialchars(($adm['ward_name'] ?? 'N/A') . ' - ' . ($adm['room_number'] ?? 'N/A')); ?></strong>
                </div>
                <div>
                    <span style="color:#94a3b8;">Admission Date:</span><br>
                    <strong><?php echo $admission_date->format('d M Y'); ?></strong> (<?php echo $days; ?> Days)
                </div>
                <div>
                    <span style="color:#94a3b8;">Ward Type:</span><br>
                    <span style="color: #f59e0b;"><?php echo htmlspecialchars($adm['ward_type'] ?? 'General'); ?></span>
                </div>
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Billing Estimation</label>
                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px;">
                    <div class="bill-row">
                        <span>Room Charges (<?php echo $days; ?> days @ ₹<?php echo $rate; ?>)</span>
                        <span>₹<?php echo number_format($room_charge); ?></span>
                    </div>
                    <?php foreach($other_charges as $oc): ?>
                    <div class="bill-row">
                        <span><?php echo htmlspecialchars($oc['bill_type']); ?></span>
                        <span>₹<?php echo number_format($oc['total_amount']); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="total-row">
                        <span>Total Bill Amount</span>
                        <span>₹<?php echo number_format($total_est); ?></span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Final Diagnosis</label>
                <input type="text" name="final_diagnosis" required placeholder="e.g. Acute Viral Fever - Recovered">
            </div>
            
            <div class="form-group">
                <label>Discharge Summary / Treatment Given</label>
                <textarea name="summary_notes" rows="4" required placeholder="Details of treatment, medication, and patient condition..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Medical Advice & Medication</label>
                <textarea name="advice" rows="3" required placeholder="Home care instructions..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Follow-up Date</label>
                <input type="date" name="follow_up_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <button type="submit" class="btn-submit">Generate Bill & Discharge Patient</button>
        </form>
    </div></body>
</html>
