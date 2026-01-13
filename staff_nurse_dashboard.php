<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch Nurse Details
$res = $conn->query("SELECT * FROM nurses WHERE user_id = $user_id");
$nurse = $res->fetch_assoc();
$department = $nurse['department'] ?? 'General';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent-blue: #3b82f6;
            --border-soft: rgba(255, 255, 255, 0.05);
        }

        .reception-top-bar { background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .top-info-group { display: flex; gap: 40px; }
        .top-info-item { display: flex; align-items: center; gap: 12px; color: #1e293b; font-size: 13px; }
        .top-info-item i { color: #3b82f6; font-size: 24px; }
        .top-info-text strong { display: block; text-transform: uppercase; font-size: 11px; color: #64748b; }

        .secondary-nav { background: #0f172a; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .staff-label { color: #94a3b8; font-size: 14px; }
        .btn-logout-alt { background: transparent; border: 1px solid #3b82f6; color: #fff; padding: 8px 25px; border-radius: 20px; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .btn-logout-alt:hover { background: #3b82f6; }

        .dashboard-body { display: grid; grid-template-columns: 260px 1fr; height: calc(100vh - 140px); background: #020617; }
        .side-nav { background: #020617; padding: 20px 0; border-right: 1px solid var(--border-soft); }
        .nav-item { display: flex; align-items: center; padding: 15px 30px; color: #94a3b8; text-decoration: none; font-size: 14px; gap: 15px; transition: 0.3s; }
        .nav-item.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-left: 4px solid #3b82f6; }
        .nav-item:hover:not(.active) { color: #fff; background: rgba(255,255,255,0.02); }

        .main-ops { padding: 40px; overflow-y: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card-new { background: #0f172a; padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); }
        .stat-card-new h2 { font-size: 24px; color: #4fc3f7; margin-bottom: 5px; }

        .patient-list-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .patient-card {
            background: #0f172a;
            border: 1px solid var(--border-soft);
            border-radius: 16px;
            padding: 25px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            position: relative;
            overflow: hidden;
        }
        .token-badge {
            position: absolute; top: 0; right: 0;
            background: rgba(79, 195, 247, 0.1); color: #4fc3f7;
            padding: 10px 20px; border-bottom-left-radius: 16px;
            font-weight: 800; font-size: 14px; border: 1px solid rgba(79, 195, 247, 0.2);
            border-top: none; border-right: none;
        }
        .vital-inputs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .form-group-staff { display: flex; flex-direction: column; gap: 8px; }
        .form-group-staff label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; }
        .form-group-staff input, .form-group-staff textarea { 
            background: rgba(255,255,255,0.03); border: 1px solid var(--border-soft); 
            padding: 12px; border-radius: 10px; color: #fff; font-size: 14px; outline: none; transition: 0.3s;
        }
        .form-group-staff input:focus { border-color: #4fc3f7; background: rgba(79, 195, 247, 0.05); }
    </style>
</head>
<body>

    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
        <h1 style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">+ HEALCARE</h1>
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">EMERGENCY</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">(+254) 717 783 146</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-clock"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WORK HOUR</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">09:00 - 20:00 Everyday</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">LOCATION</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">Kanjirapally, Kottayam</span>
                </div>
            </div>
        </div>
    </div>

    <div class="secondary-nav">
        <div style="display: flex; align-items: center; gap: 15px;"><div style="background: #4fc3f7; color:#fff; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold;">N</div><h2 style="color:#fff; font-size:20px;">Nurse Panel</h2></div>
        <div style="display: flex; align-items: center; gap: 30px;"><span class="staff-label"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span><a href="logout.php" class="btn-logout-alt" style="border-color: #4fc3f7;">Log Out</a></div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <a href="?section=patients" class="nav-item active"><i class="fas fa-hospital-user"></i> My Patients</a>
            <a href="?section=vitals" class="nav-item"><i class="fas fa-heartbeat"></i> Vitals Monitor</a>
            <a href="?section=notes" class="nav-item"><i class="fas fa-notes-medical"></i> Nursing Notes</a>
            <a href="?section=medication" class="nav-item"><i class="fas fa-syringe"></i> Medication</a>
            <a href="?section=handover" class="nav-item"><i class="fas fa-clock"></i> Shift handover</a>
            <a href="?section=reports" class="nav-item"><i class="fas fa-chart-line"></i> Nursing Reports</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <?php if (!isset($_GET['section']) || $_GET['section'] == 'dashboard' || $_GET['section'] == 'patients'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Ward Management - <?php echo $department; ?></h1>
                    <p style="color:#64748b; font-size:14px;">Monitor assigned patients and update vital signs.</p>
                </div>

                <div class="stats-grid">
                    <?php
                    // Dynamic Stats Calculation
                    $today = date('Y-m-d');
                    
                    // 1. Assigned Patients (Active Today)
                    $sql_assigned = "SELECT COUNT(*) as c FROM appointments 
                                     WHERE appointment_date = '$today' 
                                     AND status IN ('Approved', 'Checked-In', 'In-Treatment', 'Admitted')";
                    $assigned_count = $conn->query($sql_assigned)->fetch_assoc()['c'] ?? 0;

                    // 2. Critical/In-Patient (Admitted)
                    $sql_critical = "SELECT COUNT(*) as c FROM appointments 
                                     WHERE status IN ('Admitted', 'In-Treatment')";
                    $critical_count = $conn->query($sql_critical)->fetch_assoc()['c'] ?? 0;

                    // 3. Doses Ready (Prescriptions Today)
                    // Assuming 'prescriptions' table exists with created_at or checking if it returns result
                    $doses_count = 0;
                    $sql_doses = "SELECT COUNT(*) as c FROM prescriptions WHERE DATE(created_at) = '$today'";
                    if ($res_doses = $conn->query($sql_doses)) {
                        $doses_count = $res_doses->fetch_assoc()['c'] ?? 0;
                    }

                    // 4. Ward Capacity (Static Max 50)
                    $ward_display = str_pad($critical_count, 2, '0', STR_PAD_LEFT) . ' / 50';
                    ?>
                    <div class="stat-card-new"><h2><?php echo str_pad($assigned_count, 2, '0', STR_PAD_LEFT); ?></h2><p>Assigned Patients</p></div>
                    <div class="stat-card-new"><h2><?php echo str_pad($critical_count, 2, '0', STR_PAD_LEFT); ?></h2><p>Critical Monitoring</p></div>
                    <div class="stat-card-new"><h2><?php echo str_pad($doses_count, 2, '0', STR_PAD_LEFT); ?></h2><p>Doses Ready</p></div>
                    <div class="stat-card-new"><h2><?php echo $ward_display; ?></h2><p>Ward Cap.</p></div>
                </div>

                <!-- Quick Archive -->
                <div style="background: linear-gradient(135deg, #0f172a, #1e293b); padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="color: #fff; margin-bottom: 5px; font-size: 16px;"><i class="fas fa-file-upload" style="color: #4fc3f7;"></i> Nursing Department Reports</h3>
                        <p style="color: #64748b; font-size: 12px;">Upload shift handover reports or patient care summaries.</p>
                    </div>
                    <button onclick="openReportModal()" style="background: #4fc3f7; color: #020617; text-decoration: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 12px; border: none; cursor: pointer;">
                        <i class="fas fa-upload"></i> Upload Report
                    </button>
                </div>

                <h3 style="color:#fff; margin-bottom: 25px;">Live Department Queue - Assigned Patients</h3>

                <div class="patient-list-container">
                    <?php
                    // Fetch Active Patients for Nurse (Today's appointments or Admitted)
                    // Logic: Get appointments for today that are confirmed/checked-in/admitted
                    // In a real scenario, this might filter by the nurse's specific ward or department
                    $sql_patients = "SELECT a.appointment_id, a.status as appt_status, 
                                            r.name, r.phone, u.user_id as patient_id, 
                                            d.name as doctor_name, d.specialization
                                     FROM appointments a
                                     JOIN users u ON a.patient_id = u.user_id
                                     JOIN registrations r ON u.registration_id = r.registration_id
                                     LEFT JOIN users du ON a.doctor_id = du.user_id
                                     LEFT JOIN registrations d ON du.registration_id = d.registration_id
                                     WHERE a.appointment_date = CURDATE() 
                                     AND a.status IN ('Approved', 'Checked-In', 'Admitted', 'In-Treatment')
                                     ORDER BY a.appointment_time ASC";
                    
                    $res_patients = $conn->query($sql_patients);

                    if ($res_patients && $res_patients->num_rows > 0):
                        while($pt = $res_patients->fetch_assoc()):
                            $status_color = ($pt['appt_status'] == 'In-Treatment' || $pt['appt_status'] == 'Admitted') ? '#4fc3f7' : '#fbbf24';
                            $status_text = ($pt['appt_status'] == 'Approved') ? 'Waiting' : $pt['appt_status'];
                            $opacity = ($pt['appt_status'] == 'Approved') ? '0.8' : '1';
                    ?>
                    <div class="patient-card" style="opacity: <?php echo $opacity; ?>;">
                        <div class="token-badge" style="background: rgba(<?php echo ($status_color == '#4fc3f7') ? '79, 195, 247' : '251, 191, 36'; ?>, 0.1); color: <?php echo $status_color; ?>; border-color: rgba(<?php echo ($status_color == '#4fc3f7') ? '79, 195, 247' : '251, 191, 36'; ?>, 0.2);">
                            TOKEN: #APT-<?php echo $pt['appointment_id']; ?>
                        </div>
                        <div style="border-right: 1px solid var(--border-soft); padding-right: 30px;">
                            <span style="font-size:11px; color:<?php echo $status_color; ?>; font-weight:800; text-transform:uppercase;"><?php echo htmlspecialchars($status_text); ?></span>
                            <h4 style="color:#fff; margin: 10px 0; font-size: 18px;"><?php echo htmlspecialchars($pt['name']); ?></h4>
                            <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">ID: HC-P-<?php echo $pt['patient_id']; ?></p>
                            
                            <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; font-size: 13px; color: #cbd5e1;">
                                <p style="margin-bottom: 5px;"><i class="fas fa-bed" style="width:20px;"></i> General Ward</p>
                                <p><i class="fas fa-stethoscope" style="width:20px;"></i> Dr. <?php echo htmlspecialchars($pt['doctor_name']); ?> (<?php echo htmlspecialchars($pt['specialization']); ?>)</p>
                            </div>
                        </div>
                        <div>
                            <?php if ($pt['appt_status'] == 'Approved'): ?>
                                <p style="color: #64748b; font-size: 14px; font-style: italic;">Patient is currently waiting for initial vital check. Please call the patient to the nursing station.</p>
                                <button style="margin-top: 20px; padding: 10px 25px; background: #fbbf24; border: none; border-radius: 10px; color: #000; font-weight: 700; cursor: pointer;">Call Patient</button>
                            <?php else: ?>
                                <div class="vital-inputs">
                                    <div class="form-group-staff"><label>Heart Rate (BPM)</label><input type="text" placeholder="--"></div>
                                    <div class="form-group-staff"><label>BP (Sys/Dia)</label><input type="text" placeholder="--/--"></div>
                                    <div class="form-group-staff"><label>Temp (°F)</label><input type="text" placeholder="--"></div>
                                    <div class="form-group-staff"><label>SPO2 (%)</label><input type="text" placeholder="--"></div>
                                </div>
                                <div class="form-group-staff" style="margin-top: 20px;">
                                    <label>Nursing Care Notes</label>
                                    <textarea rows="3" placeholder="Enter patient observation, pain levels, or medication response..."></textarea>
                                </div>
                                <div style="display: flex; gap: 15px; margin-top: 20px;">
                                    <button style="flex: 1; padding: 12px; background: #4fc3f7; border: none; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer;">Update Vitals</button>
                                    <button style="padding: 12px 20px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-soft); border-radius: 10px; color: #fff; cursor: pointer;"><i class="fas fa-history"></i> History</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 60px; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px dashed var(--border-soft);">
                            <i class="fas fa-user-injured" style="font-size: 40px; color: #64748b; margin-bottom: 20px;"></i>
                            <h3 style="color: #94a3b8; font-size: 18px;">No Active Patients</h3>
                            <p style="color: #64748b; font-size: 14px;">There are no patients currently assigned or waiting in the queue for today.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($_GET['section'] == 'vitals'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Vitals Monitoring</h1>
                    <p style="color:#64748b; font-size:14px;">Quick vitals entry for assigned patients.</p>
                </div>
                <div class="stat-card-new">
                    <h3 style="color:#fff; margin-bottom:20px;">Select Patient</h3>
                    <select style="width: 100%; padding: 12px; background: #020617; border: 1px solid var(--border-soft); color: #fff; border-radius: 8px;">
                        <option>Ravi Sharma (HC-P-2026-1025) - Ward B/15</option>
                        <option>Sneha Gupta (HC-P-2026-1026) - Waiting</option>
                    </select>
                    
                    <div class="vital-inputs" style="margin-top: 20px;">
                        <div class="form-group-staff"><label>Heart Rate (BPM)</label><input type="text" placeholder="--"></div>
                        <div class="form-group-staff"><label>BP (Sys/Dia)</label><input type="text" placeholder="--/--"></div>
                        <div class="form-group-staff"><label>Temp (°F)</label><input type="text" placeholder="--"></div>
                        <div class="form-group-staff"><label>SPO2 (%)</label><input type="text" placeholder="--"></div>
                        <div class="form-group-staff"><label>Resp. Rate</label><input type="text" placeholder="--"></div>
                    </div>
                    <button style="margin-top: 20px; padding: 12px 30px; background: #4fc3f7; border: none; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer;">Log Vitals</button>
                </div>

            <?php elseif ($_GET['section'] == 'notes'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Nursing Notes</h1>
                    <p style="color:#64748b; font-size:14px;">Clinical observations and care notes.</p>
                </div>
                <div class="stat-card-new">
                     <div class="form-group-staff" style="margin-bottom: 20px;">
                        <label>Patient</label>
                        <select style="width: 100%; padding: 12px; background: #020617; border: 1px solid var(--border-soft); color: #fff; border-radius: 8px;">
                            <option value="">-- Select Patient --</option>
                            <?php
                            $today_notes = date('Y-m-d');
                            $sql_notes_pt = "SELECT u.user_id as patient_id, r.name 
                                             FROM appointments a
                                             JOIN users u ON a.patient_id = u.user_id
                                             JOIN registrations r ON u.registration_id = r.registration_id
                                             WHERE a.appointment_date = '$today_notes' 
                                             AND a.status IN ('Approved', 'Checked-In', 'Admitted', 'In-Treatment')
                                             ORDER BY r.name ASC";
                            $res_notes_pt = $conn->query($sql_notes_pt);
                            
                            if ($res_notes_pt && $res_notes_pt->num_rows > 0) {
                                while ($npt = $res_notes_pt->fetch_assoc()) {
                                    echo '<option value="'.$npt['patient_id'].'">' . htmlspecialchars($npt['name']) . ' (HC-P-'.$npt['patient_id'].')</option>';
                                }
                            } else {
                                echo '<option disabled>No active patients found today</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group-staff">
                        <label>Note Content</label>
                        <textarea rows="6" placeholder="Detailed nursing notes..."></textarea>
                    </div>
                    <button style="margin-top: 20px; padding: 12px 30px; background: #4fc3f7; border: none; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer;">Save Note</button>
                </div>

            <?php elseif ($_GET['section'] == 'medication'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Medication Administration</h1>
                    <p style="color:#64748b; font-size:14px;">Track and administer prescribed doses.</p>
                </div>
                <div class="patient-card">
                     <h3 style="color:#fff; margin-bottom:15px;">Ravi Sharma - Ward B/15</h3>
                     <table style="width:100%; color:#cbd5e1; border-collapse:collapse;">
                        <tr style="border-bottom:1px solid var(--border-soft); text-align:left;"><th style="padding:10px;">Drug</th><th style="padding:10px;">Dose</th><th style="padding:10px;">Time</th><th style="padding:10px;">Status</th></tr>
                        <tr style="border-bottom:1px solid var(--border-soft);">
                            <td style="padding:10px;">Paracetamol</td>
                            <td style="padding:10px;">500mg</td>
                            <td style="padding:10px;">14:00</td>
                            <td style="padding:10px;"><span style="color:#4fc3f7;">Due Now</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px;">Amoxicillin</td>
                            <td style="padding:10px;">250mg</td>
                            <td style="padding:10px;">20:00</td>
                            <td style="padding:10px;"><span style="color:#64748b;">Upcoming</span></td>
                        </tr>
                     </table>
                </div>

            <?php elseif ($_GET['section'] == 'handover'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Shift Handover</h1>
                    <p style="color:#64748b; font-size:14px;">Prepare handover notes for the next shift.</p>
                </div>
                 <div class="stat-card-new">
                    <div class="form-group-staff">
                        <label>Shift Summary</label>
                        <textarea rows="8" placeholder="Summarize critical events, pending tasks, and patient status changes..."></textarea>
                    </div>
                     <button style="margin-top: 20px; padding: 12px 30px; background: #10b981; border: none; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer;">Submit Handover Log</button>
                </div>

            <?php elseif ($_GET['section'] == 'reports'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Nursing Reports</h1>
                    <p style="color:#64748b; font-size:14px;">Vital signs logs and patient care records.</p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
                    <!-- Vitals Report -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=nurse_vitals'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: var(--accent-blue);">Vital Signs</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Patient monitoring logs</p>
                            </div>
                            <i class="fas fa-heartbeat" style="font-size:24px; color: var(--accent-blue);"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Daily Vital Check Logs</li>
                            <li>Critical Patient Alerts</li>
                        </ul>
                        <button class="btn-logout-alt" style="width:100%; text-align:center;">View Report</button>
                    </div>

                    <!-- Care/Duty Report -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=nurse_care'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: #10b981;">Patient Care & Duty</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Nursing shift handouts</p>
                            </div>
                            <i class="fas fa-user-nurse" style="font-size:24px; color: #10b981;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Shift Handover Notes</li>
                            <li>Patient Care Summaries</li>
                        </ul>
                        <button class="btn-logout-alt" style="width:100%; text-align:center; border-color: #10b981; color: #10b981;">View Report</button>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php 
    // Set staff_type for the modal
    $staff_type = 'nurse';
    include 'includes/report_upload_modal.php'; 
    ?>
</body>
</html>
