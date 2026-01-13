<?php
session_start();
include 'includes/db_connect.php';

// Authentication Check
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$staff_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);

// Fetch Patients for Dropdowns
$pat_query = "SELECT u.user_id, r.name, pp.patient_code FROM users u JOIN registrations r ON u.registration_id = r.registration_id LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id WHERE u.role = 'patient' ORDER BY r.name ASC";
$pat_res = $conn->query($pat_query);
$patient_list = [];
if($pat_res) {
    while($p = $pat_res->fetch_assoc()) {
        $patient_list[] = $p;
    }
}

// Handle POST Requests
$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. New Patient Registration
    if (isset($_POST['action']) && $_POST['action'] === 'register_patient') {
        $p_name = mysqli_real_escape_string($conn, $_POST['p_name']);
        $p_phone = mysqli_real_escape_string($conn, $_POST['p_phone']);
        $p_dept = mysqli_real_escape_string($conn, $_POST['p_dept']);
        $p_age = mysqli_real_escape_string($conn, $_POST['p_age']);
        $p_gender = mysqli_real_escape_string($conn, $_POST['p_gender']);

        // Generate Patient ID
        $year = date("Y");
        $rand = rand(1000, 9999);
        $p_code = "HC-P-{$year}-{$rand}";
        
        // Create User Account (Optional, usually for Portal Access)
        // For Quick Reg, we might just store in patient_profiles linked to a new user
        // Password = Phone number as default
        $password = password_hash($p_phone, PASSWORD_DEFAULT);
        
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO users (username, password, role, status) VALUES ('$p_code', '$password', 'patient', 'Active')");
            $new_user_id = $conn->insert_id;
            
            $conn->query("INSERT INTO patient_profiles (user_id, patient_code, name, phone, gender, department_visit) VALUES ($new_user_id, '$p_code', '$p_name', '$p_phone', '$p_gender', '$p_dept')");
            $new_patient_id = $conn->insert_id;
            
            // Auto Book Appointment if desired? Let's just register.
            $msg = "Patient Registered! ID: <strong>$p_code</strong>";
            $msg_type = "success";
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error: " . $e->getMessage();
            $msg_type = "error";
        }
    }

    // 2. Book Appointment
    if (isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
        $p_id = (int)$_POST['patient_id'];
        $p_dept = mysqli_real_escape_string($conn, $_POST['b_dept']);
        $b_date = mysqli_real_escape_string($conn, $_POST['b_date']);
        $b_time = mysqli_real_escape_string($conn, $_POST['b_time']);
        
        // Validation
        if(empty($p_id) || empty($b_date) || empty($b_time)) {
             $msg = "Please select a patient and valid date/time.";
             $msg_type = "error";
        } else {
            $full_datetime = $b_date . ' ' . $b_time;
            
            // Check if slot taken (simple check)
            $check = $conn->query("SELECT appointment_id FROM appointments WHERE doctor_id = (SELECT user_id FROM doctors WHERE department = '$p_dept' LIMIT 1) AND appointment_date = '$full_datetime'");
             
            // Assign a doctor (Simple Round Robin or First Available Default)
            // For now, getting ANY doctor from that Dept
            $doc_res = $conn->query("SELECT user_id FROM doctors WHERE department = '$p_dept' LIMIT 1");
            $doc_id = ($doc_res && $doc_res->num_rows > 0) ? $doc_res->fetch_assoc()['user_id'] : 0; // 0 or Admin if no doctor found
            
            if($doc_id == 0) {
                 // Fallback: try to find any doctor or set null
                 // Just proceed, assuming 0 is handled or handled later
            }

            // Get Queue Number / Token
            $q_res = $conn->query("SELECT MAX(queue_number) as max_q FROM appointments WHERE appointment_date LIKE '$b_date%' AND department = '$p_dept'");
            $max_q = $q_res->fetch_assoc()['max_q'];
            $token_no = $max_q + 1;

            if ($conn->query("INSERT INTO appointments (patient_id, doctor_id, department, appointment_date, appointment_time, queue_number, status) VALUES ($p_id, $doc_id, '$p_dept', '$b_date', '$b_time', $token_no, 'Scheduled')")) {
                $msg = "Appointment Booked! Token: #$token_no";
                $msg_type = "success";
            } else {
                $msg = "Error Booking: " . $conn->error;
                $msg_type = "error";
            }
        }
    }

    // 2.5 Generate Bill
    if (isset($_POST['action']) && $_POST['action'] === 'generate_bill') {
        $p_id = (int)$_POST['patient_id'];
        $service = mysqli_real_escape_string($conn, $_POST['bill_type']);
        $amount = (float)$_POST['amount'];
        $pay_mode = mysqli_real_escape_string($conn, $_POST['payment_mode']);
        $pay_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
        $date = date('Y-m-d');

        if ($conn->query("INSERT INTO billing (patient_id, bill_type, total_amount, payment_mode, payment_status, bill_date) VALUES ($p_id, '$service', $amount, '$pay_mode', '$pay_status', '$date')")) {
             $msg = "Bill Generated Successfully!";
             $msg_type = "success";
        } else {
             $msg = "Billing Error: " . $conn->error;
             $msg_type = "error";
        }
    }

    // 3. Update Status (Check In / Check Out)
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $appt_id = $_POST['appt_id'];
        $new_status = $_POST['status'];
        $conn->query("UPDATE appointments SET status = '$new_status' WHERE appointment_id = $appt_id");
        $msg = "Status Updated to $new_status";
        $msg_type = "success";
    }

    // 4. Cancel / Reschedule
    if (isset($_POST['action']) && $_POST['action'] === 'reschedule') {
        $appt_id = $_POST['appt_id'];
        $new_date = $_POST['new_date'];
        $new_time = $_POST['new_time'];
        $full_datetime = $new_date . ' ' . $new_time;
        
        $conn->query("UPDATE appointments SET appointment_date = '$full_datetime', status = 'Rescheduled' WHERE appointment_id = $appt_id");
        $msg = "Appointment Rescheduled.";
        $msg_type = "success";
    }
}

// Fetch Data
// Fetch Data
$today = date('Y-m-d');
// JOIN users -> registrations to get name properly
$queue_sql = "
    SELECT a.*, r.name as patient_name 
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    JOIN registrations r ON u.registration_id = r.registration_id
    WHERE DATE(a.appointment_date) = '$today'
    ORDER BY a.appointment_time ASC
";
$queue_result = $conn->query($queue_sql);

$cal_sql = "
    SELECT a.*, r.name as patient_name 
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    JOIN registrations r ON u.registration_id = r.registration_id
    WHERE DATE(a.appointment_date) >= '$today'
    ORDER BY a.appointment_date ASC LIMIT 10
";
$calendar_result = $conn->query($cal_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        /* Inherit Styles from previous dashboard.css + specific overrides */
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent-blue: #3b82f6;
            --border-soft: rgba(255, 255, 255, 0.05);
        }

        .reception-top-bar { background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .secondary-nav { background: #0f172a; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .btn-logout-alt { background: transparent; border: 1px solid #3b82f6; color: #fff; padding: 8px 25px; border-radius: 20px; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .btn-logout-alt:hover { background: #3b82f6; }
        .dashboard-body { display: grid; grid-template-columns: 260px 1fr; height: calc(100vh - 140px); background: #020617; }
        .side-nav { background: #020617; padding: 20px 0; border-right: 1px solid var(--border-soft); }
        .nav-item { display: flex; align-items: center; padding: 15px 30px; color: #94a3b8; text-decoration: none; font-size: 14px; gap: 15px; transition: 0.3s; }
        .nav-item.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-left: 4px solid #3b82f6; }
        .main-ops { padding: 40px; overflow-y: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card-new { background: #0f172a; padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); }
        .stat-card-new h2 { font-size: 24px; color: #3b82f6; margin-bottom: 5px; }
        
        /* Queue Table */
        .queue-table { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .queue-table th { text-align: left; padding: 15px; color: #64748b; font-size: 12px; border-bottom: 1px solid var(--border-soft); }
        .queue-table td { padding: 18px 15px; color: #cbd5e1; font-size: 14px; border-bottom: 1px solid var(--border-soft); }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        
        .pill-Scheduled { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .pill-Waiting { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .pill-Checked-In { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .pill-Completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .pill-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .btn-check { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; border: none; cursor: pointer; color: white; display: inline-flex; align-items: center; gap: 5px; }
        .btn-green { background: #10b981; }
        .btn-red { background: #ef4444; }
        .btn-blue { background: #3b82f6; }
        .btn-orange { background: #f59e0b; }

        /* Calendar Widget */
        .calendar-widget { background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 20px; margin-top: 30px; }
        .quick-actions-bar { background: rgba(30, 41, 59, 0.4); padding: 30px; border-radius: 12px; border: 1px solid var(--border-soft); }
        .btn-action-main { background: rgba(255,255,255,0.03); border: 1px solid var(--border-soft); color: #fff; padding: 10px 25px; border-radius: 20px; cursor: pointer; font-size: 13px; transition: 0.3s; margin-right: 10px; }
        .btn-action-main:hover { border-color: #3b82f6; color: #3b82f6; }

        /* Modals */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-box { background: #0f172a; border: 1px solid var(--border-soft); width: 90%; max-width: 500px; padding: 30px; border-radius: 20px; }
        .form-group-staff { margin-bottom: 15px; }
        .form-group-staff label { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 5px; }
        .form-group-staff input, .form-group-staff select { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border-soft); padding: 10px; border-radius: 8px; color: #fff; }
    </style>
</head>
<body>

    <!-- Top Bar -->
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

    <!-- Secondary Nav -->
    <div class="secondary-nav">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: #3b82f6; color:#fff; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold;">+</div>
            <h2 style="color:#fff; font-size:20px;">HealCare</h2>
        </div>
        <div style="display: flex; align-items: center; gap: 30px;">
            <span class="staff-label"><?php echo $staff_name; ?></span>
            <a href="logout.php" class="btn-logout-alt">Log Out</a>
        </div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <a href="?section=dashboard" class="nav-item <?php echo (!isset($_GET['section']) || $_GET['section'] == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="?section=queue" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'queue') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Reception / Queue</a>
            <a href="?section=billing" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'billing') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Patient Billing</a>
            <a href="?section=reports" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'reports') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <?php if($msg): ?>
                <div style="background: <?php echo $msg_type == 'success' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; color: #fff; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_GET['section']) || $_GET['section'] == 'dashboard'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Reception Dashboard</h1>
                    <p style="color:#64748b; font-size:14px;">Manage patient flow and appointments.</p>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions-bar">
                    <h4 style="color:#fff; font-size:16px; margin-bottom: 15px;">Quick Actions</h4>
                    <div style="display: flex; flex-wrap: wrap;">
                        <button class="btn-action-main" onclick="openModal('regModal')"><i class="fas fa-user-plus"></i> New Registration</button>
                        <button class="btn-action-main" onclick="openModal('bookModal')"><i class="fas fa-calendar-plus"></i> Book Appointment</button>
                        <button class="btn-action-main" onclick="openModal('billModal')"><i class="fas fa-file-invoice"></i> Generate Bill</button>
                        <button class="btn-action-main" onclick="openReportModal()"><i class="fas fa-upload"></i> Upload Report</button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
                    <!-- Live Queue -->
                    <div>
                        <h3 style="color:#fff; margin-bottom: 20px;">Today's Queue & Appointments</h3>
                        <table class="queue-table">
                            <thead>
                                <tr>
                                    <th>Token</th>
                                    <th>Patient Name</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($queue_result->num_rows > 0): ?>
                                    <?php while($appt = $queue_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($appt['queue_number'] ?? $appt['appointment_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appt['appointment_time'] ?? $appt['appointment_date'])); ?></td>
                                            <td><span class="status-pill pill-<?php echo $appt['status']; ?>"><?php echo $appt['status']; ?></span></td>
                                            <td>
                                                <div style="display: flex; gap: 5px;">
                                                    <!-- Check In / Out Logic -->
                                                    <?php if($appt['status'] == 'Scheduled'): ?>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                            <input type="hidden" name="status" value="Checked-In">
                                                            <button class="btn-check btn-green"><i class="fas fa-check"></i> Check In</button>
                                                        </form>
                                                        <button class="btn-check btn-orange" onclick="openReschedule(<?php echo $appt['appointment_id']; ?>)"><i class="fas fa-clock"></i></button>
                                                    <?php elseif($appt['status'] == 'Checked-In'): ?>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                            <input type="hidden" name="status" value="Completed">
                                                            <button class="btn-check btn-blue"><i class="fas fa-sign-out-alt"></i> Check Out</button>
                                                        </form>
                                                    <?php elseif($appt['status'] == 'Completed'): ?>
                                                        <span style="color: #10b981; font-size: 11px;"><i class="fas fa-check-double"></i> Done</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($appt['status'] != 'Cancelled' && $appt['status'] != 'Completed'): ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                            <input type="hidden" name="status" value="Cancelled">
                                                            <button class="btn-check btn-red" title="Cancel"><i class="fas fa-times"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align:center; padding: 20px;">No appointments for today.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Calendar Widget -->
                    <div class="calendar-widget">
                        <h4 style="color: #fff; margin-bottom: 15px;"><i class="fas fa-calendar-alt"></i> Upcoming Schedule</h4>
                        <div style="font-size: 12px; color: #cbd5e1; display: flex; flex-direction: column; gap: 10px;">
                            <?php if ($calendar_result->num_rows > 0): ?>
                                <?php while($cal = $calendar_result->fetch_assoc()): ?>
                                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-soft); padding-bottom: 5px;">
                                        <span><?php echo date('M d, h:i A', strtotime($cal['appointment_date'])); ?> - <?php echo htmlspecialchars($cal['patient_name']); ?></span>
                                        <span style="color: #3b82f6;"><?php echo htmlspecialchars($cal['department']); ?></span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No upcoming appointments.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($_GET['section'] == 'reports'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Reception Reports</h1>
                    <p style="color:#64748b; font-size:14px;">Access appointment, registration, and check-in analytics.</p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                    <!-- Appointment Report -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=receptionist_appointment'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px;">Appointment Booking</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Scheduled appointments log</p>
                            </div>
                            <i class="fas fa-calendar-check" style="font-size:24px; color: var(--accent-blue);"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Daily Bookings Count</li>
                            <li>Cancellation Stats</li>
                        </ul>
                        <button class="btn-logout-alt" style="width:100%; text-align:center;">View Report</button>
                    </div>

                    <!-- Registration Report -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=receptionist_registration'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px;">Patient Registration</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">New patient onboarding</p>
                            </div>
                            <i class="fas fa-user-plus" style="font-size:24px; color: #10b981;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>New Patients Added</li>
                            <li>Department Allocation</li>
                        </ul>
                        <button class="btn-logout-alt" style="width:100%; text-align:center; border-color: #10b981; color: #10b981;">View Report</button>
                    </div>

                    <!-- Check-In Report -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=receptionist_checkin'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px;">Daily Check-In/Out</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Visitor flow tracking</p>
                            </div>
                            <i class="fas fa-door-open" style="font-size:24px; color: #f59e0b;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Patient Arrival Times</li>
                            <li>Consultation Completions</li>
                        </ul>
                        <button class="btn-logout-alt" style="width:100%; text-align:center; border-color: #f59e0b; color: #f59e0b;">View Report</button>
                    </div>
                </div>

            <?php elseif ($_GET['section'] == 'billing'): ?>
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="color:#fff; font-size: 28px;">Patient Billing</h1>
                        <p style="color:#64748b; font-size:14px;">Create and manage invoices.</p>
                    </div>
                    <button class="btn-action-main" onclick="openModal('billModal')" style="background: #3b82f6; border-color: #3b82f6;"><i class="fas fa-plus"></i> Create New Bill</button>
                </div>

                <div class="stat-card-new" style="background: rgba(30, 41, 59, 0.4);">
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Service/Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $bill_sql = "
                                SELECT b.*, r.name as patient_name 
                                FROM billing b
                                JOIN users u ON b.patient_id = u.user_id
                                JOIN registrations r ON u.registration_id = r.registration_id
                                ORDER BY b.bill_date DESC LIMIT 50
                            ";
                            $bill_res = $conn->query($bill_sql);
                            if($bill_res && $bill_res->num_rows > 0):
                                while($bill = $bill_res->fetch_assoc()):
                            ?>
                            <tr>
                                <td>#INV-<?php echo str_pad($bill['bill_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></td>
                                <td><strong style="color:white;"><?php echo htmlspecialchars($bill['patient_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($bill['bill_type']); ?></td>
                                <td>$<?php echo number_format($bill['total_amount'], 2); ?></td>
                                <td>
                                    <?php if($bill['payment_status'] == 'Paid'): ?>
                                        <span class="status-pill pill-Completed">Paid</span>
                                    <?php else: ?>
                                        <span class="status-pill pill-Waiting">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><button style="background:none; border:none; color:#3b82f6; cursor:pointer;"><i class="fas fa-print"></i></button></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="7" style="text-align:center; padding:30px;">No billing records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                    </table>
                </div>

            <?php elseif ($_GET['section'] == 'queue'): ?>
                <!-- Dedicated Queue Section -->
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Queue Management</h1>
                    <p style="color:#64748b; font-size:14px;">Real-time appointment tracking and status updates.</p>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <div>
                         <?php 
                         // Reuse the Queue Table logic (duplicate for now or include)
                         // For simplicity and robustness in this tool usage, we duplicate the table marking clearly
                         ?>
                         <h3 style="color:#fff; margin-bottom: 20px;">Today's Full Queue</h3>
                         <div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--border-soft);">
                             <table class="queue-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th>Token</th>
                                        <th>Patient Name</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Reset pointer just in case or re-fetch if needed, but result object can be iterated once. 
                                    // Since we are in an elseif, the dashboard one wanst iterated.
                                    if ($queue_result->num_rows > 0): 
                                        $queue_result->data_seek(0); // Reset pointer
                                        while($appt = $queue_result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($appt['queue_number'] ?? $appt['appointment_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appt['appointment_time'] ?? $appt['appointment_date'])); ?></td>
                                            <td><span class="status-pill pill-<?php echo $appt['status']; ?>"><?php echo $appt['status']; ?></span></td>
                                            <td>
                                                <div style="display: flex; gap: 5px;">
                                                    <?php if($appt['status'] == 'Scheduled'): ?>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                            <input type="hidden" name="status" value="Checked-In">
                                                            <button class="btn-check btn-green"><i class="fas fa-check"></i> In</button>
                                                        </form>
                                                        <button class="btn-check btn-orange" onclick="openReschedule(<?php echo $appt['appointment_id']; ?>)"><i class="fas fa-clock"></i></button>
                                                    <?php elseif($appt['status'] == 'Checked-In'): ?>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                            <input type="hidden" name="status" value="Completed">
                                                            <button class="btn-check btn-blue"><i class="fas fa-sign-out-alt"></i> Out</button>
                                                        </form>
                                                    <?php elseif($appt['status'] == 'Completed'): ?>
                                                        <span style="color: #10b981; font-size: 11px;"><i class="fas fa-check-double"></i> Done</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($appt['status'] != 'Cancelled' && $appt['status'] != 'Completed'): ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                            <input type="hidden" name="status" value="Cancelled">
                                                            <button class="btn-check btn-red" title="Cancel"><i class="fas fa-times"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; else: ?>
                                        <tr><td colspan="5" style="text-align:center; padding: 20px;">No appointments for today.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                             </table>
                         </div>
                    </div>

                    <!-- Calendar Side Widget -->
                    <div class="calendar-widget" style="margin-top: 0; align-self: start;">
                        <h4 style="color: #fff; margin-bottom: 15px;"><i class="fas fa-calendar-alt"></i> Complete Schedule</h4>
                        <div style="font-size: 12px; color: #cbd5e1; display: flex; flex-direction: column; gap: 10px;">
                            <?php 
                            if ($calendar_result->num_rows > 0): 
                                $calendar_result->data_seek(0);
                                while($cal = $calendar_result->fetch_assoc()): 
                            ?>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-soft); padding-bottom: 5px;">
                                    <span><?php echo date('M d, h:i A', strtotime($cal['appointment_date'])); ?> - <?php echo htmlspecialchars($cal['patient_name']); ?></span>
                                    <span style="color: #3b82f6;"><?php echo htmlspecialchars($cal['department']); ?></span>
                                </div>
                            <?php endwhile; else: ?>
                                <p>No upcoming appointments.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </main>
    </div>

    <!-- Registration Modal -->
    <div id="regModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; margin-bottom: 25px;">
                <h3 style="color: #fff;">New Patient Registration</h3>
                <i class="fas fa-times" style="cursor: pointer; color: #64748b;" onclick="closeModal('regModal')"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="register_patient">
                <div class="form-group-staff"><label>Full Name</label><input type="text" name="p_name" required></div>
                <div class="form-group-staff"><label>Phone Number</label><input type="text" name="p_phone" required></div>
                <div class="form-group-staff"><label>Gender</label><select name="p_gender"><option>Male</option><option>Female</option></select></div>
                <div class="form-group-staff"><label>Age</label><input type="number" name="p_age"></div>
                <div class="form-group-staff"><label>Initial Dept</label><select name="p_dept"><option>General Medicine</option><option>ENT</option><option>Dental</option></select></div>
                <button type="submit" style="width: 100%; padding: 12px; background: #3b82f6; border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer;">Register Patient</button>
            </form>
        </div>
    </div>

    <!-- Book Appointment Modal -->
    <div id="bookModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; margin-bottom: 25px;">
                <h3 style="color: #fff;">Book Appointment</h3>
                <i class="fas fa-times" style="cursor: pointer; color: #64748b;" onclick="closeModal('bookModal')"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="book_appointment">
                <div class="form-group-staff">
                    <label>Select Patient</label>
                    <select name="patient_id" required style="background: #1e293b; color: white;">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach($patient_list as $p): ?>
                            <option value="<?php echo $p['user_id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['patient_code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- <div class="form-group-staff"><label>Patient Name</label><input type="text" name="b_name" required placeholder="Search or Type Name"></div> -->
                <div class="form-group-staff"><label>Department</label><select name="b_dept" style="background: #1e293b; color: white;"><option>General Medicine</option><option>ENT</option><option>Dental</option><option>Orthopedics</option><option>Pediatrics</option></select></div>
                <div class="form-group-staff"><label>Date</label><input type="date" name="b_date" required min="<?php echo date('Y-m-d'); ?>"></div>
                <div class="form-group-staff"><label>Time</label><input type="time" name="b_time" required></div>
                <button type="submit" style="width: 100%; padding: 12px; background: #10b981; border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer;">Confirm Booking</button>
            </form>
        </div>
    </div>

    <!-- Generate Bill Modal -->
    <div id="billModal" class="modal-overlay">
        <div class="modal-box">
             <div style="display: flex; justify-content: space-between; margin-bottom: 25px;">
                <h3 style="color: #fff;">Generate Bill / Invoice</h3>
                <i class="fas fa-times" style="cursor: pointer; color: #64748b;" onclick="closeModal('billModal')"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="generate_bill">
                <div class="form-group-staff">
                    <label>Patient</label>
                    <select name="patient_id" required style="background: #1e293b; color: white;">
                        <option value="">-- Select Patient --</option>
                        <?php foreach($patient_list as $p): ?>
                            <option value="<?php echo $p['user_id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-staff">
                     <label>Service / Bill Type</label>
                     <select name="bill_type" required style="background: #1e293b; color: white;">
                         <option value="Consultation Fee">Consultation Fee</option>
                         <option value="Lab Test">Lab Test</option>
                         <option value="Pharmacy">Pharmacy / Medicine</option>
                         <option value="Procedure">Surgical Procedure</option>
                         <option value="Other">Other</option>
                     </select>
                </div>
                <div class="form-group-staff">
                    <label>Amount ($)</label>
                    <input type="number" name="amount" min="0" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group-staff">
                    <label>Payment Mode</label>
                    <select name="payment_mode" style="background: #1e293b; color: white;">
                        <option value="Cash">Cash</option>
                        <option value="Card">Credit/Debit Card</option>
                        <option value="Insurance">Insurance</option>
                        <option value="UPI">UPI / Digital</option>
                    </select>
                </div>
                <div class="form-group-staff">
                    <label>Payment Status</label>
                    <select name="payment_status" style="background: #1e293b; color: white;">
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                    </select>
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #3b82f6; border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer;">Generate Bill</button>
            </form>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="color: white; margin-bottom: 20px;">Reschedule Appointment</h3>
            <form method="POST">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" id="resch_appt_id" name="appt_id">
                <div class="form-group-staff"><label>New Date</label><input type="date" name="new_date" required></div>
                <div class="form-group-staff"><label>New Time</label><input type="time" name="new_time" required></div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeModal('rescheduleModal')" style="flex:1; padding: 10px; background: #333; color: white; border: none; border-radius: 8px;">Cancel</button>
                    <button type="submit" style="flex:1; padding: 10px; background: #f59e0b; color: white; border: none; border-radius: 8px;">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function openReschedule(id) {
            document.getElementById('resch_appt_id').value = id;
            openModal('rescheduleModal');
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    <?php 
    // Set staff_type for the modal
    $staff_type = 'receptionist';
    include 'includes/report_upload_modal.php'; 
    ?>
</body>
</html>
