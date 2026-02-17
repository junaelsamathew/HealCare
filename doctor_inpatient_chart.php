<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$adm_id = intval($_GET['admission_id']);
$doctor_id = $_SESSION['user_id'];

// Fetch Admission & Patient Info
$sql = "SELECT a.*, r.room_number, w.ward_name, w.ward_type, reg.name, pp.gender, pp.date_of_birth, pp.blood_group
        FROM admissions a 
        LEFT JOIN rooms r ON a.room_id = r.room_id 
        LEFT JOIN wards w ON r.ward_id = w.ward_id
        JOIN users u ON a.patient_id = u.user_id
        JOIN registrations reg ON u.registration_id = reg.registration_id
        LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
        WHERE a.admission_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adm_id);
$stmt->execute();
$adm = $stmt->get_result()->fetch_assoc();

if (!$adm) die("Admission not found.");

// Calculate Age
$dob = new DateTime($adm['date_of_birth'] ?? 'today');
$age = $dob->diff(new DateTime())->y;

// Handle New Note
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['daily_note'])) {
    $note = $_POST['daily_note'];
    $plan = $_POST['plan'];
    $stmt_add = $conn->prepare("INSERT INTO inpatient_treatment (admission_id, doctor_id, daily_notes, treatment_plan) VALUES (?, ?, ?, ?)");
    $stmt_add->bind_param("iiss", $adm_id, $doctor_id, $note, $plan);
    $stmt_add->execute();
    header("Location: doctor_inpatient_chart.php?admission_id=$adm_id&msg=Note Added");
    exit();
}

// Handle Nurse Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_nurse'])) {
    $pid = $adm['patient_id'];
    $stmt_req = $conn->prepare("INSERT INTO nurse_vitals_requests (patient_id, doctor_id, admission_id) VALUES (?, ?, ?)");
    $stmt_req->bind_param("iii", $pid, $doctor_id, $adm_id);
    $stmt_req->execute();
    header("Location: doctor_inpatient_chart.php?admission_id=$adm_id&msg=Nurse Requested");
    exit();
}


// Fetch Notes
$notes = $conn->query("SELECT * FROM inpatient_treatment WHERE admission_id = $adm_id ORDER BY visit_date DESC");

// Fetch Vitals (Assuming linked by patient_id)
$pid = $adm['patient_id'];
$vitals = $conn->query("SELECT * FROM patient_vitals WHERE patient_id = $pid ORDER BY recorded_at DESC LIMIT 10");

// Fetch Lab Results
$lab_res = $conn->query("SELECT * FROM lab_tests WHERE patient_id = $pid AND status = 'Completed' ORDER BY test_date DESC LIMIT 5");

// Fetch Prescriptions
$prescr = $conn->query("SELECT * FROM prescriptions WHERE patient_id = $pid ORDER BY prescription_date DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inpatient Chart - <?php echo htmlspecialchars($adm['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent: #3b82f6;
            --accent-glow: rgba(59, 130, 246, 0.2);
            --border: rgba(255, 255, 255, 0.05);
            --text-main: #f1f5f9;
            --text-dim: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: var(--bg-deep); 
            color: var(--text-main); 
            font-family: 'Outfit', sans-serif; 
            min-height: 100vh;
            background-image: radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.05) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.05) 0%, transparent 40%);
        }

        .container { max-width: 1400px; margin: 0 auto; padding: 40px; }
        
        .header-strip { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            background: var(--bg-card);
            padding: 25px 35px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .patient-meta { display: flex; gap: 30px; align-items: center; }
        .meta-item { border-right: 1px solid var(--border); padding-right: 30px; }
        .meta-item:last-child { border-right: none; }
        .meta-label { font-size: 11px; text-transform: uppercase; color: var(--text-dim); font-weight: 700; letter-spacing: 1px; margin-bottom: 5px; }
        .meta-val { font-size: 16px; font-weight: 600; color: #fff; }

        .main-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }

        .card { 
            background: var(--bg-card); 
            border-radius: 20px; 
            padding: 30px; 
            border: 1px solid var(--border); 
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .card-title { 
            font-size: 18px; 
            font-weight: 700; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            gap: 12px;
            color: #fff;
        }
        .card-title i { color: var(--accent); }

        /* Timeline style for history */
        .timeline { position: relative; padding-left: 20px; }
        .timeline::before { 
            content: ''; 
            position: absolute; 
            left: 0; 
            top: 0; 
            bottom: 0; 
            width: 2px; 
            background: var(--border); 
        }
        .timeline-item { position: relative; margin-bottom: 25px; }
        .timeline-item::before { 
            content: ''; 
            position: absolute; 
            left: -24px; 
            top: 8px; 
            width: 10px; 
            height: 10px; 
            background: var(--accent); 
            border-radius: 50%; 
            box-shadow: 0 0 10px var(--accent-glow);
        }
        .time-stamp { font-size: 11px; color: var(--text-dim); font-weight: 600; margin-bottom: 5px; display: block; }
        .note-content { font-size: 14px; line-height: 1.6; color: #cbd5e1; }
        .plan-box { 
            margin-top: 10px; 
            background: rgba(59, 130, 246, 0.05); 
            border-left: 3px solid var(--accent); 
            padding: 10px 15px; 
            border-radius: 4px; 
            font-style: italic;
            font-size: 13px;
        }

        /* Form styling */
        textarea { 
            width: 100%; 
            background: #020617; 
            border: 1px solid #1e293b; 
            border-radius: 12px; 
            padding: 15px; 
            color: #fff; 
            font-size: 14px; 
            margin-bottom: 15px;
            transition: 0.3s;
        }
        textarea:focus { border-color: var(--accent); outline: none; }
        .btn-primary { 
            background: var(--accent); 
            color: #fff; 
            border: none; 
            padding: 12px 25px; 
            border-radius: 10px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px var(--accent-glow); }

        .vitals-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .vital-tag { 
            background: rgba(255,255,255,0.03); 
            padding: 12px; 
            border-radius: 12px; 
            display: flex; 
            flex-direction: column;
            border: 1px solid var(--border);
        }
        .v-label { font-size: 10px; color: var(--text-dim); font-weight: 600; margin-bottom: 4px; }
        .v-val { font-size: 15px; font-weight: 700; color: #fff; }

        .badge { 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 10px; 
            font-weight: 700; 
            text-transform: uppercase; 
        }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }

        .report-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 12px 0; 
            border-bottom: 1px solid var(--border); 
        }
        .report-item:last-child { border-bottom: none; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .card { animation: fadeIn 0.5s ease-out backwards; }
        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.2s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-strip">
            <div>
                <h1 style="font-size: 24px; font-weight: 800; letter-spacing: -1px;"><?php echo htmlspecialchars($adm['name']); ?></h1>
                <p style="color: var(--text-dim); font-size: 13px;"><i class="fas fa-bed"></i> <?php echo htmlspecialchars($adm['ward_name'] . " - Room " . $adm['room_number']); ?></p>
            </div>
            
            <div class="patient-meta">
                <div class="meta-item">
                    <div class="meta-label">Age / Gender</div>
                    <div class="meta-val"><?php echo $age; ?>Y / <?php echo $adm['gender'] ?: 'N/A'; ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Blood Group</div>
                    <div class="meta-val" style="color: var(--danger);"><?php echo $adm['blood_group'] ?: 'N/A'; ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Admission ID</div>
                    <div class="meta-val">#ADM-<?php echo $adm_id; ?></div>
                </div>
            </div>

            <a href="doctor_dashboard.php" style="color: var(--text-dim); text-decoration: none; font-size: 13px; font-weight: 600;"><i class="fas fa-arrow-left"></i> Exit Chart</a>
        </div>

        <div class="main-layout">
            <!-- Left: Clinical Evolution -->
            <div>
                <div class="card">
                    <div class="card-title"><i class="fas fa-file-signature"></i> Round Entry</div>
                    <form method="POST">
                        <textarea name="daily_note" rows="3" required placeholder="Observations, patient complaints, clinical status..."></textarea>
                        <textarea name="plan" rows="2" placeholder="Plan: Medication adjustments, new orders, etc. (optional)"></textarea>
                        <button type="submit" class="btn-primary">Append to History</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-title"><i class="fas fa-history"></i> Clinical History</div>
                    <div class="timeline">
                        <?php if ($notes && $notes->num_rows > 0): ?>
                            <?php while($n = $notes->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <span class="time-stamp"><?php echo date('d M Y, h:i A', strtotime($n['visit_date'])); ?></span>
                                    <div class="note-content"><?php echo nl2br(htmlspecialchars($n['daily_notes'] ?? '')); ?></div>
                                    <?php if(!empty($n['treatment_plan'])): ?>
                                        <div class="plan-box">
                                            <strong>Plan:</strong> <?php echo nl2br(htmlspecialchars($n['treatment_plan'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: var(--text-dim); font-size: 14px; text-align: center; padding: 20px;">No clinical notes recorded for this admission.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Diagnostics & Actions -->
            <div class="sidebar">
                <div class="card">
                    <div class="card-title"><i class="fas fa-heartbeat"></i> Latest Vitals</div>
                    <?php if ($vitals && $vitals->num_rows > 0): 
                        $v = $vitals->fetch_assoc();
                    ?>
                        <div style="margin-bottom: 20px;">
                            <span class="time-stamp">LAST UPDATE: <?php echo date('h:i A', strtotime($v['recorded_at'])); ?></span>
                            <div class="vitals-grid">
                                <div class="vital-tag"><span class="v-label">BP (SYS/DIA)</span><span class="v-val"><?php echo $v['blood_pressure_systolic'].'/'.$v['blood_pressure_diastolic']; ?></span></div>
                                <div class="vital-tag"><span class="v-label">HEART RATE</span><span class="v-val"><?php echo $v['heart_rate']; ?> BPM</span></div>
                                <div class="vital-tag"><span class="v-label">TEMP</span><span class="v-val"><?php echo $v['temperature']; ?>°C</span></div>
                                <div class="vital-tag"><span class="v-label">SpO2</span><span class="v-val"><?php echo $v['spo2']; ?>%</span></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-dim); font-size: 13px; margin-bottom: 15px;">No vitals recorded.</p>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <button type="submit" name="request_nurse" class="btn-primary" style="width: 100%; background: var(--accent); opacity: 0.9;">
                            <i class="fas fa-hand-holding-medical"></i> Request Nurse Check
                        </button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-title"><i class="fas fa-flask"></i> Lab Reports</div>
                    <?php if ($lab_res && $lab_res->num_rows > 0): ?>
                        <?php while($lr = $lab_res->fetch_assoc()): ?>
                            <div class="report-item">
                                <div>
                                    <div style="font-size: 13px; font-weight: 600;"><?php echo htmlspecialchars($lr['test_name']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-dim);"><?php echo date('d M', strtotime($lr['test_date'])); ?></div>
                                </div>
                                <a href="view_lab_report.php?id=<?php echo $lr['labtest_id']; ?>" target="_blank" style="color: var(--accent); font-size: 12px; text-decoration: none;"><i class="fas fa-external-link-alt"></i> View</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--text-dim); font-size: 13px;">No completed reports found.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-title"><i class="fas fa-pills"></i> Current Meds</div>
                    <?php if ($prescr && $prescr->num_rows > 0): ?>
                        <?php while($pr = $prescr->fetch_assoc()): ?>
                            <div class="report-item">
                                <div style="font-size: 13px; color: #cbd5e1;"><?php echo htmlspecialchars($pr['medicine_details']); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--text-dim); font-size: 13px;">No active prescriptions.</p>
                    <?php endif; ?>
                </div>

                <div class="card" style="border-top: 5px solid var(--warning); background: linear-gradient(180deg, rgba(245, 158, 11, 0.05) 0%, transparent 100%);">
                    <div class="card-title" style="color: var(--warning);"><i class="fas fa-door-open"></i> Disposition</div>
                    <p style="font-size: 13px; color: var(--text-dim); margin-bottom: 20px;">Ready to discharge? This will finalize clinical summaries and billing.</p>
                    <a href="doctor_discharge.php?admission_id=<?php echo $adm_id; ?>" class="btn-primary" style="display: block; text-align: center; background: var(--warning); color: #000; text-decoration: none;">Initiate Discharge</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

