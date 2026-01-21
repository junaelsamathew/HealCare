<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check if profile is complete
$res = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $user_id");
$profile_exists = ($res->num_rows > 0);
$profile = $profile_exists ? $res->fetch_assoc() : null;

// Fetch medical history and visit records
$medical_records = [];
if ($profile_exists) {
    // Current visit records from medical_records table
    // Current visit records from medical_records table
    $records_res = $conn->query("
        SELECT mr.*, r.name as doctor_name, u.user_id as doctor_user_id,
        p.medicine_details as prescription,
        p.prescription_id,
        b.bill_id,
        b.payment_status as bill_status,
        b.bill_type as type_of_bill,
        b.bill_type as type_of_bill,
        b.total_amount as bill_amount,
        mr.appointment_id,
        (SELECT GROUP_CONCAT(test_name SEPARATOR ', ') FROM lab_tests WHERE appointment_id = mr.appointment_id) as lab_tests
        FROM medical_records mr
        LEFT JOIN users u ON mr.doctor_id = u.user_id
        LEFT JOIN registrations r ON u.registration_id = r.registration_id
        LEFT JOIN prescriptions p ON mr.prescription_id = p.prescription_id
        LEFT JOIN billing b ON p.prescription_id = b.reference_id AND (b.bill_type = 'Pharmacy' OR b.bill_type = 'Medical Services')
        WHERE mr.patient_id = $user_id 
        ORDER BY mr.created_at DESC LIMIT 5
    ");
    while ($row = $records_res->fetch_assoc()) {
        $medical_records[] = $row;
    }
}

// Handle Nurse Request from Patient Dashboard
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_nurse_dash'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $admission_id = intval($_POST['admission_id']);
    $stmt_req = $conn->prepare("INSERT INTO nurse_vitals_requests (patient_id, doctor_id, admission_id) VALUES (?, ?, ?)");
    $stmt_req->bind_param("iii", $user_id, $doctor_id, $admission_id);
    if ($stmt_req->execute()) {
        header("Location: patient_dashboard.php?msg=Nurse+Requested");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HealCare</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js for Health Analysis -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Styles -->
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* New Dash Specific Styles */
        .notification-bell {
            position: relative;
            cursor: pointer;
            margin-right: 20px;
        }
        .bell-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 50%;
        }
        .search-container {
            flex: 1;
            max-width: 400px;
            margin: 0 40px;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 10px 20px;
            padding-left: 45px;
            border-radius: 25px;
            border: 1px solid var(--border-color);
            background: rgba(255,255,255,0.05);
            color: white;
        }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
        }
        .chart-container {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-top: 30px;
        }
        .canteen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .food-item {
            background: rgba(255,255,255,0.03);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid transparent;
        }
        .food-item:hover {
            border-color: var(--primary-blue);
            background: rgba(59, 130, 246, 0.1);
        }
        .food-item i { font-size: 24px; margin-bottom: 8px; display: block; color: var(--primary-blue); }
        .food-item span { font-size: 13px; font-weight: 500; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-online { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-offline { background: rgba(156, 163, 175, 0.1); color: #9ca3af; }
        .status-Pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-Requested { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-Scheduled { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-Approved, .status-Confirmed { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-Completed, .status-Checked { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
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

    <!-- Secondary Navy Header -->
    <header class="secondary-header">
        <div class="brand-section">
            <div class="brand-icon">+</div>
            <div class="brand-name">HealCare</div>
        </div>
        <div class="user-controls">
            <?php 
            // Use name from profile if available, else session, else username
            $display_name = $profile['name'] ?? $_SESSION['full_name'] ?? $username;
            ?>
            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($display_name); ?></strong></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="patient_lab_results.php" class="nav-link"><i class="fas fa-flask"></i> Lab Reports</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Patient Dashboard</h1>
                <p>Welcome back! Here's your real-time health and hospital status.</p>
            </div>

            <!-- Enhanced Stats Overview Cards -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <?php
                // 1. Upcoming Appointments
                $upcoming_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = $user_id AND status IN ('Scheduled', 'Approved', 'Confirmed', 'Pending', 'Requested', 'Pending Lab') AND appointment_date >= CURDATE()";
                $upcoming_count = $conn->query($upcoming_sql)->fetch_assoc()['count'];

                // 2. Past Visits
                $past_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = $user_id AND (status = 'Completed' OR (appointment_date < CURDATE() AND status != 'Cancelled'))";
                $past_count = $conn->query($past_sql)->fetch_assoc()['count'];

                // 3. Queue Status (Today)
                $queue_display = 'N/A';
                $today = date('Y-m-d');
                $queue_sql = "SELECT queue_number FROM appointments WHERE patient_id = $user_id AND appointment_date = '$today' AND status NOT IN ('Cancelled', 'Completed') ORDER BY appointment_time ASC LIMIT 1";
                $q_res = $conn->query($queue_sql);
                if ($q_res && $q_res->num_rows > 0) {
                    $queue_display = '#' . $q_res->fetch_assoc()['queue_number'];
                }

                // 4. Bed Status
                $bed_display = 'Not Admitted';
                $bed_color = '#94a3b8'; // gray
                
                // Check admissions table instead of appointments
                $admit_sql = "SELECT status FROM admissions WHERE patient_id = $user_id AND status IN ('Admitted', 'Pending') ORDER BY request_date DESC LIMIT 1";
                $adm_chk = $conn->query($admit_sql);
                
                if ($adm_chk && $adm_chk->num_rows > 0) {
                    $adm_status = $adm_chk->fetch_assoc()['status'];
                    if ($adm_status == 'Admitted') {
                        $bed_display = 'Admitted';
                        $bed_color = '#10b981'; // green
                    } elseif ($adm_status == 'Pending') {
                        $bed_display = 'Pending';
                        $bed_color = '#f59e0b'; // orange
                    }
                }
                ?>
                <div class="stat-card">
                    <span class="stat-value"><?php echo str_pad($upcoming_count, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="stat-label">Upcoming Appts</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo str_pad($past_count, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="stat-label">Past Visits</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value" style="color: #f59e0b;"><?php echo $queue_display; ?></span>
                    <span class="stat-label">Queue Status</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value" style="color: <?php echo $bed_color; ?>;"><?php echo $bed_display; ?></span>
                    <span class="stat-label">Bed Status</span>
                </div>
            </div>

            <!-- Health Trends Chart -->
            <div class="chart-container">
                <div class="section-head">
                    <h3>Health Analysis Trends</h3>
                </div>
                <canvas id="healthChart" height="100"></canvas>
            </div>

            <!-- Two Column Layout: Main Ops & Side Info -->
            
            <?php
            // Check Admission Status
            $adm_sql = "SELECT a.*, r.room_number, w.ward_name, w.ward_type, d.username as doctor_name 
                        FROM admissions a 
                        JOIN rooms r ON a.room_id = r.room_id 
                        JOIN wards w ON r.ward_id = w.ward_id
                        JOIN users d ON a.doctor_id = d.user_id 
                        WHERE a.patient_id = $user_id AND a.status = 'Admitted'";
            $adm_res = $conn->query($adm_sql);
            if ($adm_res && $adm_res->num_rows > 0) {
                $adm = $adm_res->fetch_assoc();
                $adm_days = (new DateTime())->diff(new DateTime($adm['admission_date']))->days ?: 1;
                // Approx rate
                $w_rate = 1000; 
                if($adm['ward_type'] == 'General') $w_rate = 500;
                if($adm['ward_type'] == 'Semi-Private') $w_rate = 1500;
                if($adm['ward_type'] == 'Private') $w_rate = 3000;
                if($adm['ward_type'] == 'ICU') $w_rate = 5000;
                
                $est_bill = $adm_days * $w_rate; 
            ?>
            <div class="content-section" style="margin-bottom: 30px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); border: 1px solid rgba(16, 185, 129, 0.2);">
                <div class="section-head" style="margin-bottom: 15px;">
                    <h3 style="color: #10b981;"><i class="fas fa-bed"></i> Current Admission Status</h3>
                    <span class="badge status-check" style="background:#10b981; color:white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Inpatient</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    <div>
                        <small style="color: #94a3b8;">Ward / Room</small>
                        <h4 style="margin: 0; font-size: 16px; color: white;"><?php echo htmlspecialchars($adm['ward_name'] . ' - ' . $adm['room_number']); ?></h4>
                        <small style="color: #64748b;"><?php echo htmlspecialchars($adm['ward_type']); ?></small>
                    </div>
                    <div>
                        <small style="color: #94a3b8;">Treating Doctor</small>
                        <h4 style="margin: 0; font-size: 16px; color: white;">Dr. <?php echo htmlspecialchars($adm['doctor_name']); ?></h4>
                    </div>
                    <div>
                        <small style="color: #94a3b8;">Admitted Since</small>
                        <h4 style="margin: 0; font-size: 16px; color: white;"><?php echo date('d M, Y', strtotime($adm['admission_date'])); ?></h4>
                        <small style="color: #64748b;"><?php echo $adm_days; ?> Days</small>
                    </div>
                    <div>
                        <small style="color: #94a3b8;">Est. Room Charges</small>
                        <h4 style="margin: 0; font-size: 16px; color: #f59e0b;">₹<?php echo number_format($est_bill); ?></h4>
                        <small style="color: #64748b;">(Excl. medicines/procedures)</small>
                    </div>
                </div>
                <div style="margin-top: 20px; border-top: 1px solid rgba(16, 185, 129, 0.2); padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                    <p style="margin: 0; font-size: 13px; color: #cbd5e1;"><i class="fas fa-user-nurse"></i> Need assistance? You can request a nurse for a vitals check.</p>
                    <form method="POST">
                        <input type="hidden" name="doctor_id" value="<?php echo $adm['doctor_id']; ?>">
                        <input type="hidden" name="admission_id" value="<?php echo $adm['admission_id']; ?>">
                        <button type="submit" name="request_nurse_dash" style="background: #3b82f6; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px;">
                            <i class="fas fa-hand-holding-medical"></i> Request Nurse Check
                        </button>
                    </form>
                </div>
            </div>
            <?php } elseif ($adm_res && $adm_chk->num_rows > 0 && isset($adm_status) && $adm_status == 'Pending') { 
                // Fetch details for Pending Request
                 $pend_sql = "SELECT a.*, d.username as doctor_name 
                 FROM admissions a 
                 JOIN users d ON a.doctor_id = d.user_id 
                 WHERE a.patient_id = $user_id AND a.status = 'Pending'";
                 $pend_res = $conn->query($pend_sql);
                 if($pend_res && $pend_res->num_rows > 0) {
                     $pend = $pend_res->fetch_assoc();
            ?>
            <div class="content-section" style="margin-bottom: 30px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05)); border: 1px solid rgba(245, 158, 11, 0.2);">
                <div class="section-head" style="margin-bottom: 15px;">
                    <h3 style="color: #f59e0b;"><i class="fas fa-procedures"></i> Admission Request Pending</h3>
                    <span class="badge status-check" style="background:#f59e0b; color:white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Processing</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div>
                        <small style="color: #94a3b8;">Requested Ward</small>
                        <h4 style="margin: 0; font-size: 16px; color: white;"><?php echo htmlspecialchars($pend['ward_type_req']); ?></h4>
                    </div>
                    <div>
                        <small style="color: #94a3b8;">Recommending Doctor</small>
                        <h4 style="margin: 0; font-size: 16px; color: white;">Dr. <?php echo htmlspecialchars($pend['doctor_name']); ?></h4>
                    </div>
                    <div>
                        <small style="color: #94a3b8;">Request Date</small>
                        <h4 style="margin: 0; font-size: 16px; color: white;"><?php echo date('d M, h:i A', strtotime($pend['request_date'])); ?></h4>
                    </div>
                </div>
                <p style="margin-top: 15px; font-size: 13px; color: #cbd5e1;"><i class="fas fa-info-circle"></i> Administrators are currently assigning you a room. You will be notified once a bed is confirmed.</p>
            </div>
            <?php } } ?>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 30px;">
                
                <!-- Appointments & Medical History -->
                <div class="content-section">
                    <div class="section-head">
                        <h3>Next Appointment</h3>
                    </div>
                    <div class="appointment-list">
                        <?php
                        // Fetch next upcoming appointment
                        $today_dt = date('Y-m-d H:i:s');
                        $appt_sql = "SELECT a.*, d.specialization, u.username as doc_name, r.name as real_doc_name 
                                     FROM appointments a 
                                     LEFT JOIN users u ON a.doctor_id = u.user_id 
                                     LEFT JOIN doctors d ON u.user_id = d.user_id 
                                     LEFT JOIN registrations r ON u.registration_id = r.registration_id
                                     WHERE a.patient_id = $user_id AND a.status IN ('Scheduled', 'Approved', 'Pending', 'Requested', 'Confirmed', 'Pending Lab') AND a.appointment_date >= '$today_dt'
                                     ORDER BY a.appointment_date ASC LIMIT 1";
                        
                        $appt_res = $conn->query($appt_sql);
                        
                        if ($appt_res && $appt_res->num_rows > 0):
                            $appt = $appt_res->fetch_assoc();
                            $doc_display_name = $appt['real_doc_name'] ?? ('Dr. ' . $appt['doc_name']);
                            $appt_time = date('M d, Y \a\t h:i A', strtotime($appt['appointment_date']));
                            $specialty = $appt['specialization'] ?? $appt['department'] ?? 'General';
                        ?>
                        <div class="appointment-item">
                            <div class="doc-info">
                                <h4><?php echo htmlspecialchars($doc_display_name); ?> <span class="status-badge status-<?php echo $appt['status']; ?>"><?php echo htmlspecialchars($appt['status']); ?></span></h4>
                                <p><?php echo htmlspecialchars($specialty); ?> • <?php echo $appt_time; ?></p>
                                <p style="font-size: 12px; margin-top: 5px;"><i class="fas fa-info-circle"></i> Token: <?php echo htmlspecialchars($appt['queue_number'] ?? 'N/A'); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <a href="my_appointments.php" style="display: block; font-size: 13px; color: #4fc3f7; text-decoration: none; margin-bottom: 10px;">View All</a>
                                <a href="cancel_booking.php?id=<?php echo $appt['appointment_id']; ?>" class="action-cancel">Cancel</a>
                            </div>
                        </div>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center; color: #aaa;">
                                <p>No upcoming appointments.</p>
                                <a href="appointment_form.php" style="color: #4fc3f7; text-decoration: none; font-size: 13px; font-weight: 600;">Book Now</a>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- Lab Reports Download -->
                <div style="margin-top: 40px;">
                    <div class="section-head"><h3>Recent Lab Reports</h3></div>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php
                        $lab_sql = "SELECT * FROM lab_tests WHERE patient_id = $user_id AND status = 'Completed' ORDER BY created_at DESC LIMIT 3";
                        $lab_res = $conn->query($lab_sql);
                        if ($lab_res && $lab_res->num_rows > 0):
                            while ($lab_order = $lab_res->fetch_assoc()):
                        ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                                <div>
                                    <span style="display: block; font-size: 14px; font-weight: 600;"><i class="fas fa-flask" style="margin-right: 10px; color: #4fc3f7;"></i> <?php echo htmlspecialchars($lab_order['test_name']); ?></span>
                                    <small style="color: #94a3b8; font-size: 11px;"><?php echo date('M d, Y', strtotime($lab_order['created_at'])); ?> • <?php echo htmlspecialchars($lab_order['test_type']); ?></small>
                                </div>
                                <?php if ($lab_order['status'] == 'Completed'): ?>
                                    <?php if (!empty($lab_order['report_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($lab_order['report_path']); ?>" target="_blank" style="color: #4fc3f7; font-size: 13px; text-decoration: none; font-weight: 600;"><i class="fas fa-file-pdf"></i> Download Result</a>
                                    <?php else: ?>
                                        <span style="color: #10b981; font-size: 11px; font-weight: 600;"><i class="fas fa-check-circle"></i> Completed</span>
                                    <?php endif; ?>
                                <?php elseif ($lab_order['status'] == 'Processing'): ?>
                                    <span style="color: #f59e0b; font-size: 11px;">Processing...</span>
                                <?php elseif (($lab_order['status'] == 'Pending' || $lab_order['status'] == 'Requested') && ($lab_order['payment_status'] ?? 'Pending') == 'Paid'): ?>
                                    <button onclick="showAuthQR(<?php echo $lab_order['labtest_id']; ?>, '<?php echo htmlspecialchars($lab_order['test_name']); ?>')" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">
                                        <i class="fas fa-qrcode"></i> View Auth Code
                                    </button>
                                <?php else: ?>
                                    <span style="color: #f59e0b; font-size: 11px;"><i class="fas fa-coins"></i> Payment Pending</span>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; else: ?>
                            <div style="text-align: center; padding: 20px; color: #64748b; font-size: 13px; background: rgba(255,255,255,0.01); border-radius: 10px;">
                                No lab reports available yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visit History & Advice Section -->
                <div style="margin-top: 40px;">
                    <div class="section-head"><h3>Visit History & Doctor's Advice</h3></div>
                    <?php if(!empty($medical_records)): ?>
                        <?php foreach($medical_records as $record): ?>
                            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <div>
                                        <h4 style="color: #4fc3f7; margin-bottom: 5px;"><?php echo htmlspecialchars($record['diagnosis']); ?></h4>
                                        <p style="font-size: 12px; color: #94a3b8;">Consulted with <?php echo htmlspecialchars($record['doctor_name']); ?> • <?php echo date('M d, Y', strtotime($record['created_at'])); ?></p>
                                    </div>
                                    <span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; align-self: flex-start;">Visit Completed</span>
                                </div>
                                
                                <?php if(!empty($record['special_notes'])): ?>
                                    <div style="background: rgba(59, 130, 246, 0.05); border-left: 4px solid #3b82f6; padding: 15px; border-radius: 4px; margin-top: 10px;">
                                        <strong style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #3b82f6; margin-bottom: 5px;">Special Advice for you:</strong>
                                        <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($record['special_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if(!empty($record['prescription'])): ?>
                                    <div style="background: rgba(16, 185, 129, 0.05); border-left: 4px solid #10b981; padding: 15px; border-radius: 4px; margin-top: 10px;">
                                        <strong style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #10b981; margin-bottom: 5px;"><i class="fas fa-pills"></i> Prescribed Medication:</strong>
                                        <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5; margin-bottom: 15px;"><?php echo nl2br(htmlspecialchars($record['prescription'])); ?></p>
                                        
                                        <div style="border-top: 1px solid rgba(16, 185, 129, 0.2); padding-top: 10px; display:flex; gap:10px;">
                                            <?php if($record['bill_id']): ?>
                                                <?php if($record['bill_status'] == 'Paid'): ?>
                                                    <a href="print_receipt.php?bill_id=<?php echo $record['bill_id']; ?>" target="_blank" style="padding: 8px 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration:none;">
                                                        <i class="fas fa-download"></i> <?php echo ($record['type_of_bill'] == 'Medical Services') ? 'Download Combined Receipt' : 'Download Receipt'; ?>
                                                    </a>
                                                    <span style="padding: 8px 0; color: #10b981; font-size: 12px; font-weight: 600;"><i class="fas fa-check-circle"></i> Paid</span>
                                                <?php else: ?>
                                                    <a href="payment_gateway.php?bill_id=<?php echo $record['bill_id']; ?>" style="padding: 8px 15px; background: #f59e0b; color: #000; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration:none;">
                                                        <i class="fas fa-credit-card"></i> Pay Bill (₹<?php echo number_format($record['bill_amount']); ?>)
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php 
                                                // Check for Lab Tests to decide if we need a Combined Bill
                                                $has_labs = !empty($record['lab_tests']);
                                                $action_type = $has_labs ? 'Combined' : 'Pharmacy';
                                                
                                                // Check if bill exists via direct query safely
                                                $chk_ref = intval($record['prescription_id']);
                                                $chk_bill = null;
                                                if($chk_ref > 0) {
                                                    $chk_bill = $conn->query("SELECT bill_id FROM billing WHERE reference_id = $chk_ref AND (bill_type='Pharmacy' OR bill_type='Medical Services')");
                                                }
                                                
                                                if($chk_bill && $chk_bill->num_rows > 0) {
                                                     echo '<a href="patient_dashboard.php" style="font-size:12px; color:#3b82f6;"><i class="fas fa-sync"></i> Refresh Status</a>';
                                                } else {
                                                
                                                $btn_text = $has_labs ? 'Generate Combined Bill' : 'Generate Bill';
                                                // High visibility for Combined
                                                $btn_style = $has_labs ? 'background: #ef4444; color: white; border: 2px solid white; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.4);' : 'background: #3b82f6; color: white;';
                                                ?>
                                                <form action="generate_bill.php" method="POST" target="_blank" style="margin-top:5px;">
                                                    <input type="hidden" name="patient_id" value="<?php echo $user_id; ?>">
                                                    <input type="hidden" name="doctor_id" value="<?php echo $record['doctor_user_id']; ?>">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $record['appointment_id']; ?>">
                                                    <input type="hidden" name="reference_id" value="<?php echo $record['prescription_id']; ?>">
                                                    <input type="hidden" name="bill_type" value="<?php echo $action_type; ?>">
                                                    <input type="hidden" name="amount" value="0">
                                                    <button type="submit" style="padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; <?php echo $btn_style; ?>">
                                                        <i class="fas fa-file-invoice-dollar"></i> <?php echo $btn_text; ?>
                                                    </button>
                                                </form>
                                                <?php } ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if(!empty($record['lab_tests'])): ?>
                                    <div style="background: rgba(79, 195, 247, 0.05); border-left: 4px solid #4fc3f7; padding: 15px; border-radius: 4px; margin-top: 10px;">
                                        <strong style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #4fc3f7; margin-bottom: 5px;"><i class="fas fa-flask"></i> Requested Lab Tests:</strong>
                                        <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5;"><?php echo htmlspecialchars($record['lab_tests']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; color: #aaa;">
                            <p>No medical visit history available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

                <!-- Canteen & Notifications -->
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <!-- Canteen Status Card -->
                    <div style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 25px; border-radius: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="font-size: 16px; font-weight: 700;">Local Canteen</h3>
                            <a href="canteen.php" style="font-size: 12px; color: #4fc3f7; text-decoration: none;">Order Food</a>
                        </div>
                        
                        <?php
                        $latest_canteen = $conn->query("
                            SELECT co.*, cm.item_name 
                            FROM canteen_orders co
                            JOIN canteen_menu cm ON co.menu_id = cm.menu_id
                            WHERE co.patient_id = $user_id
                            ORDER BY co.created_at DESC LIMIT 1
                        ");
                        if ($latest_canteen && $latest_canteen->num_rows > 0):
                            $c_order = $latest_canteen->fetch_assoc();
                            $c_progress = 20;
                            if($c_order['order_status'] == 'Preparing') $c_progress = 60;
                            if($c_order['order_status'] == 'Delivered') $c_progress = 100;
                        ?>
                            <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid rgba(79, 195, 247, 0.1);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span style="font-size: 13px; color: #fff; font-weight: 600;"><?php echo htmlspecialchars($c_order['item_name']); ?></span>
                                    <span class="status-badge status-<?php echo $c_order['order_status']; ?>" style="font-size: 10px;"><?php echo strtoupper($c_order['order_status']); ?></span>
                                </div>
                                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden; margin-bottom: 8px;">
                                    <div style="width: <?php echo $c_progress; ?>%; height: 100%; background: #4fc3f7; box-shadow: 0 0 10px rgba(79, 195, 247, 0.4);"></div>
                                </div>
                                <p style="font-size: 11px; color: #94a3b8; margin: 0;">Last updated: <?php echo date('h:i A', strtotime($c_order['created_at'])); ?></p>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: #64748b; font-size: 13px;">
                                <i class="fas fa-utensils" style="font-size: 24px; margin-bottom: 10px; display: block; opacity: 0.3;"></i>
                                No active food orders.
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="#" style="padding: 10px 20px; background: rgba(79, 195, 247, 0.1); border: 1px solid #4fc3f7; border-radius: 8px; color: #4fc3f7; text-decoration: none; font-size: 13px; font-weight: 600; text-align: center;">Submit Feedback</a>
                </div>
            </div>

        </main>
    </div>

    <!-- FontAwesome for icons -->
    <!-- Auth QR Modal -->
    <div id="qrModal" style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: #1e293b; padding: 30px; border-radius: 20px; text-align: center; max-width: 300px; width: 90%; border: 1px solid rgba(255,255,255,0.1);">
            <h3 style="color: #fff; margin-bottom: 5px;">Test Authorization</h3>
            <p id="qrTestName" style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;"></p>
            
            <div style="background: white; padding: 10px; border-radius: 10px; display: inline-block; margin-bottom: 20px;">
                <img id="qrImage" src="" alt="Auth QR" style="width: 180px; height: 180px;">
            </div>
            
            <p style="color: #10b981; font-size: 12px; font-weight: 600; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> Payment Verified
            </p>
            
            <button onclick="document.getElementById('qrModal').style.display='none'" style="background: rgba(255,255,255,0.1); color: white; border: none; padding: 10px 30px; border-radius: 8px; cursor: pointer;">Close</button>
        </div>
    </div>

    <script>
        function showAuthQR(id, name) {
            document.getElementById('qrTestName').textContent = name;
            document.getElementById('qrImage').src = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=HealCare_AUTH_" + id;
            document.getElementById('qrModal').style.display = 'flex';
        }
    </script>
</body>
</html>