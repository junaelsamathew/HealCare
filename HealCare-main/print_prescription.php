<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    die("Unauthorized access.");
}

if (!isset($_GET['id'])) {
    die("Invalid Prescription ID.");
}

$presc_id = (int)$_GET['id'];

// Fetch Prescription Details
$sql = "
    SELECT p.*, 
           rp.name as patient_name, rp.email as patient_email, rp.phone as patient_phone, 
           pp.date_of_birth as dob, pp.gender,
           rd.name as doctor_name, d.specialization, d.qualification,
           u.email as doctor_email
    FROM prescriptions p
    JOIN users up ON p.patient_id = up.user_id
    JOIN registrations rp ON up.registration_id = rp.registration_id
    LEFT JOIN patient_profiles pp ON up.user_id = pp.user_id
    JOIN users ud ON p.doctor_id = ud.user_id
    JOIN registrations rd ON ud.registration_id = rd.registration_id
    LEFT JOIN doctors d ON ud.user_id = d.user_id
    JOIN users u ON p.doctor_id = u.user_id
    WHERE p.prescription_id = $presc_id
";

$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    die("Prescription not found.");
}

$data = $result->fetch_assoc();
$age = 'N/A';
if (!empty($data['dob'])) {
    $dob = new DateTime($data['dob']);
    $now = new DateTime();
    $age = $now->diff($dob)->y . ' Years';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription #<?php echo $presc_id; ?> - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            padding: 40px;
            color: #1e293b;
        }
        .paper {
            background: #fff;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #020617;
            text-transform: uppercase;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo i { color: #3b82f6; }
        .hospital-info {
            text-align: right;
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }
        .doc-section {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .doc-profile h2 { font-size: 18px; margin: 0 0 5px 0; color: #0f172a; }
        .doc-profile p { font-size: 13px; color: #64748b; margin: 0; }
        
        .patient-section {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        .patient-row span { display: inline-block; margin-right: 20px; }
        .patient-row strong { color: #475569; }

        .rx-symbol {
            font-family: serif;
            font-size: 48px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .med-content {
            font-size: 14px;
            line-height: 1.8;
            min-height: 200px;
            white-space: pre-wrap;
            color: #334155;
        }
        
        .instructions-box {
            margin-top: 30px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 20px;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 20px;
            border-top: 2px solid #0f172a;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #94a3b8;
            text-align: center;
            padding-top: 5px;
            font-size: 12px;
            color: #64748b;
        }
        
        @media print {
            body { background: #fff; padding: 0; }
            .paper { box-shadow: none; padding: 20px; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width: 800px; margin: 0 auto 20px auto; text-align: right;">
        <button onclick="window.print()" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;"><i class="fas fa-print"></i> Print Prescription</button>
        <button onclick="window.close()" style="background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; margin-left: 10px;">Close</button>
    </div>

    <div class="paper">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <i class="fas fa-plus-square"></i> HEALCARE
            </div>
            <div class="hospital-info">
                <strong>HealCare Hospital & Diagnostics</strong><br>
                Kanjirapally, Kottayam, Kerala<br>
                Phone: (+254) 717 783 146<br>
                Email: support@healcare.com
            </div>
        </div>

        <!-- Doctor Info -->
        <div class="doc-section">
            <div class="doc-profile">
                <h2>Dr. <?php echo htmlspecialchars($data['doctor_name']); ?></h2>
                <p><?php echo htmlspecialchars($data['specialization']); ?></p>
                <p><?php echo htmlspecialchars($data['qualification']); ?></p>
            </div>
            <div style="text-align: right; font-size: 12px; color: #64748b;">
                <strong>Date:</strong> <?php echo date('d M, Y', strtotime($data['prescription_date'])); ?><br>
                <strong>Prescription ID:</strong> #RX-<?php echo str_pad($data['prescription_id'], 5, '0', STR_PAD_LEFT); ?>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="patient-section">
            <div class="patient-row">
                <span><strong>Patient Name:</strong> <?php echo htmlspecialchars($data['patient_name']); ?></span>
                <span><strong>Age/Sex:</strong> <?php echo $age; ?> / <?php echo htmlspecialchars($data['gender']); ?></span>
            </div>
            <div class="patient-row">
                <span><strong>Phone:</strong> <?php echo htmlspecialchars($data['patient_phone']); ?></span>
            </div>
        </div>

        <!-- Rx Content -->
        <div class="rx-symbol">Rx</div>
        <div class="med-content">
            <?php echo nl2br(htmlspecialchars($data['medicine_details'])); ?>
        </div>

        <!-- Instructions -->
        <?php if(!empty($data['instructions'])): ?>
        <div class="instructions-box">
            <h4 style="margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; color: #475569;">Additional Instructions</h4>
            <p style="margin: 0; font-size: 13px; color: #475569; font-style: italic;">
                <?php echo nl2br(htmlspecialchars($data['instructions'])); ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div style="font-size: 10px; color: #94a3b8;">
                Generated by HealCare System<br>
                <?php echo date('Y-m-d H:i:s'); ?>
            </div>
            <div class="signature-line">
                Doctor's Signature
            </div>
        </div>
    </div>

</body>
</html>
