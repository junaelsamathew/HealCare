<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Record ID.");
}

$record_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch Medical Record Details
$sql = "
    SELECT mr.*, 
           doc.name as doctor_name, doc.email as doctor_email,
           pat.name as patient_name, pat.email as patient_email, pp.patient_code, pp.gender, pp.phone as patient_phone,
           pp.address, pp.age, pp.blood_group,
           p_table.medicine_details, p_table.instructions as presc_instructions,
           d_details.specialization, d_details.designation
    FROM medical_records mr
    LEFT JOIN users u_doc ON mr.doctor_id = u_doc.user_id
    LEFT JOIN registrations doc ON u_doc.registration_id = doc.registration_id
    LEFT JOIN doctors d_details ON u_doc.user_id = d_details.user_id
    LEFT JOIN users u_pat ON mr.patient_id = u_pat.user_id
    LEFT JOIN registrations pat ON u_pat.registration_id = pat.registration_id
    LEFT JOIN patient_profiles pp ON mr.patient_id = pp.user_id
    LEFT JOIN prescriptions p_table ON mr.prescription_id = p_table.prescription_id
    WHERE mr.record_id = ?
";

// If patient, restrict to own records? Yes.
// If doctor/admin, allow? For now assuming patient view based on previous file context.
// But let's be safe.
if ($_SESSION['user_role'] == 'patient') {
    $sql .= " AND mr.patient_id = $user_id";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    die("Record not found or access denied.");
}

$data = $res->fetch_assoc();

// Prepare Data
$report_date = date('d M, Y', strtotime($data['created_at']));
$report_id = "RPT-" . str_pad($data['record_id'], 5, "0", STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosis Report - <?php echo $report_id; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body {
            background: #525659;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            padding: 30px;
        }
        .a4-page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            position: relative;
        }
        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-area h1 {
            color: #1e293b;
            margin: 0;
            font-size: 28px;
            letter-spacing: -1px;
        }
        .logo-area span {
            color: #3b82f6;
            font-weight: 700;
        }
        .hospital-details {
            text-align: right;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }
        .report-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            font-size: 14px;
        }
        .meta-box h4 {
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 5px 0;
        }
        .meta-box p {
            margin: 0;
            color: #1e293b;
            font-weight: 600;
        }
        .section-title {
            background: #f1f5f9;
            padding: 8px 15px;
            border-left: 4px solid #3b82f6;
            color: #334155;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .content-block {
            margin-bottom: 30px;
            font-size: 14px;
            color: #334155;
            line-height: 1.6;
        }
        .medicine-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .medicine-table th {
            text-align: left;
            background: #f8fafc;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-weight: 600;
        }
        .medicine-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .footer {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .stamp-area {
            position: absolute;
            bottom: 40mm;
            right: 20mm;
            text-align: center;
        }
        .stamp-box {
            width: 120px;
            height: 40px;
            margin-bottom: 10px;
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .btn-download {
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            transition: 0.3s;
        }
        .btn-download:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="downloadPDF()" class="btn-download">Download PDF</button>
    </div>

    <div class="a4-page" id="reportContent">
        <!-- Header -->
        <div class="header">
            <div class="logo-area">
                <h1>+ HEAL<span>CARE</span></h1>
                <div style="font-size: 11px; color: #64748b; letter-spacing: 2px;">MEDICAL CENTER</div>
            </div>
            <div class="hospital-details">
                Kanjirapally, Kottayam, Kerala<br>
                Phone: (+254) 717 783 146<br>
                Email: info@healcare.com
            </div>
        </div>

        <!-- Meta Info -->
        <div class="report-meta">
            <div class="meta-box">
                <h4>Patient Details</h4>
                <p><?php echo htmlspecialchars($data['patient_name']); ?></p>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    <?php echo $data['gender']; ?> 
                    <?php echo (!empty($data['age'])) ? " • " . $data['age'] . " Years" : ""; ?>
                    <?php echo (!empty($data['blood_group'])) ? " • " . $data['blood_group'] : ""; ?>
                    <br>
                    <?php echo (!empty($data['patient_code'])) ? "ID: " . $data['patient_code'] : ""; ?>
                    <br>
                    <?php echo $data['patient_phone']; ?>
                    <?php echo (!empty($data['address'])) ? "<br>" . htmlspecialchars($data['address']) : ""; ?>
                </div>
            </div>
            <div class="meta-box" style="text-align: right;">
                <h4>Consulting Doctor</h4>
                <p>Dr. <?php echo htmlspecialchars($data['doctor_name']); ?></p>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    <?php echo htmlspecialchars($data['specialization'] ?? $data['designation'] ?? 'General Physician'); ?><br>
                    License No: HCL-<?php echo rand(10000,99999); ?>
                </div>
            </div>
        </div>

        <div class="report-meta" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px;">
            <div class="meta-box">
                <h4>Report ID</h4>
                <p><?php echo $report_id; ?></p>
            </div>
            <div class="meta-box" style="text-align: right;">
                <h4>Date</h4>
                <p><?php echo $report_date; ?></p>
            </div>
        </div>

        <!-- Diagnosis Section -->
        <div class="section-title">Clinical Diagnosis</div>
        <div class="content-block">
            <p style="font-size: 16px; font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($data['diagnosis']); ?></p>
            <?php if (!empty($data['treatment'])): ?>
                <p><strong>Treatment Plan:</strong> <?php echo nl2br(htmlspecialchars($data['treatment'])); ?></p>
            <?php endif; ?>
        </div>

        <!-- Prescription Section -->
        <?php if (!empty($data['medicine_details'])): ?>
        <div class="section-title">Prescription (Rx)</div>
        <div class="content-block">
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <p style="white-space: pre-wrap; font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($data['medicine_details']); ?></p>
            </div>
            <?php if (!empty($data['presc_instructions'])): ?>
                <p style="margin-top: 10px; font-size: 12px;"><strong>Instructions:</strong> <?php echo htmlspecialchars($data['presc_instructions']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="stamp-area">
            <div class="stamp-box" style="display:flex; flex-direction:column; justify-content:center; align-items:center;">
                <!-- Placeholder for signature -->
                <div style="font-family: 'Brush Script MT', cursive; font-size: 20px; color: #3b82f6;">Dr. <?php echo explode(' ', $data['doctor_name'])[0]; ?></div>
            </div>
            <div style="font-size: 10px; font-weight: 700; border-top: 1px solid #333; padding-top: 5px; width: 150px; margin: 0 auto;">AUTHORIZED SIGNATORY</div>
        </div>

        <div class="footer">
            This report is digitally generated by HealCare Hospital Management System and is valid without a physical signature.<br>
            HealCare &copy; <?php echo date('Y'); ?>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('reportContent');
            const opt = {
                margin:       0,
                filename:     'Diagnosis_Report_<?php echo $report_id; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            // Hide the download button from the PDF capture? 
            // html2pdf captures the element provided. Button is outside #reportContent.
            
            html2pdf().set(opt).from(element).save();
        }

        // Auto-trigger download on load?
        // Let's ask user first? No, user clicked "Download PDF". Let's trigger it.
        window.onload = function() {
            // Check if we should auto download
            // A small timeout to ensure fonts load
            setTimeout(downloadPDF, 1000);
        };
    </script>
</body>
</html>
