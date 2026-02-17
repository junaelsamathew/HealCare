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
           ds.qualification as doctor_qualification,
           w.ward_name
    FROM admissions a
    JOIN patient_profiles p ON a.patient_id = p.user_id
    JOIN users u_doc ON a.doctor_id = u_doc.user_id
    JOIN registrations d ON u_doc.registration_id = d.registration_id
    LEFT JOIN doctors ds ON u_doc.user_id = ds.user_id
    LEFT JOIN rooms r ON a.room_id = r.room_id
    LEFT JOIN wards w ON r.ward_id = w.ward_id
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

// 2. Fetch Discharge Summary details
$ds_sql = "SELECT * FROM discharge_summaries WHERE admission_id = ?";
$stmt_ds = $conn->prepare($ds_sql);
$stmt_ds->bind_param("i", $admission_id);
$stmt_ds->execute();
$summary = $stmt_ds->get_result()->fetch_assoc();

// 3. Fetch Vitals Comparison (Admission vs Discharge)
// Admission Vitals (First recorded)
$vitals_adm_sql = "SELECT * FROM patient_vitals WHERE patient_id = ? AND recorded_at >= ? ORDER BY recorded_at ASC LIMIT 1";
$stmt_va = $conn->prepare($vitals_adm_sql);
$stmt_va->bind_param("is", $patient_id, $admission['admission_date']);
$stmt_va->execute();
$vitals_adm = $stmt_va->get_result()->fetch_assoc();

// Discharge Vitals (Latest recorded)
$vitals_dis_sql = "SELECT * FROM patient_vitals WHERE patient_id = ? AND recorded_at >= ? ORDER BY recorded_at DESC LIMIT 1";
$stmt_vd = $conn->prepare($vitals_dis_sql);
$stmt_vd->bind_param("is", $patient_id, $admission['admission_date']);
$stmt_vd->execute();
$vitals_dis = $stmt_vd->get_result()->fetch_assoc();

// 4. Fetch Treatment Plan / Daily Notes
$treatment_sql = "SELECT * FROM inpatient_treatment WHERE admission_id = ? ORDER BY visit_date DESC";
$stmt_t = $conn->prepare($treatment_sql);
$stmt_t->bind_param("i", $admission_id);
$stmt_t->execute();
$treatments = $stmt_t->get_result();
$treatment_history = [];
while($th = $treatments->fetch_assoc()) $treatment_history[] = $th;

// 5. Fetch Active Prescriptions (Discharge Calculations)
$presc_sql = "SELECT * FROM prescriptions WHERE patient_id = ? AND prescription_date >= DATE(?) ORDER BY prescription_date DESC";
$stmt_p = $conn->prepare($presc_sql);
$stmt_p->bind_param("is", $patient_id, $admission['admission_date']);
$stmt_p->execute();
$prescriptions = $stmt_p->get_result();
$medications = [];
while($pm = $prescriptions->fetch_assoc()) $medications[] = $pm;

// 6. Fetch Administered Medications (Medication Course Logs)
$med_logs_sql = "SELECT medicine_name, dosage, COUNT(*) as dose_count, MAX(administered_at) as last_dose 
                 FROM medication_logs 
                 WHERE patient_id = ? AND administered_at >= ? 
                 GROUP BY medicine_name, dosage";
$stmt_ml = $conn->prepare($med_logs_sql);
$stmt_ml->bind_param("is", $patient_id, $admission['admission_date']);
$stmt_ml->execute();
$med_logs = $stmt_ml->get_result();
$administered_meds = [];
while($ml = $med_logs->fetch_assoc()) $administered_meds[] = $ml;

// 7. Fetch Lab Results (Investigations)
$labs_sql = "SELECT test_name, test_type as test_category, result as result_value, 'N/A' as normal_range, test_date 
             FROM lab_tests 
             WHERE patient_id = ? AND (test_date >= DATE(?) OR created_at >= ?) AND status = 'Completed'
             ORDER BY test_date DESC";
$stmt_l = $conn->prepare($labs_sql);
$stmt_l->bind_param("iss", $patient_id, $admission['admission_date'], $admission['admission_date']);
$stmt_l->execute();
$lab_results = $stmt_l->get_result();
$labs = [];
while($l = $lab_results->fetch_assoc()) $labs[] = $l;


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
            --accent: #3b82f6;
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
            border-bottom: 3px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo-area h1 { font-size: 28px; font-weight: 800; margin: 0; color: var(--primary); letter-spacing: -1px; }
        .logo-area p { font-size: 12px; color: var(--secondary); margin: 5px 0 0; }
        
        .document-title { text-align: right; }
        .document-title h2 { font-size: 20px; margin: 0; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; }
        .document-title p { font-size: 11px; color: var(--secondary); margin: 5px 0 0; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 30px;
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .info-block h4 { font-size: 11px; text-transform: uppercase; color: var(--accent); margin: 0 0 15px; letter-spacing: 1px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 5px; }
        .info-row { display: flex; margin-bottom: 8px; font-size: 13px; }
        .info-label { width: 130px; font-weight: 600; color: var(--secondary); }
        .info-value { color: #334155; font-weight: 500; }

        .section { margin-bottom: 35px; }
        .section-title {
            font-size: 14px; font-weight: 700; text-transform: uppercase; color: #fff;
            background: var(--primary); padding: 8px 15px; border-radius: 6px;
            margin-bottom: 15px; letter-spacing: 0.5px;
        }
        .section-content { font-size: 14px; line-height: 1.6; color: #334155; padding: 0 5px; }

        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th { text-align: left; padding: 10px; background: #f1f5f9; color: var(--primary); font-weight: 700; border-bottom: 2px solid var(--border); }
        .data-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .data-table tr:last-child td { border-bottom: none; }

        /* Vitals Comparison */
        .vitals-comp { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .vital-box { border: 1px solid var(--border); border-radius: 8px; padding: 15px; }
        .vital-box h5 { margin: 0 0 10px; font-size: 12px; text-transform: uppercase; color: var(--secondary); text-align: center; }
        .vital-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .v-item { text-align: center; }
        .v-label { font-size: 10px; color: var(--secondary); text-transform: uppercase; }
        .v-val { font-size: 14px; font-weight: 700; color: var(--primary); }

        .footer { margin-top: 60px; display: flex; justify-content: space-between; align-items: flex-end; page-break-inside: avoid; }
        .signature-block { text-align: center; width: 220px; }
        .signature-line { border-top: 1px solid var(--primary); margin-bottom: 10px; }
        .signature-name { font-size: 13px; font-weight: 700; color: var(--primary); }
        .signature-title { font-size: 11px; color: var(--secondary); }

        .no-print { position: fixed; top: 20px; right: 20px; z-index: 1000; }
        .btn-download {
            background: var(--accent); color: white; padding: 12px 24px; border: none; border-radius: 8px;
            cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            display: flex; align-items: center; gap: 10px;
        }
        .btn-download:hover { background: #2563eb; }

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
            Download PDF
        </button>
    </div>

    <div class="container" id="printableArea">
        <div class="header">
            <div class="logo-area">
                <h1>HEALCARE</h1>
                <p>Advanced Medical Center & Research Institute</p>
                <p>Department of <?php echo htmlspecialchars($admission['doctor_specialization']); ?></p>
            </div>
            <div class="document-title">
                <h2>Discharge Summary</h2>
                <p>Ref: <?php echo $summary['summary_id'] ?? 'Pending'; ?></p>
                <p>Generated: <?php echo date('d M, Y h:i A'); ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h4>Patient Profile</h4>
                <div class="info-row"><span class="info-label">Name:</span><span class="info-value"><?php echo htmlspecialchars($admission['patient_name']); ?></span></div>
                <div class="info-row"><span class="info-label">UHID:</span><span class="info-value"><?php echo htmlspecialchars($admission['patient_code']); ?></span></div>
                <div class="info-row"><span class="info-label">Age/Gender:</span><span class="info-value"><?php echo $admission['age']; ?> / <?php echo $admission['gender']; ?></span></div>
                <div class="info-row"><span class="info-label">Address:</span><span class="info-value"><?php echo htmlspecialchars($admission['patient_address'] ?? 'N/A'); ?></span></div>
            </div>
            <div class="info-block">
                <h4>Admission Details</h4>
                <div class="info-row"><span class="info-label">Admitted On:</span><span class="info-value"><?php echo $admission_date; ?></span></div>
                <div class="info-row"><span class="info-label">Discharged On:</span><span class="info-value"><?php echo $discharge_date; ?></span></div>
                <div class="info-row"><span class="info-label">Consultant:</span><span class="info-value">Dr. <?php echo htmlspecialchars($admission['doctor_name']); ?></span></div>
                <div class="info-row"><span class="info-label">Ward/Bed:</span><span class="info-value"><?php echo htmlspecialchars($admission['ward_name']); ?></span></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Clinical Summary</div>
            <div class="section-content">
                <strong>Diagnosis:</strong><br>
                <?php echo nl2br(htmlspecialchars($summary['final_diagnosis'] ?? 'Pending')); ?><br><br>
                <strong>History & Course:</strong><br>
                <?php echo nl2br(htmlspecialchars($summary['summary_notes'] ?? 'Pending notes.')); ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Vitals Comparison</div>
            <div class="vitals-comp">
                <div class="vital-box" style="background: #eff6ff; border-color: #dbeafe;">
                    <h5>On Admission</h5>
                    <?php if($vitals_adm): ?>
                    <div class="vital-grid">
                        <div class="v-item"><div class="v-label">BP</div><div class="v-val"><?php echo $vitals_adm['blood_pressure_systolic'].'/'.$vitals_adm['blood_pressure_diastolic']; ?></div></div>
                        <div class="v-item"><div class="v-label">Pulse</div><div class="v-val"><?php echo $vitals_adm['heart_rate']; ?></div></div>
                        <div class="v-item"><div class="v-label">Temp</div><div class="v-val"><?php echo $vitals_adm['temperature']; ?>°F</div></div>
                        <div class="v-item"><div class="v-label">SpO2</div><div class="v-val"><?php echo $vitals_adm['spo2']; ?>%</div></div>
                    </div>
                    <?php else: ?>
                        <p style="text-align: center; font-size: 12px; color: #94a3b8;">Not Recorded</p>
                    <?php endif; ?>
                </div>
                <div class="vital-box" style="background: #f0fdf4; border-color: #bbf7d0;">
                    <h5>At Discharge</h5>
                    <?php if($vitals_dis): ?>
                    <div class="vital-grid">
                        <div class="v-item"><div class="v-label">BP</div><div class="v-val"><?php echo $vitals_dis['blood_pressure_systolic'].'/'.$vitals_dis['blood_pressure_diastolic']; ?></div></div>
                        <div class="v-item"><div class="v-label">Pulse</div><div class="v-val"><?php echo $vitals_dis['heart_rate']; ?></div></div>
                        <div class="v-item"><div class="v-label">Temp</div><div class="v-val"><?php echo $vitals_dis['temperature']; ?>°F</div></div>
                        <div class="v-item"><div class="v-label">SpO2</div><div class="v-val"><?php echo $vitals_dis['spo2']; ?>%</div></div>
                    </div>
                    <?php else: ?>
                        <p style="text-align: center; font-size: 12px; color: #94a3b8;">Not Recorded</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if(count($labs) > 0): ?>
        <div class="section">
            <div class="section-title">Significant Investigations</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Investigation</th>
                        <th>Result</th>
                        <th>Reference Range</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($labs as $l): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($l['test_name']); ?></td>
                        <td style="color: #64748b;"><?php echo htmlspecialchars($l['result_value']); ?></td>
                        <td><?php echo htmlspecialchars($l['normal_range']); ?></td>
                        <td><?php echo date('d M', strtotime($l['test_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if(count($administered_meds) > 0): ?>
        <div class="section">
            <div class="section-title">In-Hospital Medication Course</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Dosage</th>
                        <th>Total Doses</th>
                        <th>Last Administered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($administered_meds as $am): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($am['medicine_name']); ?></td>
                        <td><?php echo htmlspecialchars($am['dosage']); ?></td>
                        <td><?php echo $am['dose_count']; ?></td>
                        <td><?php echo date('d M, h:i A', strtotime($am['last_dose'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-title">Discharge Advice & Prescriptions</div>
            <div class="section-content">
                <div style="margin-bottom: 20px;">
                    <strong>Instructions:</strong><br>
                    <?php echo nl2br(htmlspecialchars($summary['advice'] ?? 'Follow routine advice.')); ?>
                </div>
                
                <?php if(count($medications) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Discharge Medicine</th>
                            <th>Regime</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($medications as $m): 
                               $details = explode("\n", $m['medicine_details']);
                               foreach($details as $med): if(trim($med)):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($med); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['instructions']); ?></td>
                            <td><?php echo htmlspecialchars($m['duration']); ?></td>
                        </tr>
                        <?php endif; endforeach; endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <?php if($summary['follow_up_date']): ?>
        <div class="section" style="background: #fff7ed; border: 1px solid #fed7aa; padding: 15px; border-radius: 6px;">
            <strong style="color: #c2410c;">Follow Up:</strong> 
            <span style="color: #9a3412;">Please review with Dr. <?php echo htmlspecialchars($admission['doctor_name']); ?> on <strong><?php echo date('d M, Y', strtotime($summary['follow_up_date'])); ?></strong>.</span>
        </div>
        <?php endif; ?>

        <div class="footer">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">Dr. <?php echo htmlspecialchars($admission['doctor_name']); ?></div>
                <div class="signature-title"><?php echo htmlspecialchars($admission['doctor_qualification'] . " (" . $admission['doctor_specialization'] . ")"); ?></div>
            </div>
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">Medical Superintendent</div>
                <div class="signature-title">HealCare Hospital</div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #cbd5e1; border-top: 1px solid #e2e8f0; padding-top: 10px;">
            This is a computer-generated discharge summary and does not require a physical signature if verified digitally. 
            Generated by HealCare HMS on <?php echo date('d M Y'); ?>.
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
    </script>
</body>
</html>
