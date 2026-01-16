<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch actual doctor professional info
$stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $doctor = $res->fetch_assoc();
    $specialization = $doctor['specialization'];
    $department = $doctor['department'];
    $designation = $doctor['designation'];
} else {
    // Fallback for demo/manual users without doctor profiles
    $specialization = "General Healthcare / Consultation";
    $department = "General Medicine";
    $designation = "Professional Consultant";
}

$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
if (stripos($doctor_name, 'Dr.') === false && stripos($doctor_name, 'Doctor') === false) {
    $doctor_name = "Dr. " . $doctor_name;
}

// Handle Status Updates (Accept Appointment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appt_id = intval($_POST['appt_id']);
    $new_status = $_POST['new_status'];
    // Verify ownership
    $check_own = $conn->query("SELECT appointment_id FROM appointments WHERE appointment_id = $appt_id AND doctor_id = $user_id");
    if ($check_own->num_rows > 0) {
        $stmt_upd = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt_upd->bind_param("si", $new_status, $appt_id);
        $stmt_upd->execute();
        header("Location: doctor_dashboard.php");
        exit();
    }
}

// Handle Patient Selection for Treatment
$active_patient = null;
$history_records = [];
if (isset($_GET['patient_id'])) {
    $pid = $_GET['patient_id'];
    
    // Fetch Patient Details
    $stmt = $conn->prepare("SELECT u.username, r.name, r.phone, pp.patient_code, pp.gender, pp.date_of_birth, pp.medical_history FROM users u JOIN registrations r ON u.registration_id = r.registration_id LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id WHERE u.user_id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $active_patient = $stmt->get_result()->fetch_assoc();
    
    // Calculate Age
    if (isset($active_patient['date_of_birth']) && $active_patient['date_of_birth']) {
        $dob = new DateTime($active_patient['date_of_birth']);
        $now = new DateTime();
        $active_patient['age'] = $now->diff($dob)->y . ' Years';
    } else {
        $active_patient['age'] = 'N/A';
    }

    // Fetch Latest Vitals (recorded by nurse)
    $stmt_v = $conn->prepare("SELECT * FROM patient_vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmt_v->bind_param("i", $pid);
    $stmt_v->execute();
    $vitals_res = $stmt_v->get_result();
    $latest_vitals = $vitals_res->fetch_assoc();

    // Fetch Medical History
    $stmt = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $hist_res = $stmt->get_result();
    while($row = $hist_res->fetch_assoc()) {
        $history_records[] = $row;
    }
}

// --- Fetch Dynamic Stats for Cards ---
// 1. Pending Appointments
$stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND status IN ('Requested', 'Pending')");
$stmt_pending->bind_param("i", $user_id);
$stmt_pending->execute();
$stats_pending = $stmt_pending->get_result()->fetch_assoc()['count'];

// 2. Patients Today
$today_date = date('Y-m-d');
$stmt_today = $conn->prepare("SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
$stmt_today->bind_param("is", $user_id, $today_date);
$stmt_today->execute();
$stats_today = $stmt_today->get_result()->fetch_assoc()['count'];

// 3. Critical/Pending Lab Reports
$stmt_lab = $conn->prepare("SELECT COUNT(*) as count FROM lab_tests WHERE doctor_id = ? AND status = 'Pending'");
$stmt_lab->bind_param("i", $user_id);
$stmt_lab->execute();
$stats_lab = $stmt_lab->get_result()->fetch_assoc()['count'];

// 4. Total Consults
$stmt_total = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND status = 'Completed'");
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$stats_total = $stmt_total->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - HealCare</title>
    
    <!-- Fonts & Charts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Standard Dashboard Styles -->
    <link rel="stylesheet" href="styles/dashboard.css">
    
    <style>
        /* Doctor Specific UI Enhancements */
        :root {
            --section-gap: 35px;
            --card-radius: 16px;
        }

        .main-content {
            padding: 40px !important;
            gap: var(--section-gap);
            display: flex;
            flex-direction: column;
        }

        .stats-grid {
            gap: 25px !important;
            margin-bottom: 10px;
        }

        .doctor-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .doctor-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .content-section {
            padding: 30px !important;
            border-radius: var(--card-radius) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(30, 41, 59, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
        }

        .section-head {
            margin-bottom: 25px !important;
        }

        .appointment-list {
            gap: 20px !important;
        }

        .appointment-item {
            padding: 20px !important;
            background: rgba(255, 255, 255, 0.03) !important;
            border-radius: 12px !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            transition: transform 0.2s, background 0.2s;
        }

        .appointment-item:hover {
            background: rgba(255, 255, 255, 0.05) !important;
            transform: translateY(-2px);
        }

        .action-btns { display: flex; gap: 12px; }
        .btn-accept { background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        .btn-reject { background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        .btn-consult { background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        
        .btn-accept:hover { background: #047857; }
        .btn-reject:hover { background: #b91c1c; }
        .btn-consult:hover { background: #1d4ed8; }

        .leave-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 15px !important; }
        .leave-type-card { padding: 15px !important; border-radius: 10px !important; }

        .chart-container {
            height: 350px !important;
            margin-top: 20px;
        }

        /* Modal fixes */
        .consultation-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); backdrop-filter: blur(5px);
            display: none; justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-content {
            background: #0f172a; border: 1px solid rgba(255,255,255,0.1);
            width: 90%; max-width: 1100px; height: 90vh; border-radius: 24px;
            display: flex; flex-direction: column; overflow: hidden;
        }
        .modal-header { padding: 30px 40px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 40px !important; gap: 40px !important; overflow-y: auto; flex: 1; display: grid; grid-template-columns: 1fr 1.8fr; }
        
        .patient-sidebar { border-right: 1px solid rgba(255,255,255,0.1); padding-right: 40px !important; }
        .consult-form { display: flex; flex-direction: column; gap: 25px; }
        
        .tab-btn { background: none; border: none; color: #94a3b8; padding: 12px 20px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s; }
        .tab-btn.active { color: #4fc3f7; border-bottom: 2px solid #4fc3f7; }
        .tab-btn:hover:not(.active) { color: #fff; }

        .leave-status { font-size: 12px; padding: 4px 10px; border-radius: 12px; font-weight: 600; }
        .status-granted { background: rgba(16, 185, 129, 0.1); color: #10b981; }

        /* Status Badges */
        .badge-status-Requested, .badge-status-Pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 4px 10px; border-radius: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-status-Approved, .badge-status-Scheduled, .badge-status-Confirmed { background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 4px 10px; border-radius: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-status-Completed { background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-status-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 4px 10px; border-radius: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-status-Lab { background: rgba(168, 85, 247, 0.1); color: #a855f7; padding: 4px 10px; border-radius: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
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

    <header class="secondary-header">
        <div class="brand-section">
            <div class="brand-icon">+</div>
            <div class="brand-name">HealCare</div>
            <div style="margin-left: 20px; padding: 4px 12px; background: rgba(79, 195, 247, 0.15); border: 1px solid #4fc3f7; border-radius: 20px; color: #4fc3f7; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                <?php echo $department; ?> DEPT
            </div>
        </div>
        <div class="user-controls">
            <span class="user-greeting">Welcome, <strong><?php echo $doctor_name; ?></strong></span>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="doctor_dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="doctor_patients.php" class="nav-link"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_prescriptions.php" class="nav-link"><i class="fas fa-file-prescription"></i> Prescriptions</a>
                <a href="doctor_lab_orders.php" class="nav-link"><i class="fas fa-flask"></i> Lab Orders</a>
                <a href="reports_manager.php" class="nav-link"><i class="fas fa-chart-line"></i> Reports</a>
                <a href="doctor_leave.php" class="nav-link"><i class="fas fa-calendar-minus"></i> Apply Leave</a>
                <a href="doctor_settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <h1>Medical Dashboard</h1>
                    <span class="badge status-granted" style="font-size: 11px;"><?php echo $department; ?></span>
                </div>
                <p><?php echo $specialization; ?> • <?php echo $designation; ?></p>
            </div>

            <!-- Stats Grid - Scoped to Department -->
            <!-- Quick Report Upload -->
            <div style="grid-column: span 3; background: linear-gradient(135deg, #0f172a, #1e293b); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="color: #fff; margin-bottom: 5px;"><i class="fas fa-file-upload" style="color: #3b82f6;"></i> Need to archive a report?</h3>
                    <p style="color: #94a3b8; font-size: 13px;">Upload consultation summaries or patient case studies in PDF format.</p>
                </div>
                <button onclick="openReportModal()" class="btn-upload" style="background: #3b82f6; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; display: flex; align-items: center; gap: 10px; border: none; cursor: pointer;">
                    <i class="fas fa-upload"></i> Upload PDF Report
                </button>
            </div>

            <div class="doctor-stats-grid">
                <div class="stat-card">
                    <span class="stat-value"><?php echo str_pad($stats_pending, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="stat-label">Pending (<?php echo $department; ?>)</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo str_pad($stats_today, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="stat-label">Patients Today</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo str_pad($stats_lab, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="stat-label">Pending Lab Reports</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $stats_total; ?></span>
                    <span class="stat-label">Total Dept Consults</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 30px;">
                <!-- Main Activity Area -->
                <div>
                    <!-- Today's Queue Section - ENT Filtered -->
                    <!-- Today's Queue Section - Dynamic -->
                    <div class="content-section">
                        <div class="section-head">
                            <h3><?php echo $department; ?> Appointment Queue</h3>
                            <a href="doctor_appointments.php" style="color: #4fc3f7; font-size: 13px;">Manage All</a>
                        </div>
                        <div class="appointment-list">
                            <?php
                            $today = date('Y-m-d');
                            // Join with users/registrations to get name. Left join profile for age/gender if available.
                            // Assuming patient_profiles exists and has data. If not, use defaults.
                            $stmt = $conn->prepare("
                                SELECT a.*, r.name as patient_name, r.phone, pp.patient_code, pp.gender, pp.date_of_birth 
                                FROM appointments a 
                                JOIN users u ON a.patient_id = u.user_id 
                                JOIN registrations r ON u.registration_id = r.registration_id 
                                LEFT JOIN patient_profiles pp ON a.patient_id = pp.user_id 
                                WHERE a.doctor_id = ? AND a.appointment_date = ? 
                                ORDER BY a.appointment_time ASC
                            ");
                            $stmt->bind_param("is", $user_id, $today);
                            $stmt->execute();
                            $queue_res = $stmt->get_result();

                            if ($queue_res->num_rows > 0) {
                                while ($appt = $queue_res->fetch_assoc()) {
                                    $p_name = htmlspecialchars($appt['patient_name']);
                                    $p_code = $appt['patient_code'] ?: 'N/A';
                                    $p_time = date("h:i A", strtotime($appt['appointment_time']));
                                    
                                    // Calculate Age
                                    $p_age = '--';
                                    if (!empty($appt['date_of_birth'])) {
                                        $dob_date = new DateTime($appt['date_of_birth']);
                                        $now_date = new DateTime();
                                        $p_age = $now_date->diff($dob_date)->y . ' Yrs';
                                    }

                                    $p_gender = $appt['gender'] ?: 'Unknown';
                                    $p_id = $appt['patient_id'];
                                    $a_id = $appt['appointment_id'];
                                    $status = $appt['status'];
                                    
                                    echo '
                                    <div class="appointment-item" style="border-left: 4px solid '.($status == 'Requested' ? '#fbbf24' : '#3b82f6').';">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div class="doc-info">
                                                <h4 style="font-size: 15px;">'.$p_name.' <span style="font-weight: normal; color: #94a3b8; font-size: 13px;">(ID: '.$p_code.')</span></h4>
                                                <p style="font-size: 13px; margin-top: 5px;"><i class="fas fa-clock"></i> '.$p_time.' • Age: '.$p_age.' • Sex: '.$p_gender.' • <span class="badge-status-'.$status.'" style="font-weight:600;">'.$status.'</span></p>
                                            </div>
                                            <div class="action-btns" style="display:flex; gap:10px;">';
                                                if($status == 'Requested' || $status == 'Pending') {
                                                    echo '<form method="POST" style="margin:0;">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <input type="hidden" name="appt_id" value="'.$a_id.'">
                                                            <input type="hidden" name="new_status" value="Approved">
                                                            <button type="submit" class="btn-consult" style="background:#10b981;"><i class="fas fa-check"></i> Approve</button>
                                                          </form>';
                                                } else if($status == 'Approved' || $status == 'Scheduled' || $status == 'Checked-In' || $status == 'Confirmed' || $status == 'Pending Lab' || $status == 'Lab Completed') {
                                                    $is_lab = ($status == 'Pending Lab' || $status == 'Lab Completed');
                                                    $btn_text = $is_lab ? 'Review Lab' : 'Consult';
                                                    $btn_icon = $is_lab ? 'fa-flask' : 'fa-user-md';
                                                    $btn_style = $is_lab ? 'background:#a855f7;' : '';
                                                    echo '<a href="doctor_dashboard.php?patient_id='.$p_id.'&appt_id='.$a_id.'" class="btn-consult" style="'.$btn_style.'"><i class="fas '.$btn_icon.'"></i> '.$btn_text.'</a>';
                                                }
                                    echo '  </div>
                                        </div>
                                    </div>';
                                }
                            } else {
                                echo '<p style="color: #94a3b8; text-align: center; padding: 20px;">No appointments for today.</p>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- ACTIVE CASE: Health Analysis & Medical History -->
                    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 30px;">
                        <!-- Comprehensive Health Analytics -->
                        <div class="content-section">
                            <div class="section-head">
                                <h3>Patient Health Dynamics</h3>
                                <span style="font-size: 12px; color: #10b981; background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: 20px;">Active Analysis</span>
                            </div>
                            <div style="height: 300px; margin-bottom: 20px;">
                                <canvas id="mainHealthChart"></canvas>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                                <div style="text-align: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px;">
                                    <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">Average Pulse</span>
                                    <h3 style="color: #3b82f6;">72 <small>bpm</small></h3>
                                </div>
                                <div style="text-align: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px;">
                                    <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">Peak BP</span>
                                    <h3 style="color: #ef4444;">125/85</h3>
                                </div>
                                <div style="text-align: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px;">
                                    <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">Glucose</span>
                                    <h3 style="color: #10b981;">98 <small>mg/dL</small></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Complete Medical History Timeline -->
                        <div class="content-section">
                            <div class="section-head">
                                <h3>Complete History</h3>
                            </div>
                            <div style="position: relative; padding-left: 20px;">
                                <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 1px; background: rgba(255,255,255,0.1);"></div>
                                
                                <div style="position: relative; margin-bottom: 25px;">
                                    <div style="position: absolute; left: -24px; top: 5px; width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; border: 3px solid #0f172a;"></div>
                                    <span style="font-size: 11px; color: #3b82f6; font-weight: 700;">OCT 2025</span>
                                    <h5 style="color: #fff; margin: 3px 0;">Viral Fever / Fatigue</h5>
                                    <p style="font-size: 12px; color: #94a3b8;">Prescribed: Paracetamol 500mg, Vitamin C.</p>
                                </div>

                                <div style="position: relative; margin-bottom: 25px;">
                                    <div style="position: absolute; left: -24px; top: 5px; width: 8px; height: 8px; background: #fbbf24; border-radius: 50%; border: 3px solid #0f172a;"></div>
                                    <span style="font-size: 11px; color: #fbbf24; font-weight: 700;">AUG 2025</span>
                                    <h5 style="color: #fff; margin: 3px 0;">Blood Lab Review</h5>
                                    <p style="font-size: 12px; color: #94a3b8;">HbA1c: 6.2% (Pre-diabetic Range). Advised Diet.</p>
                                </div>

                                <div style="position: relative; margin-bottom: 25px;">
                                    <div style="position: absolute; left: -24px; top: 5px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; border: 3px solid #0f172a;"></div>
                                    <span style="font-size: 11px; color: #ef4444; font-weight: 700;">MAY 2025</span>
                                    <h5 style="color: #fff; margin: 3px 0;">Acute Hypertension</h5>
                                    <p style="font-size: 12px; color: #94a3b8;">Emergency stabilization required. Enalapril started.</p>
                                </div>
                            </div>
                            <button style="width: 100%; padding: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; font-size: 12px; cursor: pointer; transition: 0.3s; margin-top: 10px;">View Full Longitudinal Record</button>
                        </div>
                    </div>
                </div>

                <!-- Side Panel Area -->
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <!-- Leave Management -->
                    <div class="content-section">
                        <div class="section-head"><h3>Doctor Availability</h3></div>
                        <p style="font-size: 13px; color: var(--text-gray);">Plan your leave or shift timing</p>
                        <div class="leave-grid">
                            <div class="leave-type-card active">Morning</div>
                            <div class="leave-type-card">Noon</div>
                            <div class="leave-type-card">Afternoon</div>
                            <div class="leave-type-card">Full Day</div>
                        </div>
                        <a href="doctor_leave.php" style="width: 100%; margin-top: 15px; padding: 12px; background: #3b82f6; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; text-decoration: none; display: block; text-align: center;">Go to Leave Management</a>
                    </div>

                    <!-- Recent Lab Reports (To Review) -->
                    <!-- Recent Lab Orders -->
                    <div class="content-section">
                        <div class="section-head">
                            <h3>Recent Lab Orders</h3>
                            <a href="doctor_lab_orders.php" style="color: #4fc3f7; font-size: 13px;">View All</a>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php
                            // Fetch both Pending and Completed orders
                            // Using labtest_id DESC as proxy for time if created_at is missing, but prefer created_at if available.
                            
                            $query = "
                                SELECT l.labtest_id, l.test_name, l.status, l.priority, l.report_path, l.result, l.created_at, r.name as patient_name 
                                FROM lab_tests l 
                                JOIN users u ON l.patient_id = u.user_id 
                                JOIN registrations r ON u.registration_id = r.registration_id 
                                WHERE l.doctor_id = ? 
                                ORDER BY l.labtest_id DESC LIMIT 5
                            ";
                            
                            $stmt_reports = $conn->prepare($query);
                            if ($stmt_reports) {
                                $stmt_reports->bind_param("i", $user_id);
                                $stmt_reports->execute();
                                $res_reports = $stmt_reports->get_result();

                                if ($res_reports->num_rows > 0) {
                                    while ($rep = $res_reports->fetch_assoc()) {
                                        $has_pdf = !empty($rep['report_path']);
                                        $pdf_link = $has_pdf ? htmlspecialchars($rep['report_path']) : '';
                                        $status = $rep['status'];
                                        $priority = $rep['priority'] ?? 'Normal';
                                        $result_text = htmlspecialchars($rep['result'] ?? '');
                                        
                                        // Status Colors
                                        $status_color = '#94a3b8'; 
                                        if ($status == 'Completed') $status_color = '#10b981';
                                        elseif ($status == 'Pending' || $status == 'Requested') $status_color = '#fbbf24';
                                        elseif ($status == 'Processing') $status_color = '#f59e0b';

                                        // Priority Colors
                                        $p_bg = 'rgba(59, 130, 246, 0.1)';
                                        $p_color = '#3b82f6';
                                        if (strtolower($priority) === 'urgent') {
                                            $p_bg = 'rgba(239, 68, 68, 0.1)';
                                            $p_color = '#ef4444';
                                        }

                                        // Time Formatting
                                        $created_val = $rep['created_at'] ?? null;
                                        $time_display = 'Recently';
                                        if($created_val) {
                                            $ts = strtotime($created_val);
                                            if(date('Y-m-d') == date('Y-m-d', $ts)) {
                                                $time_display = 'Today, ' . date('h:i A', $ts);
                                            } elseif(date('Y-m-d', strtotime('-1 day')) == date('Y-m-d', $ts)) {
                                                $time_display = 'Yesterday';
                                            } else {
                                                $time_display = date('d M, h:i A', $ts);
                                            }
                                        }
                                        
                                        $has_result = !empty($result_text) || $has_pdf;

                                        $js_test = htmlspecialchars(json_encode($rep['test_name']), ENT_QUOTES, 'UTF-8');
                                        $js_pat = htmlspecialchars(json_encode($rep['patient_name']), ENT_QUOTES, 'UTF-8');
                                        $js_res = htmlspecialchars(json_encode($result_text), ENT_QUOTES, 'UTF-8');
                                        $js_pdf = htmlspecialchars(json_encode($pdf_link), ENT_QUOTES, 'UTF-8');

                                        echo '
                                        <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <h4 style="font-size: 14px; margin-bottom: 4px; color: #f8fafc; font-weight: 600;">' . htmlspecialchars($rep['test_name']) . '</h4>
                                                <p style="font-size: 12px; color: #94a3b8; margin: 0;">
                                                    <span style="color: #cbd5e1;">' . htmlspecialchars($rep['patient_name']) . '</span> • 
                                                    Requested: ' . $time_display . '
                                                </p>
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="margin-bottom: 6px;">
                                                    <span style="font-size: 10px; font-weight: 700; background: '.$p_bg.'; color: '.$p_color.'; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        ' . htmlspecialchars(strtoupper($priority)) . '
                                                    </span>
                                                </div>
                                                <div style="font-size: 11px; font-weight: 600; color: ' . $status_color . ';">
                                                    Status: ' . ucfirst($status) . '
                                                </div>
                                                ' . ($has_result ? '
                                                <div style="margin-top: 6px;">
                                                    <button onclick="viewLabResult(' . $js_test . ', ' . $js_pat . ', ' . $js_res . ', ' . $js_pdf . ')" style="background:transparent; border:none; color: #a855f7; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fas fa-eye"></i> View Results
                                                    </button>
                                                </div>' : '') . '
                                            </div>
                                        </div>';
                                    }
                                } else {
                                    echo '<p style="font-size: 12px; color: #64748b; text-align: center; padding: 10px; background: rgba(255,255,255,0.01); border-radius: 8px;">No recent lab orders found.</p>';
                                }
                            } else {
                                echo '<p style="color:red; font-size:12px;">Query Error</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Lab Result View Modal -->
    <div id="labResultModal" style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2000; justify-content:center; align-items:center;">
        <div style="background:#1e293b; padding:30px; border-radius:16px; width:500px; max-width:90%; position:relative; border:1px solid rgba(255,255,255,0.1);">
            <button onclick="document.getElementById('labResultModal').style.display='none'" style="position:absolute; top:20px; right:20px; background:none; border:none; color:#94a3b8; cursor:pointer; font-size:20px;">&times;</button>
            
            <h3 id="labResTestName" style="color:#fff; margin-bottom:5px;">Test Results</h3>
            <p id="labResPatName" style="color:#94a3b8; font-size:13px; margin-bottom:20px;">Patient Name</p>
            
            <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:8px; margin-bottom:20px;">
                <label style="color:#4fc3f7; font-size:11px; text-transform:uppercase; font-weight:bold; display:block; margin-bottom:5px;">Technician Notes / Findings</label>
                <p id="labResText" style="color:#e2e8f0; font-size:14px; line-height:1.5; white-space: pre-wrap;">No text result available.</p>
            </div>

            <div id="labResPdfArea" style="display:none;">
                <a id="labResPdfLink" href="#" target="_blank" style="display:block; background:#3b82f6; color:#fff; text-align:center; padding:12px; border-radius:8px; text-decoration:none; font-weight:600;">
                    <i class="fas fa-file-pdf"></i> Download Official Report (PDF)
                </a>
            </div>
        </div>
    </div>

    <script>
        function viewLabResult(test, patient, text, pdf) {
            document.getElementById('labResTestName').innerText = test;
            document.getElementById('labResPatName').innerText = "Patient: " + patient;
            document.getElementById('labResText').innerText = text ? text : "No textual findings provided.";
            
            const pdfArea = document.getElementById('labResPdfArea');
            const pdfLink = document.getElementById('labResPdfLink');
            
            if (pdf && pdf !== '') {
                pdfArea.style.display = 'block';
                pdfLink.href = pdf;
            } else {
                pdfArea.style.display = 'none';
            }
            
            document.getElementById('labResultModal').style.display = 'flex';
        }
    </script>
        </main>
    </div>

    <!-- CONSULTATION WORKFLOW MODAL -->
    <div id="consultModal" class="consultation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 id="modalPatientName">Consultation: <?php echo $active_patient['name'] ?? 'Select Patient'; ?></h3>
                    <?php if($active_patient): ?>
                    <p style="color: var(--text-gray); font-size: 13px;">
                        Age: <?php echo $active_patient['age'] ?? 'N/A'; ?> | 
                        Gender: <?php echo $active_patient['gender'] ?? 'N/A'; ?> | 
                        Patient ID: <?php echo $active_patient['patient_code']; ?>
                    </p>
                    <?php endif; ?>
                </div>
                <button onclick="closeConsultation()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Left: History & Summary -->
                <div class="patient-sidebar">
                    <h4 style="margin-bottom: 20px; color: #4fc3f7; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-circle"></i> Patient Summary
                    </h4>
                    
                    <!-- Medical History Section -->
                    <div style="font-size: 13.5px; line-height: 1.6; color: #cbd5e1; background: rgba(59, 130, 246, 0.05); padding: 18px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.1); margin-bottom: 20px;">
                        <span style="font-size: 11px; color: #3b82f6; font-weight: 700; text-transform: uppercase;">Medical History</span>
                        <p style="margin-top: 5px;">
                            <?php echo !empty($active_patient['medical_history']) ? htmlspecialchars($active_patient['medical_history']) : "No previous medical history found for this patient."; ?>
                        </p>
                    </div>

                    <!-- Current Vitals Section -->
                    <h4 style="margin-bottom: 15px; font-size: 14px; color: #94a3b8;">Current Vitals</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                        <div style="background: rgba(30, 41, 59, 0.5); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">Heart Rate</span><br>
                            <strong style="font-size: 20px; color: #fff;">
                                <?php echo isset($latest_vitals['heart_rate']) ? htmlspecialchars($latest_vitals['heart_rate']) : '--'; ?> 
                                <small style="font-weight: normal; font-size: 12px; opacity: 0.6;">bpm</small>
                            </strong>
                        </div>
                        <div style="background: rgba(30, 41, 59, 0.5); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <span style="font-size: 10px; color: #10b981; text-transform: uppercase;">BP</span><br>
                            <strong style="font-size: 20px; color: #fff;">
                                <?php 
                                    if(isset($latest_vitals['blood_pressure_systolic']) && isset($latest_vitals['blood_pressure_diastolic'])) {
                                        echo htmlspecialchars($latest_vitals['blood_pressure_systolic'] . '/' . $latest_vitals['blood_pressure_diastolic']);
                                    } else {
                                        echo '--/--';
                                    }
                                ?>
                            </strong>
                        </div>
                    </div>

                    <!-- Past Consultation records -->
                    <h4 style="margin-bottom: 15px; font-size: 14px; color: #94a3b8;">Past Consultations</h4>
                    <div style="font-size: 13px; color: #cbd5e1; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); max-height: 200px; overflow-y: auto;">
                        <?php if(!empty($history_records)): ?>
                            <?php foreach($history_records as $rec): ?>
                                <div style="margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;">
                                    <strong style="color:#fff; font-size: 11px;"><?php echo date("d M Y", strtotime($rec['created_at'])); ?></strong><br>
                                    <span style="color:#94a3b8;">Diag:</span> <?php echo mb_strimwidth(htmlspecialchars($rec['diagnosis']), 0, 40, "..."); ?><br>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size: 12px; color: #64748b;">No previous consultation records.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Active Consultation -->
                <div class="consult-form">
                    <form method="POST" action="save_consultation.php">
                        <input type="hidden" name="patient_id" value="<?php echo $_GET['patient_id'] ?? ''; ?>">
                        <input type="hidden" name="doctor_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="appointment_id" value="<?php echo $_GET['appt_id'] ?? ''; ?>">
                        
                        <div style="display: flex; gap: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 10px;">
                            <button type="button" class="tab-btn active">Diagnosis & Notes</button>
                        </div>

                        <div>
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">Diagnosis</label>
                            <input type="text" name="diagnosis" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; color: white; margin-bottom: 15px;" placeholder="Primary Diagnosis (e.g. Viral Fever)">
                            
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">Doctor's Internal Notes / Treatment Plan</label>
                            <textarea name="treatment" style="width: 100%; height: 80px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 10px; color: white; padding: 15px; margin-bottom: 15px;" placeholder="Enter clinical observations..."></textarea>
                            
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">Special Notes to Patient</label>
                            <textarea name="special_notes" style="width: 100%; height: 60px; background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 10px; color: white; padding: 15px; margin-bottom: 15px;" placeholder="Advice to patient (e.g. Bed rest for 3 days, drink more water)"></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;"><i class="fas fa-pills" style="color:#10b981;"></i> Prescription</label>
                                <textarea name="prescription" style="width: 100%; height: 80px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 10px; color: white; padding: 15px;" placeholder="Medicine Name - Dosage (e.g. 500mg) - Frequency (e.g. 1-0-1)"></textarea>
                            </div>
                            <div>
                                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;"><i class="fas fa-flask" style="color:#4fc3f7;"></i> Lab Order</label>
                                <div style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px;">
                                    <label style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: #94a3b8; cursor: pointer; margin-bottom: 8px;">
                                        <input type="checkbox" name="lab_required" value="1" id="labCheck" onchange="toggleLabFields(this.checked)"> Lab Test Required
                                    </label>
                                    <input type="text" name="lab_test_name" id="labField" style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 8px; border-radius: 6px; color: white; font-size: 12px; display: none; margin-bottom: 8px;" placeholder="e.g. Blood CBC, X-Ray">
                                    
                                     <select name="lab_category" id="labCat" style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 8px; border-radius: 6px; color: white; font-size: 12px; display: none;">
                                         <option value="">Select Lab Category</option>
                                         <option value="Blood / Pathology Lab">Blood / Pathology Lab (Gen Med, Cardio, Gyn, Derm)</option>
                                         <option value="X-Ray / Imaging Lab">X-Ray / Imaging Lab (Ortho, ENT)</option>
                                         <option value="Diagnostic Lab">Diagnostic Lab (ECG, Hearing, Eye tests)</option>
                                         <option value="Ultrasound Lab">Ultrasound Lab (Gyn, Gen Med)</option>
                                     </select>
                                </div>
                            </div>
                        </div>

                        <script>
                            function toggleLabFields(checked) {
                                const labField = document.getElementById('labField');
                                const labCat = document.getElementById('labCat');
                                
                                if (checked) {
                                    labField.style.display = 'block';
                                    labCat.style.display = 'block';
                                    labField.setAttribute('required', 'required');
                                    labCat.setAttribute('required', 'required');
                                } else {
                                    labField.style.display = 'none';
                                    labCat.style.display = 'none';
                                    labField.removeAttribute('required');
                                    labCat.removeAttribute('required');
                                }
                            }
                        </script>

                        <div style="margin-top: 20px; display: flex; gap: 15px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                            <button type="submit" style="flex: 1; padding: 12px; background: #10b981; border: none; border-radius: 8px; color: white; font-weight: 700; cursor: pointer;"><i class="fas fa-save"></i> Finalize & Close Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if($active_patient): ?>
    <script>
        // Use a more robust way to open the modal
        (function() {
            var modal = document.getElementById('consultModal');
            if (modal) {
                modal.style.display = 'flex';
                console.log("Consultation modal opened for patient: <?php echo addslashes($active_patient['name']); ?>");
            }
        })();
    </script>
    <?php endif; ?>

    <script>
        // Patient Health Dynamics Chart
        const mhcCtx = document.getElementById('mainHealthChart').getContext('2d');
        new Chart(mhcCtx, {
            type: 'line',
            data: {
                labels: ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', 'Now'],
                datasets: [{
                    label: 'Pulse (bpm)',
                    data: [68, 72, 75, 70, 74, 72],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 6
                }, {
                    label: 'Systolic BP',
                    data: [115, 120, 125, 118, 122, 120],
                    borderColor: '#ef4444',
                    backgroundColor: 'transparent',
                    tension: 0.4,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });

        // UI Functions
        let patientVitalsChart = null;

        function openConsultation(name, age, gender) {
            document.getElementById('modalPatientName').innerText = "Consultation: " + name;
            document.getElementById('consultModal').style.display = 'flex';
            
            // Initialize Patient Health Analysis Chart
            if (!patientVitalsChart) {
                const pvCtx = document.getElementById('patientVitalsChart').getContext('2d');
                patientVitalsChart = new Chart(pvCtx, {
                    type: 'line',
                    data: {
                        labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Today'],
                        datasets: [
                            {
                                label: 'Heart Rate',
                                data: [75, 78, 72, 80, 74, 72],
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4
                            },
                            {
                                label: 'Cholesterol',
                                data: [210, 205, 200, 195, 192, 190],
                                borderColor: '#fbbf24',
                                backgroundColor: 'transparent',
                                tension: 0.4,
                                borderDash: [5, 5],
                                pointRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        scales: {
                            y: { display: false },
                            x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } }
                        }
                    }
                });
            }
        }
        function closeConsultation() {
            document.getElementById('consultModal').style.display = 'none';
        }
    </script>

    <?php 
    // Set staff_type for the modal
    $staff_type = 'doctor';
    include 'includes/report_upload_modal.php'; 
    ?>
</body>
</html>