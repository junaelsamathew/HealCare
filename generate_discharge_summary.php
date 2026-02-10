<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['admission_id']) || empty($_GET['admission_id'])) {
    die("Invalid Admission ID.");
}

$admission_id = intval($_GET['admission_id']);

// 1. Fetch Core Admission & Patient Info
$sql = "
    SELECT a.*, 
           p.name as patient_name, p.patient_code, p.gender, p.age, p.blood_group, p.address as patient_address,
           d.name as doctor_name, ds.specialization as doctor_specialization,
           ds.qualification as doctor_qualification
    FROM admissions a
    JOIN patient_profiles p ON a.patient_id = p.user_id
    JOIN users u_doc ON a.doctor_id = u_doc.user_id
    JOIN registrations d ON u_doc.registration_id = d.registration_id
    LEFT JOIN doctors ds ON u_doc.user_id = ds.user_id
    WHERE a.admission_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admission_id);
$stmt->execute();
$admission = $stmt->get_result()->fetch_assoc();

if (!$admission) {
    die("Admission record not found.");
}

$patient_id = $admission['patient_id'];

// 2. Fetch Discharge Summary details if exist
$ds_sql = "SELECT * FROM discharge_summaries WHERE admission_id = ?";
$stmt_ds = $conn->prepare($ds_sql);
$stmt_ds->bind_param("i", $admission_id);
$stmt_ds->execute();
$summary = $stmt_ds->get_result()->fetch_assoc();

// 3. Fetch Final Vitals (Latest recorded during admission)
$vitals_sql = "SELECT * FROM patient_vitals WHERE patient_id = ? AND recorded_at >= ? ORDER BY recorded_at DESC LIMIT 1";
$stmt_v = $conn->prepare($vitals_sql);
$stmt_v->bind_param("is", $patient_id, $admission['admission_date']);
$stmt_v->execute();
$vitals = $stmt_v->get_result()->fetch_assoc();

// 4. Fetch Treatment Plan / Daily Notes (Aggregated or latest)
$treatment_sql = "SELECT * FROM inpatient_treatment WHERE admission_id = ? ORDER BY visit_date DESC";
$stmt_t = $conn->prepare($treatment_sql);
$stmt_t->bind_param("i", $admission_id);
$stmt_t->execute();
$treatments = $stmt_t->get_result();
$treatment_history = [];
while($th = $treatments->fetch_assoc()) $treatment_history[] = $th;

// 5. Fetch Prescriptions (Active ones)
$presc_sql = "SELECT * FROM prescriptions WHERE patient_id = ? AND prescription_date >= DATE(?) ORDER BY prescription_date DESC";
$stmt_p = $conn->prepare($presc_sql);
$stmt_p->bind_param("is", $patient_id, $admission['admission_date']);
$stmt_p->execute();
$prescriptions = $stmt_p->get_result();
$medications = [];
while($pm = $prescriptions->fetch_assoc()) $medications[] = $pm;

// Formatting
$discharge_date = $admission['discharge_date'] ? date('d M, Y', strtotime($admission['discharge_date'])) : 'NOT DISCHARGED';
$admission_date = date('d M, Y', strtotime($admission['admission_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discharge Summary - <?php echo $admission['patient_code']; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #64748b;
            --border: #e2e8f0;
            --bg: #f8fafc;
        }
        body {
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
        }
        .container {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo-area h1 {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            color: var(--primary);
            letter-spacing: -1px;
        }
        .logo-area p {
            font-size: 12px;
            color: var(--secondary);
            margin: 5px 0 0;
        }
        .document-title {
            text-align: right;
        }
        .document-title h2 {
            font-size: 18px;
            margin: 0;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .document-title p {
            font-size: 11px;
            color: var(--secondary);
            margin: 5px 0 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 40px;
            background: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
        }
        .info-block h4 {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--secondary);
            margin: 0 0 10px;
            letter-spacing: 1px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .info-label {
            width: 120px;
            font-weight: 600;
            color: var(--primary);
        }
        .info-value {
            color: #334155;
        }

        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary);
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-content {
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
        }

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .vital-card {
            background: white;
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 6px;
            text-align: center;
        }
        .vital-label {
            font-size: 10px;
            color: var(--secondary);
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .vital-value {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
        }

        .med-table {
            width: 100%;
            border-collapse: collapse;
        }
        .med-table th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--secondary);
            padding: 10px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }
        .med-table td {
            padding: 12px 10px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }

        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .signature-block {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid var(--primary);
            margin-bottom: 10px;
        }
        .signature-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
        }
        .signature-title {
            font-size: 11px;
            color: var(--secondary);
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .btn-download {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-download:hover { background: #1e293b; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; background: white; }
            .container { box-shadow: none; width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="downloadPDF()" class="btn-download">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Generate Final PDF
        </button>
    </div>

    <div class="container" id="printableArea">
        <div class="header">
            <div class="logo-area">
                <h1>HEALCARE</h1>
                <p>Advanced Medical Center & Research Institute</p>
                <p>24/7 Emergency: +91 4828 200 100</p>
            </div>
            <div class="document-title">
                <h2>Discharge Summary</h2>
                <p>Summary ID: <?php echo $summary['summary_id'] ?? 'DRAFT-'.$admission_id; ?></p>
                <p>Date: <?php echo date('d M, Y'); ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h4>Patient Information</h4>
                <div class="info-row">
                    <span class="info-label">Patient Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($admission['patient_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Patient ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($admission['patient_code']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Age / Gender:</span>
                    <span class="info-value"><?php echo $admission['age']; ?> Yrs / <?php echo $admission['gender']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Blood Group:</span>
                    <span class="info-value"><?php echo $admission['blood_group']; ?></span>
                </div>
            </div>
            <div class="info-block">
                <h4>Admission Details</h4>
                <div class="info-row">
                    <span class="info-label">Admission Date:</span>
                    <span class="info-value"><?php echo $admission_date; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Discharge Date:</span>
                    <span class="info-value"><?php echo $discharge_date; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Primary Consultant:</span>
                    <span class="info-value"><?php echo htmlspecialchars($admission['doctor_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department:</span>
                    <span class="info-value"><?php echo htmlspecialchars($admission['doctor_specialization']); ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Clinical Presentation & Diagnosis</div>
            <div class="section-content">
                <strong>Reason for Admission:</strong><br>
                <?php echo nl2br(htmlspecialchars($admission['reason'] ?? 'Not specified')); ?><br><br>
                
                <strong>Final Diagnosis:</strong><br>
                <?php echo nl2br(htmlspecialchars($summary['final_diagnosis'] ?? 'Under evaluation')); ?>
            </div>
        </div>

        <?php if($vitals): ?>
        <div class="section">
            <div class="section-title">Vitals at Discharge</div>
            <div class="vitals-grid">
                <div class="vital-card">
                    <div class="vital-label">B.P (mmHg)</div>
                    <div class="vital-value"><?php echo $vitals['blood_pressure_systolic']; ?>/<?php echo $vitals['blood_pressure_diastolic']; ?></div>
                </div>
                <div class="vital-card">
                    <div class="vital-label">Heart Rate (bpm)</div>
                    <div class="vital-value"><?php echo $vitals['heart_rate']; ?></div>
                </div>
                <div class="vital-card">
                    <div class="vital-label">Temp (°F)</div>
                    <div class="vital-value"><?php echo $vitals['temperature']; ?></div>
                </div>
                <div class="vital-card">
                    <div class="vital-label">SpO2 (%)</div>
                    <div class="vital-value"><?php echo $vitals['spo2']; ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-title">Hospital Course & Summary</div>
            <div class="section-content">
                <?php 
                if ($summary['summary_notes']) {
                    echo nl2br(htmlspecialchars($summary['summary_notes']));
                } else {
                    foreach($treatment_history as $t) {
                        echo "<strong>".date('d M', strtotime($t['visit_date'])).":</strong> ".htmlspecialchars($t['daily_notes'])."<br>";
                    }
                }
                ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Treatment Plan & Discharge Advice</div>
            <div class="section-content">
                <?php echo nl2br(htmlspecialchars($summary['advice'] ?? 'Routine discharge advice provided. Stay hydrated and rest.')); ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Medications Prescribed</div>
            <table class="med-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Instructions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($medications) > 0): ?>
                        <?php foreach($medications as $m): 
                               $details = explode("\n", $m['medicine_details']);
                               foreach($details as $med): if(trim($med)):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($med); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($m['duration']); ?></td>
                            <td><?php echo htmlspecialchars($m['instructions']); ?></td>
                        </tr>
                        <?php endif; endforeach; endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">No medications currently prescribed.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($summary['follow_up_date']): ?>
        <div class="section" style="background: #fff7ed; padding: 15px; border-radius: 6px; border: 1px solid #ffedd5;">
            <div class="section-title" style="border:none; margin-bottom: 5px; color: #9a3412;">Follow-Up Appointment</div>
            <div class="section-content" style="color: #9a3412;">
                Kindly report to the consultant on <strong><?php echo date('d M, Y (l)', strtotime($summary['follow_up_date'])); ?></strong> for a review.
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">Medical Superintendent</div>
                <div class="signature-title">Seal of the Institute</div>
            </div>
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">Dr. <?php echo htmlspecialchars($admission['doctor_name']); ?></div>
                <div class="signature-title"><?php echo htmlspecialchars($admission['doctor_qualification'] . " (" . $admission['doctor_specialization'] . ")"); ?></div>
            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('printableArea');
            const opt = {
                margin:       10,
                filename:     'Discharge_Summary_<?php echo $admission['patient_code']; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
        
        // Auto-scroll to container
        window.scroll(0, 0);
    </script>
</body>
</html>
