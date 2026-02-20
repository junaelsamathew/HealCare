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

// Fetch Doctors for Dropdown
$doc_query = "SELECT d.user_id, r.name, d.department, d.consultation_fee 
              FROM doctors d 
              JOIN users u ON d.user_id = u.user_id 
              JOIN registrations r ON u.registration_id = r.registration_id 
              ORDER BY d.department, r.name";
$doc_res = $conn->query($doc_query);
$doctor_list = [];
if ($doc_res) {
    while ($doc = $doc_res->fetch_assoc()) {
        $doctor_list[] = $doc;
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
        $p_email = mysqli_real_escape_string($conn, $_POST['p_email']);
        // If email is empty, generate a dummy one
        if(empty($p_email)) {
            $p_email = strtolower(str_replace(' ', '', $p_name)) . rand(100,999) . '@healcare.local';
        }

        $p_dept = isset($_POST['p_dept']) ? mysqli_real_escape_string($conn, $_POST['p_dept']) : ''; // Initial Dept or Preferred Dept
        $p_age = (int)$_POST['p_age'];
        $p_gender = mysqli_real_escape_string($conn, $_POST['p_gender']);
        $p_doctor_id = (int)$_POST['p_doctor']; // Selected Doctor ID

        // Generate Patient ID
        $year = date("Y");
        $rand = rand(1000, 9999);
        $p_code = "HC-P-{$year}-{$rand}";
        
        $password = password_hash($p_phone, PASSWORD_DEFAULT);
        
        $conn->begin_transaction();
        try {
            // 1. Insert into Registrations (Required for tracking name/role properly)
            // Note: Registrations table has 'name', 'email', 'phone', 'password', 'user_type', 'status'
            $stmt_reg = $conn->prepare("INSERT INTO registrations (name, email, phone, password, user_type, status, registered_date) VALUES (?, ?, ?, ?, 'patient', 'Approved', CURDATE())");
            $stmt_reg->bind_param("ssss", $p_name, $p_email, $p_phone, $password);
            $stmt_reg->execute();
            $new_reg_id = $conn->insert_id;

            // 2. Insert into Users
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, status, registration_id) VALUES (?, ?, 'patient', 'Active', ?)");
            $stmt_user->bind_param("ssi", $p_code, $password, $new_reg_id);
            $stmt_user->execute();
            $new_user_id = $conn->insert_id;
            
            // 3. Insert into Patient Profiles
            $dob = date('Y-m-d', strtotime("-{$p_age} years"));
            $stmt_prof = $conn->prepare("INSERT INTO patient_profiles (user_id, patient_code, name, phone, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_prof->bind_param("isssss", $new_user_id, $p_code, $p_name, $p_phone, $p_gender, $dob);
            $stmt_prof->execute();
            
            // 4. Create Appointment if Doctor Selected
            $appt_msg = "";
            if ($p_doctor_id > 0) {
                $appt_date = date('Y-m-d H:i:s'); // Now
                
                // Calculate Queue Number for this Doctor Today
                $q_res = $conn->query("SELECT MAX(queue_number) as max_q FROM appointments WHERE doctor_id = $p_doctor_id AND DATE(appointment_date) = CURDATE()");
                $max_q = $q_res->fetch_assoc()['max_q'] ?? 0;
                $token_no = $max_q + 1;

                // Find Department of Doctor if not set correctly in p_dept (though usually p_dept matches)
                // We use the dropdown department if available, else fetch from DB
                // For safety, let's just use the posted dept or fetch it
                $dept_chk = $conn->query("SELECT department FROM doctors WHERE user_id = $p_doctor_id");
                $doc_dept = ($dept_chk && $dept_chk->num_rows > 0) ? $dept_chk->fetch_assoc()['department'] : $p_dept;

                $stmt_appt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, department, appointment_date, status, queue_number) VALUES (?, ?, ?, ?, 'Waiting', ?)");
                $stmt_appt->bind_param("iisss", $new_user_id, $p_doctor_id, $doc_dept, $appt_date, $token_no);
                $stmt_appt->execute();
                
                $appt_msg = "<br>Appointment Booked with Dr. (Token: #$token_no)";
            }
            
            $msg = "Patient Registered! ID: <strong>$p_code</strong> $appt_msg";
            $msg_type = "success";

            // 5. Add Insurance Policy (Optional)
            if (!empty($_POST['ins_provider']) && !empty($_POST['ins_policy_no'])) {
                $ins_provider = $_POST['ins_provider'];
                $ins_policy = $_POST['ins_policy_no'];
                $ins_limit = (float)$_POST['ins_limit'];
                $ins_percent = (int)$_POST['ins_percent'];
                $ins_valid = $_POST['ins_valid_until']; // valid_until
                
                // Set valid_from to today
                $ins_from = date('Y-m-d');
                
                $stmt_ins = $conn->prepare("INSERT INTO insurance_policies (patient_id, provider_name, policy_number, coverage_limit, coverage_percentage, valid_from, valid_until, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')");
                $stmt_ins->bind_param("issdiss", $new_user_id, $ins_provider, $ins_policy, $ins_limit, $ins_percent, $ins_from, $ins_valid);
                $stmt_ins->execute();
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error: " . $e->getMessage();
            $msg_type = "error";
        }
    }

    // 1.5 Add Insurance Policy (Standalone)
    if (isset($_POST['action']) && $_POST['action'] === 'add_policy') {
        $p_id = (int)$_POST['patient_id'];
        $ins_provider = $_POST['ins_provider'];
        $ins_policy = $_POST['ins_policy_no'];
        $ins_limit = (float)$_POST['ins_limit'];
        $ins_percent = (int)$_POST['ins_percent'];
        $ins_from = $_POST['ins_valid_from'];
        $ins_valid = $_POST['ins_valid_until'];
        
        $stmt = $conn->prepare("INSERT INTO insurance_policies (patient_id, provider_name, policy_number, coverage_limit, coverage_percentage, valid_from, valid_until, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->bind_param("issdiss", $p_id, $ins_provider, $ins_policy, $ins_limit, $ins_percent, $ins_from, $ins_valid);
        
        if ($stmt->execute()) {
            $msg = "Insurance Policy Added Successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error adding policy: " . $conn->error;
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
        
        /* Brand Animation */
        .brand-letter {
            display: inline-block;
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .brand-letter.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
        <a href="index.php" class="logo-main" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img src="images/healcare_logo.jpg" alt="HealCare" style="height: 50px;">
            <span class="animated-brand" style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">HEALCARE HOSPITAL</span>
        </a>
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WHATSAPP</span>
                    <a href="https://wa.me/919539045609" target="_blank" style="font-size: 13px; color: #25d366; font-weight: 600; text-decoration: none;"><i class="fab fa-whatsapp"></i> +91 953 904 5609</a>
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
            <a href="?section=insurance" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'insurance') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Insurance</a>
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
                    <?php 
                    include_once 'includes/greeting_logic.php';
                    ?>
                    <!-- Personalized Greeting Banner -->
                    <div style="background: linear-gradient(135deg, #0f172a, #1e293b); padding: 30px; border-radius: 16px; border: 1px solid var(--border-soft); margin-bottom: 30px; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(59, 130, 246, 0.05); border-radius: 50%; filter: blur(40px);"></div>
                        <div style="position: relative; z-index: 1;">
                            <h2 style="color: #fff; font-size: 24px; margin-bottom: 5px;"><?php echo $greeting; ?>, <?php echo htmlspecialchars($staff_name); ?></h2>
                            <p style="color: #64748b; font-size: 14px;">The hospital is busy today. Assist patients with registrations and coordinate with the clinical staff.</p>
                        </div>
                    </div>

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

            <?php elseif ($_GET['section'] == 'insurance'): ?>
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="color:#fff; font-size: 28px;">Insurance Management</h1>
                        <p style="color:#64748b; font-size:14px;">Manage patient insurance policies and coverage.</p>
                    </div>
                    <button class="btn-action-main" onclick="openModal('insuranceModal')" style="background: #3b82f6; border-color: #3b82f6;"><i class="fas fa-plus"></i> Add New Policy</button>
                </div>

                <div class="stat-card-new" style="background: rgba(30, 41, 59, 0.4);">
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Provider</th>
                                <th>Policy No</th>
                                <th>Coverage</th>
                                <th>Valid Until</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $ins_sql = "
                                SELECT ip.*, r.name as patient_name, pp.patient_code 
                                FROM insurance_policies ip
                                JOIN users u ON ip.patient_id = u.user_id
                                JOIN registrations r ON u.registration_id = r.registration_id
                                LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
                                ORDER BY ip.created_at DESC LIMIT 50
                            ";
                            $ins_res = $conn->query($ins_sql);
                            if($ins_res && $ins_res->num_rows > 0):
                                while($pol = $ins_res->fetch_assoc()):
                                    $is_active = ($pol['valid_until'] >= date('Y-m-d') && $pol['status'] == 'Active');
                                    $status_color = $is_active ? '#10b981' : '#ef4444';
                                    $status_text = $pol['status'];
                                    if($pol['status'] == 'Active' && $pol['valid_until'] < date('Y-m-d')) $status_text = 'Expired';
                            ?>
                            <tr>
                                <td>
                                    <strong style="color:white;"><?php echo htmlspecialchars($pol['patient_name']); ?></strong><br>
                                    <small style="color:#64748b;"><?php echo $pol['patient_code']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($pol['provider_name']); ?></td>
                                <td><?php echo htmlspecialchars($pol['policy_number']); ?></td>
                                <td>
                                    <?php echo $pol['coverage_percentage']; ?>% Cover<br>
                                    <small>Limit: ₹<?php echo number_format($pol['coverage_limit']); ?></small>
                                </td>
                                <td><?php echo date('d M Y', strtotime($pol['valid_until'])); ?></td>
                                <td><span style="color: <?php echo $status_color; ?>; font-weight: 600;"><?php echo $status_text; ?></span></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px;">No insurance policies found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                <div class="form-group-staff">
                    <label>Full Name</label>
                    <input type="text" name="p_name" id="p_name" required>
                    <small id="nameError" style="color: red; display: none;">Please enter a valid name (letters and spaces only).</small>
                </div>
                <div class="form-group-staff">
                    <label>Phone Number</label>
                    <input type="text" name="p_phone" id="p_phone" required>
                    <small id="phoneError" style="color: red; display: none;">Please enter a valid 10-digit phone number.</small>
                </div>
                <div class="form-group-staff"><label>Gender</label><select name="p_gender"><option>Male</option><option>Female</option></select></div>
                <div class="form-group-staff">
                    <label>Age</label>
                    <input type="number" name="p_age" id="p_age" min="0" max="150">
                    <small id="ageError" style="color: red; display: none;">Please enter a valid age.</small>
                </div>
                <div class="form-group-staff">
                    <label>Email (Optional)</label>
                    <input type="email" name="p_email" placeholder="patient@example.com">
                </div>
                <!-- Initial Dept removed in favor of Doctor Selection, or kept as fallback -->
                <!-- <div class="form-group-staff"><label>Initial Dept</label><select name="p_dept"><option>General Medicine</option><option>ENT</option><option>Dental</option></select></div> -->
                
                <div class="form-group-staff">
                    <label>Assign Doctor</label>
                    <select name="p_doctor" style="background: #1e293b; color: white;" required>
                        <option value="">-- Select Doctor --</option>
                        <?php foreach($doctor_list as $doc): ?>
                            <option value="<?php echo $doc['user_id']; ?>">
                                <?php echo htmlspecialchars($doc['name']); ?> - <?php echo htmlspecialchars($doc['department']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>
                
                <h4 style="color: #64748b; font-size: 14px; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid var(--border-soft); padding-bottom: 5px;">Insurance Details (Optional)</h4>
                <div class="form-group-staff"><label>Insurance Provider</label><input type="text" name="ins_provider" placeholder="e.g. Star Health"></div>
                <div class="form-group-staff"><label>Policy Number</label><input type="text" name="ins_policy_no" placeholder="e.g. POL-123456789"></div>
                <div style="display:flex; gap:15px;">
                    <div class="form-group-staff" style="flex:1;"><label>Coverage Limit (₹)</label><input type="number" name="ins_limit" placeholder="500000"></div>
                    <div class="form-group-staff" style="flex:1;"><label>Coverage (%)</label><input type="number" name="ins_percent" placeholder="80" max="100"></div>
                </div>
                <div class="form-group-staff"><label>Valid Until</label><input type="date" name="ins_valid_until" min="<?php echo date('Y-m-d'); ?>"></div>

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
                    <select name="patient_id" id="b_patient" required style="background: #1e293b; color: white;">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach($patient_list as $p): ?>
                            <option value="<?php echo $p['user_id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['patient_code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small id="b_patientError" style="color: red; display: none;">Please select a patient.</small>
                </div>
                <!-- <div class="form-group-staff"><label>Patient Name</label><input type="text" name="b_name" required placeholder="Search or Type Name"></div> -->
                <div class="form-group-staff"><label>Department</label><select name="b_dept" style="background: #1e293b; color: white;"><option>General Medicine</option><option>ENT</option><option>Dental</option><option>Orthopedics</option><option>Pediatrics</option></select></div>
                <div class="form-group-staff">
                    <label>Date</label>
                    <input type="date" name="b_date" id="b_date" required min="<?php echo date('Y-m-d'); ?>">
                    <small id="b_dateError" style="color: red; display: none;">Please select a valid future date.</small>
                </div>
                <div class="form-group-staff">
                    <label>Time</label>
                    <input type="time" name="b_time" id="b_time" required>
                    <small id="b_timeError" style="color: red; display: none;">Please select a time.</small>
                </div>
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

    <!-- Add Insurance Policy Modal -->
    <div id="insuranceModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; margin-bottom: 25px;">
                <h3 style="color: #fff;">Add Insurance Policy</h3>
                <i class="fas fa-times" style="cursor: pointer; color: #64748b;" onclick="closeModal('insuranceModal')"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_policy">
                <div class="form-group-staff">
                    <label>Select Patient</label>
                    <select name="patient_id" required style="background: #1e293b; color: white;">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach($patient_list as $p): ?>
                            <option value="<?php echo $p['user_id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-staff"><label>Provider Name</label><input type="text" name="ins_provider" required placeholder="e.g. Star Health"></div>
                <div class="form-group-staff"><label>Policy Number</label><input type="text" name="ins_policy_no" required></div>
                <div style="display:flex; gap:15px;">
                    <div class="form-group-staff" style="flex:1;"><label>Limit (₹)</label><input type="number" name="ins_limit" required></div>
                    <div class="form-group-staff" style="flex:1;"><label>Cover (%)</label><input type="number" name="ins_percent" required placeholder="80"></div>
                </div>
                <div style="display:flex; gap:15px;">
                    <div class="form-group-staff" style="flex:1;"><label>Valid From</label><input type="date" name="ins_valid_from" required value="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="form-group-staff" style="flex:1;"><label>Valid Until</label><input type="date" name="ins_valid_until" required></div>
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #3b82f6; border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer;">Add Policy</button>
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
    <script>
        // Separate event listener for Book Appointment Form
        document.querySelector('form[action="?"]').parentNode.parentNode.addEventListener('submit', function(e) {
             // Since there are multiple forms, we need to be more specific or delegate.
             // However, `document.querySelector('form[action="?"]')` only selects the first one (likely Registration).
             // Let's use a more robust way by adding IDs to forms or checking the submitter.
             // But to keep consistent with the previous patch style, let's attach listeners to all forms and check the hidden action.
        });

        // Better Approach: Attach listener to ALL forms and switch based on action value
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(event) {
                const actionInput = form.querySelector('input[name="action"]');
                if (!actionInput) return;

                let isValid = true;
                
                // 1. REGISTRATION FORM
                if (actionInput.value === 'register_patient') {
                     const nameInput = document.getElementById('p_name');
                     const nameError = document.getElementById('nameError');
                     const nameRegex = /^[a-zA-Z\s]+$/;
                     if (!nameRegex.test(nameInput.value.trim())) {
                         nameError.style.display = 'block';
                         isValid = false;
                     } else { nameError.style.display = 'none'; }

                     const phoneInput = document.getElementById('p_phone');
                     const phoneError = document.getElementById('phoneError');
                     const phoneRegex = /^\d{10}$/;
                     if (!phoneRegex.test(phoneInput.value.trim())) {
                         phoneError.style.display = 'block';
                         isValid = false;
                     } else { phoneError.style.display = 'none'; }
                     
                     const ageInput = document.getElementById('p_age');
                     const ageError = document.getElementById('ageError');
                     if (ageInput.value < 0 || ageInput.value > 150) {
                         ageError.style.display = 'block';
                         isValid = false;
                     } else { ageError.style.display = 'none'; }
                }

                // 2. BOOKING FORM
                if (actionInput.value === 'book_appointment') {
                    const patientInput = document.getElementById('b_patient');
                    const patientError = document.getElementById('b_patientError');
                    if (patientInput.value === "") {
                        patientError.style.display = 'block';
                        isValid = false;
                    } else { patientError.style.display = 'none'; }

                    const dateInput = document.getElementById('b_date');
                    const dateError = document.getElementById('b_dateError');
                    const selectedDate = new Date(dateInput.value);
                    const today = new Date();
                    today.setHours(0,0,0,0); // reset time part
                    
                    if (!dateInput.value || selectedDate < today) {
                        dateError.style.display = 'block';
                        isValid = false;
                    } else { dateError.style.display = 'none'; }

                    const timeInput = document.getElementById('b_time');
                    const timeError = document.getElementById('b_timeError');
                    if (!timeInput.value) {
                         timeError.style.display = 'block';
                         isValid = false;
                    } else { timeError.style.display = 'none'; }
                }

                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    </script>
    <?php 
    // Set staff_type for the modal
    $staff_type = 'receptionist';
    include 'includes/report_upload_modal.php'; 
    ?>
    <script>
        // Brand Animation
        document.addEventListener('DOMContentLoaded', function() {
            function initBrandAnimation() {
                const brandElement = document.querySelector('.animated-brand');
                if (!brandElement) return;

                const text = brandElement.textContent.trim();
                brandElement.textContent = ''; // Clear text

                // Create spans
                const letters = [];
                for (let char of text) {
                    const span = document.createElement('span');
                    span.textContent = char;
                    span.classList.add('brand-letter');
                    if (char === ' ') {
                        span.style.width = '0.3em'; // Space
                        span.style.display = 'inline-block';
                    }
                    brandElement.appendChild(span);
                    letters.push(span);
                }

                function animate() {
                    // Show sequence
                    letters.forEach((letter, index) => {
                        setTimeout(() => {
                            letter.classList.add('visible');
                        }, index * 100); // 100ms staggering
                    });

                    // Hide sequence (after full text is shown + delay)
                    const totalTime = (letters.length * 100) + 2000; // 2s pause

                    setTimeout(() => {
                        letters.forEach((letter, index) => {
                            setTimeout(() => {
                                letter.classList.remove('visible');
                            }, index * 50); // Faster fade out
                        });
                    }, totalTime);

                    const cycleTime = totalTime + (letters.length * 50) + 500; // time to hide + pause

                    setTimeout(animate, cycleTime);
                }

                // Start animation
                animate();
            }
            
            initBrandAnimation();
        });
    </script>
</body>
</html>
