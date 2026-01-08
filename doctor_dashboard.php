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

$doctor_name = "Dr. " . htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);

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
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-value">08</span>
                    <span class="stat-label">Pending (<?php echo $department; ?>)</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">14</span>
                    <span class="stat-label">Patients Today</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">03</span>
                    <span class="stat-label">Critical Dept Reports</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">120+</span>
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
                                                } else if($status == 'Approved' || $status == 'Scheduled' || $status == 'Checked-In') {
                                                    echo '<a href="doctor_dashboard.php?patient_id='.$p_id.'&appt_id='.$a_id.'" class="btn-consult"><i class="fas fa-user-md"></i> Consult</a>';
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
                    <div class="content-section">
                        <div class="section-head"><h3>Reports to Review</h3></div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div style="background: rgba(255,255,255,0.02); padding: 10px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                <div><span style="font-size: 13px; font-weight: 600;">Rahul K. - CBC</span><br><span style="font-size: 11px; color: #ef4444;">Abnormal Findings</span></div>
                                <a href="#" style="color: #4fc3f7;"><i class="fas fa-file-pdf"></i> View</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                                document.getElementById('labField').style.display = checked ? 'block' : 'none';
                                document.getElementById('labCat').style.display = checked ? 'block' : 'none';
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
</body>
</html>