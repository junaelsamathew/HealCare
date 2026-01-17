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
$sql = "SELECT a.*, r.room_number, w.ward_name, w.ward_type, reg.name, pp.gender, pp.date_of_birth
        FROM admissions a 
        JOIN rooms r ON a.room_id = r.room_id 
        JOIN wards w ON r.ward_id = w.ward_id
        JOIN users u ON a.patient_id = u.user_id
        JOIN registrations reg ON u.registration_id = reg.registration_id
        LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
        WHERE a.admission_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adm_id);
$stmt->execute();
$adm = $stmt->get_result()->fetch_assoc();

if (!$adm) die("Admission not found.");

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

// Fetch Notes
$notes = $conn->query("SELECT * FROM inpatient_treatment WHERE admission_id = $adm_id ORDER BY visit_date DESC");

// Fetch Vitals (Assuming linked by patient_id)
$pid = $adm['patient_id'];
$vitals = $conn->query("SELECT * FROM patient_vitals WHERE patient_id = $pid ORDER BY recorded_at DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inpatient Chart - <?php echo htmlspecialchars($adm['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css"> <!-- Reusing dashboard styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #0f172a; color: #f1f5f9; font-family: 'Poppins', sans-serif; }
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .card { background: #1e293b; border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 20px; }
        .header { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        h2 { color: #f59e0b; font-size: 20px; margin-bottom: 15px; }
        .history-item { border-left: 3px solid #3b82f6; padding-left: 15px; margin-bottom: 20px; position: relative; }
        .history-item::before { content: ''; position: absolute; left: -6px; top: 0; width: 9px; height: 9px; background: #3b82f6; border-radius: 50%; }
        .timestamp { font-size: 11px; color: #94a3b8; margin-bottom: 5px; display: block; }
        
        textarea { width: 100%; background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .btn-add { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        
        .vitals-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1 style="font-size: 24px;">Inpatient Chart: <?php echo htmlspecialchars($adm['name']); ?></h1>
                <p style="color: #94a3b8;"><?php echo htmlspecialchars($adm['ward_name'] . " - Room " . $adm['room_number']); ?></p>
            </div>
            <a href="doctor_dashboard.php" style="color: #cbd5e1; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <!-- Left Column: Clinical Notes -->
        <div>
            <!-- Add Note Form -->
            <div class="card">
                <h2><i class="fas fa-edit"></i> Daily Rounds Entry</h2>
                <form method="POST">
                    <label style="display:block; margin-bottom:5px; font-size:12px; color:#cbd5e1;">Observations / Progress Note</label>
                    <textarea name="daily_note" rows="3" required placeholder="Patient condition, complaints, observations..."></textarea>
                    
                    <label style="display:block; margin-bottom:5px; font-size:12px; color:#cbd5e1;">Treatment Plan / Instructions</label>
                    <textarea name="plan" rows="2" placeholder="Medication changes, lab orders, diet instructions..."></textarea>
                    
                    <button type="submit" class="btn-add">Save Clinical Note</button>
                </form>
            </div>

            <!-- Notes Timeline -->
            <div class="card">
                <h2>Clinical History</h2>
                <?php if ($notes && $notes->num_rows > 0): ?>
                    <?php while($n = $notes->fetch_assoc()): ?>
                        <div class="history-item">
                            <span class="timestamp"><?php echo date('d M Y, h:i A', strtotime($n['visit_date'])); ?></span>
                            <p style="margin-bottom: 5px;"><strong>Note:</strong> <?php echo nl2br(htmlspecialchars($n['daily_notes'])); ?></p>
                            <?php if(!empty($n['treatment_plan'])): ?>
                                <p style="color: #f59e0b; font-size: 13px;"><strong>Plan:</strong> <?php echo nl2br(htmlspecialchars($n['treatment_plan'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #64748b;">No clinical notes recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Vitals & Info -->
        <div>
            <div class="card">
                <h2><i class="fas fa-heartbeat"></i> Recent Vitals</h2>
                <?php if ($vitals && $vitals->num_rows > 0): ?>
                    <?php while($v = $vitals->fetch_assoc()): ?>
                        <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                            <span class="timestamp"><?php echo date('d M, h:i A', strtotime($v['recorded_at'])); ?></span>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:5px; font-size:13px;">
                                <div><strong style="color: #fca5a5;">BP:</strong> <?php echo $v['blood_pressure_systolic'].'/'.$v['blood_pressure_diastolic']; ?></div>
                                <div><strong style="color: #86efac;">HR:</strong> <?php echo $v['heart_rate']; ?></div>
                                <div><strong style="color: #93c5fd;">Temp:</strong> <?php echo $v['temperature']; ?>°C</div>
                                <div><strong style="color: #fde047;">SpO2:</strong> <?php echo $v['oxygen_saturation']; ?>%</div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #64748b;">No vitals recorded.</p>
                <?php endif; ?>
                <button class="btn-add" style="width:100%; margin-top:10px; background:#3b82f6;">Request Nurse Check</button>
            </div>

            <div class="card">
                <h2>Quick Actions</h2>
                <a href="doctor_discharge.php?admission_id=<?php echo $adm_id; ?>" style="display:block; text-align:center; padding:12px; background:#f59e0b; color:white; border-radius:8px; text-decoration:none; font-weight:600;">Initiate Discharge</a>
            </div>
        </div>
    </div>
</body>
</html>
