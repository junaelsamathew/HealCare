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
                    <div class="content-section">
                        <div class="section-head">
                            <h3><?php echo $department; ?> Appointment Queue</h3>
                            <a href="doctor_appointments.php" style="color: #4fc3f7; font-size: 13px;">Manage All</a>
                        </div>
                        <div class="appointment-list">
                            <div class="appointment-item" style="border-left: 4px solid #3b82f6;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div class="doc-info">
                                        <h4 style="font-size: 15px;">Dileep Mathew <span style="font-weight: normal; color: #94a3b8; font-size: 13px;">(ID: HC-P-2026-9901)</span></h4>
                                        <p style="font-size: 13px; margin-top: 5px;"><i class="fas fa-clock"></i> 09:30 AM • <i class="fas fa-notes-medical"></i> Chief Complaint: Chest Pain/Palpitations</p>
                                    </div>
                                    <div class="action-btns">
                                        <button class="btn-consult" onclick="openConsultation('Dileep Mathew', '32', 'Male')">Treat Patient</button>
                                    </div>
                                </div>
                            </div>
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
                        <button style="width: 100%; margin-top: 15px; padding: 10px; background: #3b82f6; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer;">Submit Leave Request</button>
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
                    <h3 id="modalPatientName">Consultation: John Doe</h3>
                    <p style="color: var(--text-gray); font-size: 13px;">Age: 32 | Gender: Male | Patient ID: HC-P-2026-9901</p>
                </div>
                <button onclick="closeConsultation()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Left: History & Summary -->
                <div class="patient-sidebar">
                    <h4 style="margin-bottom: 20px; color: #4fc3f7; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-circle"></i> Patient Summary
                    </h4>
                    <div style="font-size: 13.5px; line-height: 1.8; color: #cbd5e1; background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                        <p><strong>Medical History:</strong> Chronic Hypertension, Diabetes Mellitus Type 2 (Controlled)</p>
                        <p><strong>Past Visits:</strong> 5 (Last: 1 month ago)</p>
                        <p><strong>Allergies:</strong> Penicillin, Shellfish</p>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <h4 style="margin-bottom: 15px; font-size: 14px; color: #94a3b8;">Current Vitals</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div style="background: rgba(37, 99, 235, 0.1); padding: 15px; border-radius: 12px; border: 1px solid rgba(37, 99, 235, 0.2);">
                                <span style="font-size: 11px; color: #93c5fd; text-transform: uppercase;">Heart Rate</span><br>
                                <strong style="font-size: 18px; color: #fff;">72 <small style="font-weight: normal; font-size: 12px; opacity: 0.6;">bpm</small></strong>
                            </div>
                            <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2);">
                                <span style="font-size: 11px; color: #6ee7b7; text-transform: uppercase;">BP</span><br>
                                <strong style="font-size: 18px; color: #fff;">120/80</strong>
                            </div>
                            <div style="background: rgba(251, 191, 36, 0.1); padding: 15px; border-radius: 12px; border: 1px solid rgba(251, 191, 36, 0.2);">
                                <span style="font-size: 11px; color: #fcd34d; text-transform: uppercase;">Cholesterol</span><br>
                                <strong style="font-size: 18px; color: #fff;">190 <small style="font-weight: normal; font-size: 12px; opacity: 0.6;">mg/dL</small></strong>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <h4 style="margin-bottom: 15px; font-size: 14px; color: #94a3b8;">Health Analysis Trends</h4>
                        <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <canvas id="patientVitalsChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right: Active Consultation -->
                <div class="consult-form">
                    <div style="display: flex; gap: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 10px;">
                        <button class="tab-btn active">Diagnosis & Notes</button>
                        <button class="tab-btn">Prescription (Rx)</button>
                        <button class="tab-btn">Lab/Tests</button>
                        <button class="tab-btn">Follow-up</button>
                    </div>

                    <div>
                        <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">Doctor's Notes / Summary</label>
                        <textarea style="width: 100%; height: 100px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 10px; color: white; padding: 15px;" placeholder="Enter clinical observations and summary..."></textarea>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <label style="font-size: 13px; font-weight: 600;">Prescribe Medications</label>
                            <button style="background: var(--primary-blue); color: white; border: none; font-size: 10px; padding: 4px 10px; border-radius: 4px;">+ Add Medicine</button>
                        </div>
                        <input type="text" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; color: white;" placeholder="Search medicine (e.g. Paracetamol)...">
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1;">
                            <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;">Order Lab Test</label>
                            <input type="text" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; color: white;" placeholder="CBC, Lipids, X-Ray...">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;">Refer Next Doctor</label>
                            <input type="text" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; color: white;" placeholder="Search specialists...">
                        </div>
                    </div>

                    <div style="margin-top: auto; display: flex; gap: 15px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <button style="flex: 1; padding: 12px; background: #10b981; border: none; border-radius: 8px; color: white; font-weight: 700;">Finalize Consultation & Print Rx</button>
                        <button style="padding: 12px 25px; border: 1px solid #ef4444; border-radius: 8px; color: #ef4444; background: none;">Next Appt Request</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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