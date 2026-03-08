<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// --- Schema verified ---

// Fetch actual doctor professional info with profile photo
$stmt = $conn->prepare("SELECT d.*, r.profile_photo FROM doctors d JOIN users u ON d.user_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id WHERE d.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $doctor = $res->fetch_assoc();
    $specialization = $doctor['specialization'];
    $department = $doctor['department'];
    $designation = $doctor['designation'];
    $profile_photo = $doctor['profile_photo'];
} else {
    // Fallback for demo/manual users without doctor profiles
    $specialization = "General Healthcare / Consultation";
    $department = "General Medicine";
    $designation = "Professional Consultant";
    $profile_photo = null;
}

$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
if (stripos($doctor_name, 'Dr.') === false && stripos($doctor_name, 'Doctor') === false) {
    $doctor_name = "Dr. " . $doctor_name;
}

// Determine Avatar
$doctor_avatar = 'images/doctor_placeholder.png'; // Default
if (!empty($profile_photo)) {
    if (file_exists($profile_photo)) {
        $doctor_avatar = $profile_photo;
    } elseif (file_exists('images/' . $profile_photo)) {
        $doctor_avatar = 'images/' . $profile_photo;
    }
} elseif (stripos($doctor_name, 'Maria Vineeth') !== false) {
    $doctor_avatar = 'images/doctor_maria.png';
} elseif (stripos($doctor_name, 'June Mary') !== false) {
    $doctor_avatar = 'images/dr_june_mary_antony.png';
}

// Handle Status Updates (Accept Appointment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appt_id = intval($_POST['appt_id']);
    $new_status = $_POST['new_status'];
    // Verify ownership
    $check_own = $conn->query("SELECT appointment_id FROM appointments WHERE appointment_id = $appt_id AND doctor_id = $user_id");
    if ($check_own->num_rows > 0) {
        $meeting_link = isset($_POST['meeting_link']) ? trim($_POST['meeting_link']) : null;
        
        $sql = "UPDATE appointments SET status = ?";
        if ($meeting_link) {
            $sql .= ", meeting_link = ?";
        }
        $sql .= " WHERE appointment_id = ?";
        
        $stmt_upd = $conn->prepare($sql);
        if ($meeting_link) {
            $stmt_upd->bind_param("ssi", $new_status, $meeting_link, $appt_id);
        } else {
            $stmt_upd->bind_param("si", $new_status, $appt_id);
        }
        
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

    // Fetch Lab Results for this specific appointment
    $current_lab_results = [];
    if (isset($_GET['appt_id'])) {
        $a_id = intval($_GET['appt_id']);
        // Fetch Appointment Reason
        $stmt_r = $conn->prepare("SELECT reason FROM appointments WHERE appointment_id = ?");
        $stmt_r->bind_param("i", $a_id);
        $stmt_r->execute();
        $res_r = $stmt_r->get_result();
        if($row_r = $res_r->fetch_assoc()) {
            $active_patient['current_reason'] = $row_r['reason'];
        }

        // Fetch official Completed Lab Results
        $stmt_lab_curr = $conn->prepare("SELECT * FROM lab_tests WHERE appointment_id = ? AND status = 'Completed'");
        $stmt_lab_curr->bind_param("i", $a_id);
        $stmt_lab_curr->execute();
        $lab_curr_res = $stmt_lab_curr->get_result();
        while($l_row = $lab_curr_res->fetch_assoc()) {
            $current_lab_results[] = $l_row;
        }

        // Fetch Matching Manual Reports for this patient (Fuzzy Match by ID or Name)
        $search_name = mysqli_real_escape_string($conn, $active_patient['name']);
        $m_res = $conn->query("SELECT * FROM manual_reports WHERE (patient_id = $pid OR report_title LIKE '%$search_name%') AND (report_date = CURDATE() OR created_at >= NOW() - INTERVAL 12 HOUR)");
        while($m_row = $m_res->fetch_assoc()) {
            $current_lab_results[] = [
                'test_name' => $m_row['report_title'] . ' (Manual Upload)',
                'result' => 'Document found in repository matching patient.',
                'report_path' => $m_row['file_path']
            ];
        }
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

// --- Fetch Next Patient Details ---
$next_patient = null;
$today = date('Y-m-d');
$stmt_next = $conn->prepare("
    SELECT a.*, r.name as patient_name, r.phone, r.registered_date, pp.patient_code, pp.gender, pp.date_of_birth,
           (SELECT weight FROM patient_vitals WHERE patient_id = a.patient_id ORDER BY recorded_at DESC LIMIT 1) as weight,
           (SELECT height FROM patient_vitals WHERE patient_id = a.patient_id ORDER BY recorded_at DESC LIMIT 1) as height,
           (SELECT appointment_date FROM appointments WHERE patient_id = a.patient_id AND status = 'Completed' AND appointment_date < ? ORDER BY appointment_date DESC LIMIT 1) as last_visit
    FROM appointments a 
    JOIN users u ON a.patient_id = u.user_id 
    JOIN registrations r ON u.registration_id = r.registration_id 
    LEFT JOIN patient_profiles pp ON a.patient_id = pp.user_id 
    WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status IN ('Scheduled', 'Confirmed', 'Approved', 'Checked-In', 'Requested', 'Pending Lab', 'Lab Completed')
    ORDER BY 
        CASE 
            WHEN a.urgency = 'Emergency' THEN 1 
            WHEN a.urgency = 'Urgent' THEN 2 
            WHEN a.status = 'Lab Completed' THEN 3
            ELSE 4
        END, a.appointment_time ASC LIMIT 1
");
$stmt_next->bind_param("sis", $today, $user_id, $today);
$stmt_next->execute();
$next_patient = $stmt_next->get_result()->fetch_assoc();

// --- Fetch Weekly Consultation Stats (Day by Day) ---
$daily_stats = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    
    $stmt_count = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('Cancelled', 'Rejected', 'Requested')");
    $stmt_count->bind_param("is", $user_id, $date);
    $stmt_count->execute();
    $day_count = $stmt_count->get_result()->fetch_assoc()['count'];
    
    $daily_stats[] = [
        'day' => $day_name,
        'count' => $day_count
    ];
}
$labels_json = json_encode(array_column($daily_stats, 'day'));
$counts_json = json_encode(array_column($daily_stats, 'count'));

// --- Time-based Greeting Logic ---
include_once 'includes/greeting_logic.php';

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
            padding: 25px 40px !important;
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
        .badge-status-Waiting { background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 4px 10px; border-radius: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Table Styles for Inpatient Rounds */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            text-align: left;
            padding: 15px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        td {
            padding: 15px;
            color: #f1f5f9;
            font-size: 13px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }
        tr:last-child td {
            border-bottom: none;
        }
        /* Sidebar Profile Style */
        .sidebar-profile {
            padding: 30px 20px;
            text-align: center;
            background: linear-gradient(to bottom, #2563eb, #1e4ed8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        .profile-img-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 15px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .profile-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .doctor-sidebar-name {
            color: #fff;
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .doctor-sidebar-spec {
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Header Icons Style */
        .header-action-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .header-action-icon:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateY(-2px);
            border-color: rgba(59, 130, 246, 0.3);
        }
        .notification-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid #0f172a;
        }

        .user-greeting-box {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-right: 15px;
            line-height: 1.2;
        }

        /* Profile Header Summary */
        .header-profile-box {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-left: 20px;
            border-left: none;
            margin-left: 10px;
        }
        .profile-info {
            text-align: right;
            line-height: 1.3;
        }
        .profile-info .doc-name {
            display: block;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }
        .header-avatar-container {
            position: relative;
            width: 42px;
            height: 42px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .header-avatar-container:hover {
            transform: scale(1.05);
        }
        .header-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(59, 130, 246, 0.3);
            background: #fff;
        }
        .online-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 11px;
            height: 11px;
            background: #10b981;
            border-radius: 50%;
            border: 2px solid #0f172a;
        }

        /* Greeting Banner / Appointment Module */
        .greeting-banner {
            background: linear-gradient(to right, #1d4ed8, #3b82f6);
            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
        }
        .greeting-banner h2 { font-size: 20px; font-weight: 700; margin-bottom: 5px; }
        .greeting-banner p { color: rgba(255, 255, 255, 0.9); font-size: 13px; margin-bottom: 15px; max-width: 600px; line-height: 1.5; }

        .stat-card .stat-value { font-size: 20px !important; margin-bottom: 5px !important; }
        .stat-card .stat-label { font-size: 11px !important; color: #94a3b8 !important; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Modal Styles */
        .modal {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); justify-content: center; align-items: center;
        }
        .modal.active { display: flex; }
        .modal-card { 
            background: #0f172a; width: 90%; max-width: 550px; border-radius: 24px; 
            border: 1px solid rgba(255,255,255,0.1); overflow: hidden; animation: modalPop 0.3s ease;
        }
        @keyframes modalPop { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-card-head { padding: 25px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .modal-card-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #94a3b8; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .form-control { 
            width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
            padding: 12px 15px; border-radius: 12px; color: #fff; font-size: 14px; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: #3b82f6; background: rgba(255,255,255,0.08); }

        /* Dropdown Styles */
        .header-item-relative {
            position: relative;
        }
        .header-dropdown {
            position: absolute;
            top: 50px;
            right: 0;
            width: 320px;
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            display: none;
            z-index: 1100;
            overflow: hidden;
            animation: dropdownFade 0.3s ease;
        }
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header-dropdown.show {
            display: block;
        }
        .dropdown-header {
            padding: 15px 20px;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dropdown-header h4 { margin: 0; font-size: 14px; color: #fff; }
        .dropdown-body {
            max-height: 400px;
            overflow-y: auto;
        }
        .dropdown-item {
            padding: 15px 20px;
            display: flex;
            gap: 12px;
            text-decoration: none;
            transition: 0.2s;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .dropdown-item:hover {
            background: rgba(255,255,255,0.05);
        }
        .item-icon {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .item-content { flex: 1; }
        .item-title { display: block; color: #fff; font-size: 13px; font-weight: 600; margin-bottom: 2px; }
        .item-desc { display: block; color: #94a3b8; font-size: 11px; }
    </style>
</head>
<body>
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

    <header class="secondary-header" style="display: flex; justify-content: flex-end; padding: 15px 40px; background: #0f172a; border-bottom: 1px solid rgba(255,255,255,0.05); align-items: center;">
        <div style="flex: 1;"></div>
        <div class="user-controls" style="display: flex; align-items: center; gap: 25px;">

            <!-- Action Icons -->
            <div style="display: flex; gap: 12px; align-items: center; padding-right: 25px;">
                <!-- Notifications -->
                <div class="header-item-relative">
                    <a href="javascript:void(0)" class="header-action-icon" title="Notifications" onclick="toggleDropdown('notifDropdown')">
                        <i class="fas fa-bell"></i>
                        <?php if($stats_pending > 0): ?><span class="notification-dot"></span><?php endif; ?>
                    </a>
                    <div id="notifDropdown" class="header-dropdown">
                        <div class="dropdown-header">
                            <h4>Notifications</h4>
                            <span style="font-size: 10px; background: #3b82f6; color: #fff; padding: 2px 6px; border-radius: 10px;"><?php echo $stats_pending; ?> New</span>
                        </div>
                        <div class="dropdown-body">
                            <a href="doctor_appointments.php" class="dropdown-item">
                                <div class="item-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-calendar-check"></i></div>
                                <div class="item-content">
                                    <span class="item-title">Pending Appointments</span>
                                    <span class="item-desc">You have <?php echo $stats_pending; ?> appointments awaiting review.</span>
                                </div>
                            </a>
                            <a href="doctor_lab_orders.php" class="dropdown-item">
                                <div class="item-icon" style="background: rgba(168, 85, 247, 0.1); color: #a855f7;"><i class="fas fa-flask"></i></div>
                                <div class="item-content">
                                    <span class="item-title">Lab Reports</span>
                                    <span class="item-desc">Check latest patient test results.</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Help -->
                <div class="header-item-relative">
                    <a href="javascript:void(0)" class="header-action-icon" title="Help Centre" onclick="toggleDropdown('helpDropdown')">
                        <i class="fas fa-question-circle"></i>
                    </a>
                    <div id="helpDropdown" class="header-dropdown">
                        <div class="dropdown-header">
                            <h4>Help & Support</h4>
                        </div>
                        <div class="dropdown-body">
                            <a href="#" class="dropdown-item">
                                <div class="item-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-book-medical"></i></div>
                                <div class="item-content">
                                    <span class="item-title">Medical Guidelines</span>
                                    <span class="item-desc">Hospital protocol and clinical guidelines.</span>
                                </div>
                            </a>
                            <a href="#" class="dropdown-item">
                                <div class="item-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-headset"></i></div>
                                <div class="item-content">
                                    <span class="item-title">IT Support</span>
                                    <span class="item-desc">Report technical issues or dashboard bugs.</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Profile Summary -->
            <div class="header-profile-box header-item-relative">
                <div class="profile-info">
                    <span class="doc-name"><?php echo $doctor_name; ?></span>
                </div>
                <div class="header-avatar-container" onclick="toggleDropdown('profileDropdown')">
                    <img src="<?php echo $doctor_avatar; ?>" alt="Profile" class="header-avatar">
                    <span class="online-dot"></span>
                </div>
                <!-- Profile/Logout Dropdown -->
                <div id="profileDropdown" class="header-dropdown" style="width: 180px; top: 55px;">
                    <div class="dropdown-body">
                        <a href="logout.php" class="dropdown-item">
                            <div class="item-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-sign-out-alt"></i></div>
                            <div class="item-content">
                                <span class="item-title" style="color: #ef4444;">Sign Out</span>
                                <span class="item-desc">Securely exit HealCare</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
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

            <?php if(isset($_GET['msg'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 20px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                    </div>
                    <?php if(isset($_GET['discharged_id'])): ?>
                        <a href="generate_discharge_summary.php?admission_id=<?php echo intval($_GET['discharged_id']); ?>" target="_blank" 
                           style="background: #10b981; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-print"></i> Print Discharge Summary
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.2); margin-bottom: 25px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Personalized Greeting Banner -->
            <div class="greeting-banner">
                <div style="position: relative; z-index: 1;">
                    <h2><?php echo $greeting; ?>, <?php echo $doctor_name; ?></h2>
                    <p>Have a nice day at work! Manage your daily schedule, track patient consultations, and coordinate with other departments efficiently.</p>
                </div>
            </div>



            <!-- Stats Grid - Scoped to Department -->

            <div class="doctor-stats-grid">
                <div class="stat-card">
                    <span class="stat-value"><?php echo str_pad($stats_pending, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="stat-label">Pending</span>
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

            <!-- My Admitted Patients (Inpatient Overview) -->
            <div class="content-section" style="margin-bottom: 30px;">
                <div class="section-head" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3><i class="fas fa-procedures"></i> My Admitted Patients</h3>
                        <p style="font-size: 13px; color: #94a3b8;">Real-time status of your inpatients.</p>
                    </div>
                    <?php
                    // Check Blood Bank Global Alert
                    $low_blood = $conn->query("SELECT blood_group, units_available FROM blood_bank WHERE units_available <= 5");
                    if ($low_blood && $low_blood->num_rows > 0):
                        $alert_msg = "";
                        while($lb = $low_blood->fetch_assoc()) $alert_msg .= $lb['blood_group'] . " (" . $lb['units_available'] . "U) ";
                    ?>
                        <div style="background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 6px 12px; border-radius: 8px; font-size: 12px; border: 1px solid rgba(239, 68, 68, 0.3); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-exclamation-circle"></i> <strong>Critical Blood Low:</strong> <?php echo $alert_msg; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                $inpatients = $conn->query("
                    SELECT a.admission_id, a.admission_date, p.name, p.patient_code, p.blood_group, 
                           w.ward_name, r.room_number,
                           (SELECT recorded_at FROM patient_vitals WHERE patient_id = a.patient_id ORDER BY recorded_at DESC LIMIT 1) as last_vital_time,
                           (SELECT CONCAT(blood_pressure_systolic, '/', blood_pressure_diastolic) FROM patient_vitals WHERE patient_id = a.patient_id ORDER BY recorded_at DESC LIMIT 1) as last_bp
                    FROM admissions a
                    JOIN patient_profiles p ON a.patient_id = p.user_id
                    LEFT JOIN rooms r ON a.room_id = r.room_id
                    LEFT JOIN wards w ON r.ward_id = w.ward_id
                    WHERE a.doctor_id = $user_id AND a.status = 'Admitted'
                    ORDER BY a.admission_date DESC
                ");
                ?>

                <?php if ($inpatients && $inpatients->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left;">
                                    <th style="padding: 12px; color: #94a3b8; font-size: 11px; text-transform: uppercase;">Patient</th>
                                    <th style="padding: 12px; color: #94a3b8; font-size: 11px; text-transform: uppercase;">Location</th>
                                    <th style="padding: 12px; color: #94a3b8; font-size: 11px; text-transform: uppercase;">Admitted Since</th>
                                    <th style="padding: 12px; color: #94a3b8; font-size: 11px; text-transform: uppercase;">Status</th>
                                    <th style="padding: 12px; text-align: right; color: #94a3b8; font-size: 11px; text-transform: uppercase;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($ip = $inpatients->fetch_assoc()): 
                                    $adm_days = floor((time() - strtotime($ip['admission_date'])) / (60 * 60 * 24));
                                    
                                    // Check specific blood availability for this patient
                                    $bg = $ip['blood_group'];
                                    $bg_alert = false;
                                    if(in_array($bg, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])) {
                                        $chk_bg = $conn->query("SELECT units_available FROM blood_bank WHERE blood_group = '$bg' AND units_available <= 3");
                                        if($chk_bg->num_rows > 0) $bg_alert = true;
                                    }
                                ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                    <td style="padding: 15px;">
                                        <div style="font-weight: 600; color: #fff;"><?php echo htmlspecialchars($ip['name']); ?></div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            <?php echo $ip['patient_code']; ?> • <span style="color: <?php echo $bg_alert ? '#ef4444' : '#94a3b8'; ?>; font-weight: <?php echo $bg_alert ? '700' : '400'; ?>;"><?php echo $ip['blood_group']; ?> <?php if($bg_alert) echo '<i class="fas fa-exclamation-triangle" title="Low Blood Stock"></i>'; ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 15px;">
                                        <div style="color: #cbd5e1; font-size: 13px;"><?php echo htmlspecialchars($ip['ward_name']); ?></div>
                                        <div style="font-size: 11px; color: #64748b;">Room <?php echo $ip['room_number']; ?></div>
                                    </td>
                                    <td style="padding: 15px; font-size: 13px; color: #cbd5e1;">
                                        <?php echo date('M d', strtotime($ip['admission_date'])); ?> 
                                        <span style="font-size: 11px; color: #64748b;">(<?php echo $adm_days; ?> days)</span>
                                    </td>
                                    <td style="padding: 15px;">
                                        <?php if($ip['last_vital_time']): 
                                            $mins_ago = round((time() - strtotime($ip['last_vital_time'])) / 60);
                                            $status_color = ($mins_ago < 240) ? '#10b981' : '#f59e0b'; // Green if checked < 4 hours ago
                                            $status_text = ($mins_ago < 240) ? 'Stable' : 'Needs Check';
                                        ?>
                                            <span style="color: <?php echo $status_color; ?>; background: <?php echo $status_color; ?>15; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                                <?php echo $status_text; ?> (BP: <?php echo $ip['last_bp']; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 12px; font-size: 11px;">No Data</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 15px; text-align: right;">
                                        <a href="doctor_inpatient_chart.php?admission_id=<?php echo $ip['admission_id']; ?>" class="btn-consult" style="text-decoration: none; padding: 6px 12px; font-size: 12px;">
                                            <i class="fas fa-notes-medical"></i> View Chart
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 30px; color: #64748b;">
                        <i class="fas fa-bed" style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>No patients currently admitted under your care.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Consultation Statistics (Weekly) -->
            <div class="content-section" style="margin-bottom: 30px; background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.7)) !important; border: 1px solid rgba(59, 130, 246, 0.2) !important;">
                <div class="section-head" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="color: #4fc3f7;"><i class="fas fa-chart-bar"></i> Weekly Consultation Traffic</h3>
                        <p style="font-size: 13px; color: #94a3b8; margin-top: 5px;">Patient volume overview for the last 7 days</p>
                    </div>
                    <div style="text-align: right; background: rgba(16, 185, 129, 0.1); padding: 10px 20px; border-radius: 15px; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <span style="font-size: 28px; color: #10b981; font-weight: 800; display: block; line-height: 1;"><?php echo array_sum(array_column($daily_stats, 'count')); ?></span>
                        <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">7-Day Total</span>
                    </div>
                </div>
                <div style="height: 280px; margin-top: 25px;">
                    <canvas id="weeklyConsultChart"></canvas>
                </div>
            </div>
            


            <!-- Inpatient Rounds Section -->
            <div class="content-section" style="margin-bottom: 30px; border: 1px solid rgba(16, 185, 129, 0.2); background: rgba(16, 185, 129, 0.05);">
                <div class="section-head">
                    <h3 style="color: #10b981;"><i class="fas fa-procedures"></i> Inpatient Rounds</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Room / Ward</th>
                            <th>Admitted Date</th>
                            <th>Status.</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Updated query to include Pending admissions and use LEFT JOIN for rooms/wards
                        $inpatients = $conn->query("SELECT a.admission_id, a.admission_date, a.status, a.request_date, a.ward_type_req, 
                                                    p.name, pp.gender, 
                                                    r.room_number, w.ward_name, w.ward_type 
                                                    FROM admissions a 
                                                    JOIN users u ON a.patient_id = u.user_id 
                                                    JOIN registrations p ON u.registration_id = p.registration_id
                                                    LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
                                                    LEFT JOIN rooms r ON a.room_id = r.room_id 
                                                    LEFT JOIN wards w ON r.ward_id = w.ward_id
                                                    WHERE a.doctor_id = $user_id AND a.status IN ('Admitted', 'Pending')
                                                    ORDER BY a.status ASC, a.request_date DESC");

                        if($inpatients && $inpatients->num_rows > 0):
                            while($ip = $inpatients->fetch_assoc()):
                                $is_pending = ($ip['status'] == 'Pending');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($ip['name']); ?></strong><br>
                                <span style="font-size: 11px; color:#94a3b8;"><?php echo $ip['gender'] ?? 'N/A'; ?></span>
                            </td>
                            <td>
                                <?php if($is_pending): ?>
                                    <span style="color: #f59e0b; font-size: 12px; font-style: italic;">
                                        Requested: <?php echo htmlspecialchars($ip['ward_type_req']); ?>
                                    </span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($ip['room_number'] ?? 'Unassigned'); ?><br>
                                    <span style="font-size: 11px; color:#94a3b8;"><?php echo htmlspecialchars(($ip['ward_name'] ?? 'Ward') . ' (' . ($ip['ward_type'] ?? 'Bed') . ')'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if($is_pending) echo date('d M, h:i A', strtotime($ip['request_date'])); 
                                    else echo date('d M, h:i A', strtotime($ip['admission_date'])); 
                                ?>
                            </td>
                            <td>
                                <?php if($is_pending): ?>
                                    <span class="badge" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b;">Pending Room</span>
                                <?php else: ?>
                                    <span class="badge" style="background:rgba(16, 185, 129, 0.1); color:#10b981;">Admitted</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!$is_pending): ?>
                                    <a href="doctor_inpatient_chart.php?admission_id=<?php echo $ip['admission_id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;">View Chart</a>
                                    <a href="doctor_discharge.php?admission_id=<?php echo $ip['admission_id']; ?>" class="btn btn-warning" style="padding: 6px 12px; font-size: 11px; background:#f59e0b; border:none; color:white;">Discharge</a>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: #94a3b8;">Waiting for Admin</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:15px; color:#94a3b8;">No inpatients currently admitted under your care.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                            $stmt = $conn->prepare("
                                SELECT a.*, 
                                       COALESCE(r.name, a.external_name) as patient_name, 
                                       COALESCE(r.phone, a.external_phone) as phone, 
                                       r.registered_date, pp.patient_code, pp.gender, pp.date_of_birth,
                                       (SELECT weight FROM patient_vitals WHERE patient_id = a.patient_id ORDER BY recorded_at DESC LIMIT 1) as weight,
                                       (SELECT height FROM patient_vitals WHERE patient_id = a.patient_id ORDER BY recorded_at DESC LIMIT 1) as height,
                                       (SELECT appointment_date FROM appointments WHERE patient_id = a.patient_id AND status = 'Completed' AND appointment_date < ? ORDER BY appointment_date DESC LIMIT 1) as last_visit
                                FROM appointments a 
                                LEFT JOIN users u ON a.patient_id = u.user_id 
                                LEFT JOIN registrations r ON u.registration_id = r.registration_id 
                                LEFT JOIN patient_profiles pp ON a.patient_id = pp.user_id 
                                WHERE a.doctor_id = ? AND a.appointment_date = ? 
                                ORDER BY 
                                    CASE 
                                        WHEN a.urgency = 'Emergency' THEN 1 
                                        WHEN a.urgency = 'Urgent' THEN 2 
                                        ELSE 3 
                                    END, 
                                    a.appointment_time ASC
                            ");
                            $stmt->bind_param("sis", $today, $user_id, $today);
                            $stmt->execute();
                            $queue_res = $stmt->get_result();

                            if ($queue_res->num_rows > 0) {
                                while ($appt = $queue_res->fetch_assoc()) {
                                    $p_name = htmlspecialchars($appt['patient_name']);
                                    $p_code = $appt['patient_code'] ?: ($appt['is_external'] ? 'EXTERNAL' : 'N/A');
                                    $p_time = date("h:i A", strtotime($appt['appointment_time']));
                                    $p_phone = htmlspecialchars($appt['phone']);
                                    $urgency = $appt['urgency'] ?? 'Normal';
                                    $is_external = $appt['is_external'];
                                    
                                    // Urgency styling
                                    $urgency_class = ($urgency == 'Emergency') ? 'background:#ef4444; color:#fff;' : (($urgency == 'Urgent') ? 'background:#f59e0b; color:#fff;' : 'background:rgba(255,255,255,0.05); color:#94a3b8;');
                                    
                                    // Calculate Age
                                    $p_age = '--';
                                    if (!empty($appt['date_of_birth'])) {
                                        $dob_date = new DateTime($appt['date_of_birth']);
                                        $now_date = new DateTime();
                                        $p_age = $now_date->diff($dob_date)->y . ' Yrs';
                                    }

                                    $p_dob = !empty($appt['date_of_birth']) ? date('d M, Y', strtotime($appt['date_of_birth'])) : 'N/A';
                                    $p_gender = $appt['gender'] ?: 'Unknown';
                                    $p_id = $appt['patient_id'];
                                    $a_id = $appt['appointment_id'];
                                    $status = $appt['status'];
                                    $weight = $appt['weight'] ?: 'N/A';
                                    $height = $appt['height'] ?: 'N/A';
                                    $last_visit = $appt['last_visit'] ? date('d M, Y', strtotime($appt['last_visit'])) : ($appt['is_external'] ? 'Guest Patient' : 'First Visit');
                                    $reg_date = $appt['registered_date'] ? date('d M, Y', strtotime($appt['registered_date'])) : 'N/A';
                                    
                                    echo '
                                    <div class="appointment-item" style="border-left: 4px solid '.($urgency == 'Emergency' ? '#ef4444' : ($urgency == 'Urgent' ? '#f59e0b' : ($status == 'Requested' ? '#fbbf24' : '#3b82f6'))).'; margin-bottom: 20px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div class="doc-info" style="flex: 1;">
                                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                                    <h4 style="font-size: 16px; margin: 0;">'.$p_name.' <span style="font-weight: normal; color: #94a3b8; font-size: 13px;">(ID: '.$p_code.')</span></h4>
                                                    '.($is_external ? '<span class="badge" style="background:#3b82f6; color:#fff; font-size:10px;">EXTERNAL</span>' : '').'
                                                    <span class="badge" style="font-size:10px; '.$urgency_class.'">'.$urgency.'</span>
                                                    <span class="badge-status-'.$status.'" style="font-weight:600; font-size:10px;">'.$status.'</span>
                                                </div>
                                                
                                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                                                    <div style="font-size: 12px; color: #94a3b8;"><i class="fas fa-calendar-alt"></i> DOB: <span style="color: #fff;">'.$p_dob.' ('.$p_age.')</span></div>
                                                    <div style="font-size: 12px; color: #94a3b8;"><i class="fas fa-venus-mars"></i> Gender: <span style="color: #fff;">'.$p_gender.'</span></div>
                                                    <div style="font-size: 12px; color: #94a3b8;"><i class="fas fa-weight"></i> Weight: <span style="color: #fff;">'.$weight.' kg</span></div>
                                                    <div style="font-size: 12px; color: #94a3b8;"><i class="fas fa-ruler-vertical"></i> Height: <span style="color: #fff;">'.$height.' cm</span></div>
                                                    <div style="font-size: 12px; color: #94a3b8;"><i class="fas fa-history"></i> Last Visit: <span style="color: #3b82f6; font-weight: 600;">'.$last_visit.'</span></div>
                                                    <div style="font-size: 12px; color: #94a3b8;"><i class="fas fa-user-plus"></i> Registered: <span style="color: #fff;">'.$reg_date.'</span></div>
                                                </div>

                                                <div style="display: flex; gap: 15px; align-items: center;">
                                                    <div style="font-size: 13px; color: #fff; font-weight: 600;"><i class="fas fa-clock"></i> Scheduled: '.$p_time.'</div>
                                                    <div style="font-size: 13px; color: #94a3b8;"><i class="fas fa-info-circle"></i> Reason: <span style="color:#fbbf24;">'.((!empty($appt['reason']) && $appt['reason'] !== '0') ? htmlspecialchars($appt['reason']) : 'General Consultation').'</span></div>
                                                    <div style="flex: 1;"></div>

                                                </div>
                                            </div>
                                            <div class="action-btns" style="display:flex; flex-direction: column; gap:10px; margin-left: 20px;">';
                                                if($status == 'Requested' || $status == 'Pending') {
                                                    echo '<form method="POST" style="margin:0; display:flex; gap:10px;">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <input type="hidden" name="appt_id" value="'.$a_id.'">
                                                            <button type="submit" name="new_status" value="Scheduled" class="btn-consult" style="background:#10b981; flex:1; font-size: 11px; padding: 5px;"><i class="fas fa-check"></i> Accept</button>
                                                            <button type="submit" name="new_status" value="Cancelled" class="btn-consult" style="background:#ef4444; flex:1; font-size: 11px; padding: 5px;"><i class="fas fa-times"></i> Decline</button>
                                                          </form>';
                                                } else if($status == 'Approved' || $status == 'Scheduled' || $status == 'Checked-In' || $status == 'Confirmed' || $status == 'Pending Lab' || $status == 'Lab Completed' || $status == 'Waiting') {
                                                    if($appt['is_external']) {
                                                        echo '<span style="font-size: 11px; color: #94a3b8; text-align: center;"><i class="fas fa-id-card"></i> Reg. Required</span>';
                                                    } else {
                                                        $is_lab = ($status == 'Pending Lab' || $status == 'Lab Completed');
                                                        $btn_text = $is_lab ? 'Review Lab' : 'Consult Now';
                                                        $btn_icon = $is_lab ? 'fa-flask' : 'fa-user-md';
                                                        $btn_style = $is_lab ? 'background:#a855f7;' : 'background:#3b82f6;';
                                                        echo '<a href="doctor_dashboard.php?patient_id='.$p_id.'&appt_id='.$a_id.'" class="btn-consult" style="'.$btn_style.' text-align: center;"><i class="fas '.$btn_icon.'"></i> '.$btn_text.'</a>';
                                                    }
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
                    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 0px;">
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
                        <div class="section-head" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3>Recent Lab Orders</h3>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <button onclick="openReportModal()" style="background: transparent; border: 1px solid #3b82f6; color: #3b82f6; padding: 5px 10px; border-radius: 6px; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                                <a href="doctor_lab_orders.php" style="color: #4fc3f7; font-size: 13px;">View All</a>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php
                            // Fetch both Pending and Completed orders
                            // Using labtest_id DESC as proxy for time if created_at is missing, but prefer created_at if available.
                            
                            $query = "
                                SELECT l.labtest_id, l.patient_id, l.test_name, l.status, l.priority, l.report_path, l.result, l.created_at, r.name as patient_name 
                                FROM lab_tests l 
                                JOIN users u ON l.patient_id = u.user_id 
                                JOIN registrations r ON u.registration_id = r.registration_id 
                                WHERE l.doctor_id = ? 
                                ORDER BY l.labtest_id DESC LIMIT 10
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
                                        
                                        // Safe JS Variables
                                        $test_name = $rep['test_name'];
                                        $pat_name = $rep['patient_name'];

                                        // Status Colors
                                        $status_color = '#94a3b8'; 
                                        if ($status == 'Completed') $status_color = '#10b981';
                                        elseif ($status == 'Pending' || $status == 'Requested') $status_color = '#fbbf24';
                                        elseif ($status == 'Processing' || $status == 'Conducted') $status_color = '#f59e0b';

                                        // FUZZY MATCHING for Manual Reports
                                        if (!$has_pdf) {
                                            $p_id_cur = $rep['patient_id'] ?? null;
                                            $search_pat_name = mysqli_real_escape_string($conn, $pat_name);
                                            $search_test_name = mysqli_real_escape_string($conn, $test_name);
                                            
                                            $m_query = "SELECT file_path, report_title FROM manual_reports WHERE ";
                                            $m_query .= "( (patient_id = $p_id_cur) OR (report_title LIKE '%$search_pat_name%') ) ";
                                            $m_query .= "AND (report_title LIKE '%$search_test_name%' OR report_type LIKE '%$search_test_name%' OR created_at >= NOW() - INTERVAL 48 HOUR) ";
                                            $m_query .= "ORDER BY (CASE WHEN report_title LIKE '%$search_test_name%' THEN 1 ELSE 2 END) ASC, created_at DESC LIMIT 1";
                                            
                                            $m_check = $conn->query($m_query);
                                            if ($m_check && $m_row = $m_check->fetch_assoc()) {
                                                $pdf_link = htmlspecialchars($m_row['file_path']);
                                                $has_pdf = true;
                                                $status = "Report Uploaded"; 
                                                $status_color = "#4fc3f7";
                                            }
                                        }

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
                    <div style="font-size: 13.5px; line-height: 1.6; color: #cbd5e1; background: rgba(251, 191, 36, 0.05); padding: 18px; border-radius: 12px; border: 1px solid rgba(251, 191, 36, 0.1); margin-bottom: 20px;">
                        <span style="font-size: 11px; color: #fbbf24; font-weight: 700; text-transform: uppercase;">Current Complaint / Reason</span>
                        <p style="margin-top: 5px; color: #fff; font-weight: 500;">
                            <?php echo (!empty($active_patient['current_reason']) && $active_patient['current_reason'] !== '0') ? htmlspecialchars($active_patient['current_reason']) : 'General Consultation / Routine Checkup'; ?>
                        </p>
                    </div>

                    <!-- Medical History Section -->
                    <div style="font-size: 13.5px; line-height: 1.6; color: #cbd5e1; background: rgba(59, 130, 246, 0.05); padding: 18px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.1); margin-bottom: 20px;">
                        <span style="font-size: 11px; color: #3b82f6; font-weight: 700; text-transform: uppercase;">Medical History</span>
                        <p style="margin-top: 5px;">
                            <?php 
                            $has_static = !empty($active_patient['medical_history']);
                            $has_dynamic = !empty($history_records);
                            
                            if ($has_static) {
                                echo '<div style="margin-bottom:10px;">'.htmlspecialchars($active_patient['medical_history']).'</div>';
                            }

                            if ($has_dynamic) {
                                echo ($has_static ? '<div style="border-top:1px solid rgba(255,255,255,0.1); padding-top:10px; margin-top:10px;">' : '');
                                echo '<strong style="font-size:10px; color:#94a3b8; text-transform:uppercase; display:block; margin-bottom:5px;">Recent Diagnoses:</strong>';
                                echo '<ul style="padding-left:15px; margin:0; color:#e2e8f0; font-size:12px;">';
                                $shown = 0;
                                foreach($history_records as $rec) {
                                    if ($shown >= 3) break;
                                    if (!empty($rec['diagnosis'])) {
                                        echo '<li style="margin-bottom:4px;">' . htmlspecialchars($rec['diagnosis']) . ' <span style="color:#64748b; font-size:10px;">(' . date('M Y', strtotime($rec['created_at'])) . ')</span></li>';
                                        $shown++;
                                    }
                                }
                                echo '</ul>';
                                echo ($has_static ? '</div>' : '');
                            }

                            if (!$has_static && !$has_dynamic) {
                                echo "No previous medical history found for this patient.";
                            }
                            ?>
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
                    <h4 style="margin-bottom: 15px; font-size: 14px; color: #94a3b8;">Lab Reports (Current Appt)</h4>
                    <div style="font-size: 13px; color: #cbd5e1; background: rgba(59, 130, 246, 0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.2); margin-bottom: 25px;">
                        <?php if(!empty($current_lab_results)): ?>
                            <?php foreach($current_lab_results as $lab): ?>
                                <div style="margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;">
                                    <strong style="color:#4fc3f7; font-size: 11px;"><?php echo htmlspecialchars($lab['test_name']); ?></strong>
                                    <p style="margin: 4px 0; font-size: 12px; color: #e2e8f0;"><?php echo nl2br(htmlspecialchars($lab['result'])); ?></p>
                                    <?php if($lab['report_path']): ?>
                                        <a href="<?php echo $lab['report_path']; ?>" target="_blank" style="color: #10b981; font-size: 11px; text-decoration: none;"><i class="fas fa-file-pdf"></i> View PDF Report</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size: 12px; color: #64748b;">No lab results for this appointment yet.</p>
                        <?php endif; ?>
                    </div>

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
                    <style>
                        .validation-msg { font-size: 11px; margin-top: 4px; display: none; transition: 0.3s; }
                        .validation-msg.error { color: #ef4444; display: block; }
                        .validation-msg.success { color: #10b981; }
                        .form-input-validated { transition: border-color 0.3s, box-shadow 0.3s; }
                        .form-input-validated.invalid { border-color: #ef4444 !important; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.1); }
                        .form-input-validated.valid { border-color: #10b981 !important; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1); }
                    </style>
                </div>

                <!-- Right: Active Consultation -->
                <div class="consult-form">
                    <form method="POST" action="save_consultation.php" onsubmit="return validateConsultationForm(this)">
                        <input type="hidden" name="patient_id" value="<?php echo $_GET['patient_id'] ?? ''; ?>">
                        <input type="hidden" name="doctor_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="appointment_id" value="<?php echo $_GET['appt_id'] ?? ''; ?>">
                        
                        <div style="display: flex; gap: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 10px;">
                            <button type="button" class="tab-btn active">Diagnosis & Notes</button>
                        </div>

                        <div>
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">Diagnosis</label>
                            <input type="text" name="diagnosis" id="live_diagnosis" required class="form-input-validated" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; color: white; margin-bottom: 5px;" placeholder="Describe diagnosis (e.g. Seasonal Flu with mild fever)">
                            <div id="diag_msg" class="validation-msg"></div>
                            <div style="margin-bottom: 15px;"></div>
                            
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">Doctor's Internal Notes / Treatment Plan</label>
                            <textarea name="treatment" id="live_treatment" required class="form-input-validated" style="width: 100%; height: 80px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 10px; color: white; padding: 15px; margin-bottom: 5px;" placeholder="Detailed clinical observations and treatment plan..."></textarea>
                            <div id="treat_msg" class="validation-msg"></div>
                            <div style="margin-bottom: 15px;"></div>
                            
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block;">Special Notes to Patient</label>
                            <textarea name="special_notes" id="live_special_notes" required class="form-input-validated" style="width: 100%; height: 60px; background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 10px; color: white; padding: 15px; margin-bottom: 5px;" placeholder="Advice to patient (e.g. Drink plenty of fluids, complete the full antibiotic course)"></textarea>
                            <div id="sn_msg" class="validation-msg"></div>
                            <div style="margin-bottom: 15px;"></div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;"><i class="fas fa-pills" style="color:#10b981;"></i> Prescription</label>
                                <textarea name="prescription" id="live_prescription" class="form-input-validated" style="width: 100%; height: 80px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 10px; color: white; padding: 15px;" placeholder="Medicine Name - Dosage (e.g. 500mg) - Frequency (e.g. 1-0-1)"></textarea>
                                <div id="presc_msg" class="validation-msg"></div>
                            </div>
                            <div>
                                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;"><i class="fas fa-flask" style="color:#4fc3f7;"></i> Lab Order</label>
                                <div style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px;">
                                    <label style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: #94a3b8; cursor: pointer; margin-bottom: 8px;">
                                        <input type="checkbox" name="lab_required" value="1" id="labCheck" onchange="toggleLabFields(this.checked)"> Lab Test Required
                                    </label>
                                    
                                    <div id="labOrderFields" style="display: none;">
                                         <select name="lab_category" id="labCat" style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 8px; border-radius: 6px; color: white; font-size: 12px; margin-bottom: 10px;" onchange="loadLabTests(this.value)">
                                             <option value="">Select Lab Category</option>
                                             <?php
                                             $cat_res = $conn->query("SELECT category_name FROM lab_categories");
                                             while($cat = $cat_res->fetch_assoc()) {
                                                 echo "<option value='".htmlspecialchars($cat['category_name'])."'>".htmlspecialchars($cat['category_name'])."</option>";
                                             }
                                             ?>
                                         </select>

                                         <div id="labTestsContainer" style="max-height: 120px; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); display: none;">
                                             <p style="font-size: 11px; color: #64748b;">Select tests...</p>
                                         </div>
                                         <input type="hidden" name="lab_test_name" id="finalLabTests">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            let selectedGlobalLabTests = new Set();

                            function toggleLabFields(checked) {
                                const container = document.getElementById('labOrderFields');
                                const labCat = document.getElementById('labCat');
                                
                                if (checked) {
                                    container.style.display = 'block';
                                    labCat.setAttribute('required', 'required');
                                } else {
                                    container.style.display = 'none';
                                    labCat.removeAttribute('required');
                                    selectedGlobalLabTests.clear();
                                    document.getElementById('finalLabTests').value = '';
                                }
                            }

                            async function loadLabTests(category) {
                                const container = document.getElementById('labTestsContainer');
                                if (!category) {
                                    container.style.display = 'none';
                                    return;
                                }

                                container.innerHTML = '<p style="font-size: 11px; color: #4fc3f7;"><i class="fas fa-spinner fa-spin"></i> Loading tests...</p>';
                                container.style.display = 'block';

                                try {
                                    const response = await fetch('fetch_lab_tests_ajax.php?category_name=' + encodeURIComponent(category));
                                    const tests = await response.json();

                                    if (tests.length > 0) {
                                        let html = '';
                                        tests.forEach(test => {
                                            const safeTest = test.replace(/"/g, '&quot;');
                                            const isChecked = selectedGlobalLabTests.has(test) ? 'checked' : '';
                                            html += `
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #fff; margin-bottom: 5px; cursor: pointer;">
                                                    <input type="checkbox" class="lab-test-item" value="${safeTest}" onchange="updateLabTests(this)" ${isChecked}> ${test}
                                                </label>
                                            `;
                                        });
                                        container.innerHTML = html;
                                    } else {
                                        container.innerHTML = '<p style="font-size: 11px; color: #ef4444;">No tests found for this category.</p>';
                                    }
                                } catch (e) {
                                    container.innerHTML = '<p style="font-size: 11px; color: #ef4444;">Error loading tests.</p>';
                                }
                            }

                            function updateLabTests(checkbox) {
                                if (checkbox) {
                                    if (checkbox.checked) {
                                        selectedGlobalLabTests.add(checkbox.value);
                                    } else {
                                        selectedGlobalLabTests.delete(checkbox.value);
                                    }
                                } else {
                                    // Fallback if called without specific checkbox
                                    const checkboxes = document.querySelectorAll('.lab-test-item');
                                    checkboxes.forEach(cb => {
                                        if (cb.checked) selectedGlobalLabTests.add(cb.value);
                                        else selectedGlobalLabTests.delete(cb.value);
                                    });
                                }
                                document.getElementById('finalLabTests').value = Array.from(selectedGlobalLabTests).join(', ');
                            }
                        </script>

                        <!-- Admission Recommendation -->
                        <div style="margin-top: 20px; background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px; padding: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #f87171; font-weight: 700; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="admit_patient" value="1" onchange="document.getElementById('admissionFields').style.display = this.checked ? 'block' : 'none'"> 
                                <i class="fas fa-bed"></i> Recommend Inpatient Admission
                            </label>
                            
                            <div id="admissionFields" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(239, 68, 68, 0.2);">
                                <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 5px;">Required Ward Type</label>
                                <select name="ward_type_req" style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 8px; border-radius: 6px; color: white; font-size: 12px; margin-bottom: 10px;">
                                    <option value="General">General Ward</option>
                                    <option value="Semi-Private">Semi-Private Room</option>
                                    <option value="Private">Private Room</option>
                                    <option value="ICU">ICU</option>
                                    <option value="Emergency">Emergency</option>
                                </select>
                                
                                <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 5px;">Reason for Admission</label>
                                <textarea name="admission_reason" rows="2" style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 8px; border-radius: 6px; color: white; font-size: 12px;" placeholder="Clinical reason for admission..."></textarea>
                            </div>
                        </div>

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

                // Fix: Clear URL parameters to prevent modal opening on refresh
                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('patient_id');
                    url.searchParams.delete('appt_id');
                    window.history.replaceState({}, document.title, url.toString());
                }
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

        // Weekly Consultation Traffic Chart
        const wctCtx = document.getElementById('weeklyConsultChart').getContext('2d');
        new Chart(wctCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $labels_json; ?>,
                datasets: [{
                    label: 'Patients Seen',
                    data: <?php echo $counts_json; ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 30,
                    hoverBackgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                        ticks: { color: '#94a3b8', stepSize: 1 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#fff', font: { weight: '600' } }
                    }
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

        function validateConsultationForm(form) {
            const diagnosis = form.diagnosis.value.trim();
            const treatment = form.treatment.value.trim();
            const specialNotes = form.special_notes.value.trim();

            if (diagnosis.length < 3 || isGibberish(diagnosis)) {
                alert("Please provide a valid diagnosis.");
                return false;
            }

            if (treatment.length < 5 || isGibberish(treatment)) {
                alert("Please provide more detailed treatment plan / notes.");
                return false;
            }

            if (specialNotes.length > 0 && isGibberish(specialNotes)) {
                alert("Special notes contain invalid text.");
                return false;
            }

            return true;
        }

        const isGibberish = (str) => {
            if (!str) return false;
            const cleanStr = str.toLowerCase().replace(/[^a-z]/g, '');
            if (cleanStr.length === 0) return false;
            const words = str.toLowerCase().split(/\s+/);
            const vowelPattern = /[aeiouy]/;
            for (let word of words) {
                const cleanWord = word.replace(/[^a-z]/g, '');
                if (cleanWord.length > 3 && !vowelPattern.test(cleanWord)) return true;
            }
            if (/(.)\1{4,}/.test(cleanStr)) return true;
            const patterns = ['qwerty', 'asdfgh', 'zxcvbn', 'qazwsx', 'edcrfv', '123456'];
            for (let p of patterns) { if (cleanStr.includes(p)) return true; }
            const uniqueChars = new Set(cleanStr).size;
            const ratio = uniqueChars / cleanStr.length;
            if (cleanStr.length > 8 && ratio > 0.7 && !str.includes(' ')) return true;
            return false;
        };

        function setValidationUI(el, msgEl, isValid, message) {
            if (!el || !msgEl) return;
            if (isValid) {
                el.classList.remove('invalid');
                el.classList.add('valid');
                msgEl.innerText = "";
                msgEl.classList.remove('error');
            } else {
                el.classList.remove('valid');
                el.classList.add('invalid');
                msgEl.innerText = message;
                msgEl.classList.add('error');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fields = [
                { id: 'live_diagnosis', msgId: 'diag_msg', min: 3, label: 'Diagnosis' },
                { id: 'live_treatment', msgId: 'treat_msg', min: 5, label: 'Treatment Plan' },
                { id: 'live_special_notes', msgId: 'sn_msg', min: 5, label: 'Special Notes' },
                { id: 'live_prescription', msgId: 'presc_msg', min: 5, label: 'Prescription' }
            ];

            fields.forEach(field => {
                const el = document.getElementById(field.id);
                const msgEl = document.getElementById(field.msgId);
                if (el) {
                    el.addEventListener('input', function() {
                        const val = this.value.trim();
                        if (val.length === 0) {
                            el.classList.remove('invalid', 'valid');
                            msgEl.innerText = "";
                            msgEl.classList.remove('error');
                            return;
                        }
                        if (val.length < field.min) {
                            setValidationUI(el, msgEl, false, `${field.label} is too short.`);
                        } else if (isGibberish(val)) {
                            setValidationUI(el, msgEl, false, `${field.label} contains invalid text.`);
                        } else {
                            setValidationUI(el, msgEl, true, "");
                        }
                    });
                }
            });
        });
    </script>

    <?php 
    // Set staff_type for the modal
    $staff_type = 'doctor';
    include 'includes/report_upload_modal.php'; 
    ?>    <script>
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
    <script>
        function toggleDropdown(id) {
            // Close all other dropdowns
            document.querySelectorAll('.header-dropdown').forEach(d => {
                if(d.id !== id) d.classList.remove('show');
            });
            // Toggle current
            document.getElementById(id).classList.toggle('show');
        }

        // Close on outside click
        window.onclick = function(event) {
            if (!event.target.closest('.header-item-relative')) {
                document.querySelectorAll('.header-dropdown').forEach(d => {
                    d.classList.remove('show');
                });
            }
        }
    </script>
    <!-- Report Upload Modal Integration -->
    <?php 
    $staff_type = 'doctor';
    include 'includes/report_upload_modal.php'; 
    ?>
</body>
</html>
