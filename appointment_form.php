<?php
session_start();
include 'includes/db_connect.php';

// Check auth
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Mock Doctors Data (Consistent with other pages)
// Fetch Doctors from DB
$doctors = [];
$dept_filter = isset($_GET['dept']) ? trim($_GET['dept']) : '';

// Base Query (Updated to include current leave status)
$sql = "SELECT d.user_id as id, r.name, d.department as dept, d.experience as exp, d.qualification as qual, r.profile_photo as img, d.consultation_fee,
        (SELECT COUNT(*) FROM doctor_leaves dl WHERE dl.doctor_id = d.user_id AND dl.status = 'Approved' AND CURDATE() BETWEEN dl.start_date AND dl.end_date) as is_on_leave
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        JOIN registrations r ON u.registration_id = r.registration_id";

// Apply Filter
if (!empty($dept_filter) && $dept_filter !== 'Select Department') {
    $safe_dept = mysqli_real_escape_string($conn, $dept_filter);
    $sql .= " WHERE TRIM(d.department) = TRIM('$safe_dept')";
}

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if(empty($row['img'])) {
            $row['img'] = 'images/doctor-' . (rand(1, 10)) . '.jpg'; 
        }
        $doctors[] = $row;
    }
}

// Handle Query Params
$pre_doc_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$pre_dept = isset($_GET['dept']) ? $_GET['dept'] : '';

$selected_doc = null;
if ($pre_doc_id) {
    foreach ($doctors as $d) {
        if ($d['id'] == $pre_doc_id) {
            $selected_doc = $d;
            break;
        }
    }
}
// Fetch Logged-in User Data
$user_data = [];
$is_logged_in = true; // Always true here
if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    // Fetch patient code and details
    $q = $conn->query("SELECT p.patient_code, r.phone, r.email, r.name 
                       FROM users u 
                       JOIN registrations r ON u.registration_id = r.registration_id 
                       LEFT JOIN patient_profiles p ON u.user_id = p.user_id 
                       WHERE u.user_id = $uid");
    if($q && $q->num_rows > 0) {
        $user_data = $q->fetch_assoc();
    }
}

// Slot Capacity Logic
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$booked_slots = [];
$max_capacity = 5; // Max patients per time slot

// Token Number Logic (Based on actual appointments)
$token_number = 1;
if ($pre_doc_id) {
    $token_q = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('Cancelled')");
    if ($token_q) {
        $token_q->bind_param("is", $pre_doc_id, $selected_date);
        $token_q->execute();
        $token_number = $token_q->get_result()->fetch_assoc()['total'] + 1;
        $token_q->close();
    }
}

// Fetch actual booked slot counts from DB
if ($pre_doc_id) {
    $stmt = $conn->prepare("SELECT TIME_FORMAT(appointment_time, '%h:%i %p') as time_fmt, COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('Cancelled') GROUP BY appointment_time");
    if ($stmt) {
        $stmt->bind_param("is", $pre_doc_id, $selected_date);
        $stmt->execute();
        $slot_res = $stmt->get_result();
        while ($row = $slot_res->fetch_assoc()) {
            $t = trim($row['time_fmt']);
            // Store both formats to ensure matching regardless of leading zero
            $booked_slots[$t] = $row['count'];
            $booked_slots[date('h:i A', strtotime($t))] = $row['count'];
        }
        $stmt->close();
    }
}

function renderSlotChip($time, $booked_slots, $max_capacity, $index) {
    $count = isset($booked_slots[$time]) ? $booked_slots[$time] : 0;
    $remaining = $max_capacity - $count;
    if ($remaining < 0) $remaining = 0;
    
    $status_class = ($remaining <= 0) ? 'full' : '';
    $color = ($remaining <= 0) ? '#ef4444' : (($remaining <= 2) ? '#f59e0b' : '#10b981');
    $label = ($remaining <= 0) ? "Full" : "$remaining Slots Left";
    $onclick = ($remaining > 0) ? "selectSlot(this)" : "return false;";
    
    echo '<div class="slot-chip ' . $status_class . '" onclick="' . $onclick . '" data-index="' . $index . '" data-booked="' . $count . '" style="' . ($remaining <= 0 ? 'opacity:0.5; cursor:not-allowed;' : '') . '">';
    echo '<div>' . $time . '</div>';
    echo '<div style="font-size: 10px; font-weight: 600; color:' . $color . '; margin-top: 2px;">' . $label . '</div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        /* Specific Styles for Form Elements matching Dashboard Theme */
        .token-alert {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-size: 1rem;
            display: none;
        }
        .token-number { font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0 5px; }

        .booking-wrapper {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            padding: 30px;
            border-radius: 12px;
        }

        .top-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 8px;
        }
        .filter-group label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; color: var(--text-gray); }
        .filter-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            background: #0f172a;
            color: white;
            border-radius: 6px;
            font-size: 0.9rem;
            color-scheme: dark;
        }
        .filter-control:focus { outline: none; border-color: var(--primary-blue); }
        .filter-control::-webkit-calendar-picker-indicator, .form-control-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        .doctor-display {
            display: flex;
            gap: 20px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .doc-profile-left { display: flex; align-items: center; gap: 20px; flex: 1; min-width: 300px; }
        .doc-img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; }
        .doc-details h3 { margin: 0 0 5px; color: white; font-size: 1.1rem; }
        .doc-qual { color: var(--text-gray); font-size: 0.85rem; margin-bottom: 5px; }

        .consult-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-top: 10px; }
        .consult-table th { background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 8px; font-weight: 600; text-align: center; color: var(--text-gray); }
        .consult-table td { border: 1px solid var(--border-color); padding: 8px; text-align: center; color: white; }

        .btn-time-slot {
            background: var(--primary-blue);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            display: block;
            width: 100%;
            max-width: 200px;
            transition: 0.3s;
        }
        .btn-time-slot:hover { background: #2563eb; }

        .time-slots-panel { display: none; margin-top: 20px; padding: 20px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-color); border-radius: 8px; }
        .session-title { font-weight: 600; color: var(--text-gray); margin: 15px 0 10px; display: block; font-size: 0.9rem; }
        
        .slot-chip {
            display: inline-block;
            padding: 8px 15px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin: 5px;
            cursor: pointer;
            color: var(--text-light);
            transition: 0.3s;
            font-size: 0.85rem;
        }
        .slot-chip:hover { border-color: var(--primary-blue); }
        .slot-chip.selected { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }

        .reg-form-container { background: rgba(0,0,0,0.2); padding: 30px; border-radius: 12px; margin-top: 20px; border: 1px solid var(--border-color); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        
        .form-control-input { 
            width: 100%; padding: 10px; 
            border: 1px solid var(--border-color); 
            border-radius: 6px; 
            background: #0f172a; 
            color: white; 
        }
        
        .btn-continue {
            background: #10b981;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: 0.3s;
        }
        .btn-continue:hover { background: #059669; }

        .captcha-box {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            display: inline-block;
            margin-top: 20px;
        }
        .captcha-img {
            font-family: monospace;
            font-weight: bold;
            font-size: 1.2rem;
            letter-spacing: 3px;
            background: #fff;
            color: #333;
            padding: 5px 15px;
            border-radius: 4px;
            margin-right: 15px;
            text-decoration: line-through;
        }

        .hidden { display: none; }
        .required { color: #ef4444; }
        
        /* Validation Styles */
        .validation-msg { font-size: 11px; margin-top: 4px; display: none; transition: 0.3s; }
        .validation-msg.error { color: #ef4444; display: block; }
        .form-control-input.invalid, .filter-control.invalid { border-color: #ef4444 !important; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.1); }
        .form-control-input.valid, .filter-control.valid { border-color: #10b981 !important; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1); }
        .captcha-input.invalid { border-color: #ef4444 !important; }
        .captcha-input.valid { border-color: #10b981 !important; }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-phone-alt"></i></div>
                <div class="info-details"><span class="info-label">EMERGENCY</span><span class="info-value">(+254) 717 783 146</span></div>
            </div>
        </div>
    </header>

    <header class="secondary-header">
        <div class="brand-section"><div class="brand-icon">+</div><div class="brand-name">HealCare</div></div>
        <div class="user-controls"><span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($username); ?></strong></span><a href="logout.php" class="btn-logout">Log Out</a></div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link">Dashboard</a>
                <a href="book_appointment.php" class="nav-link active">Book Appointment</a>
                <a href="my_appointments.php" class="nav-link">My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Appointment Form</h1>
                <p>Select a doctor and schedule your visit</p>
            </div>

            <div id="tokenMsg" class="token-alert" style="display: none;">
                <i class="fas fa-ticket-alt"></i> Your token number is <span class="token-number">--</span><br>
                Please fill in the details below to complete the booking.
            </div>

            <div class="booking-wrapper">
                <form action="process_booking.php" method="POST" onsubmit="return validateCaptcha()">
                    <input type="hidden" name="token" value="<?php echo $token_number; ?>">
                    <input type="hidden" name="doctor_name" value="<?php echo $selected_doc ? htmlspecialchars($selected_doc['name']) : ''; ?>">
                    
                    <!-- Filters -->
                    <div class="top-filters">
                        <div class="filter-group">
                            <label>Department</label>
                            <select class="filter-control" name="dept" onchange="window.location.href='?dept='+this.value">
                                    <option value="">Select Department</option>
                                    <option value="General Medicine / Cardiovascular" <?php if($pre_dept == 'General Medicine / Cardiovascular') echo 'selected'; ?>>General Medicine / Cardiovascular</option>
                                    <option value="Gynecology" <?php if($pre_dept == 'Gynecology') echo 'selected'; ?>>Gynecology</option>
                                    <option value="Orthopedics (Bones)" <?php if($pre_dept == 'Orthopedics (Bones)') echo 'selected'; ?>>Orthopedics (Bones)</option>
                                    <option value="ENT" <?php if($pre_dept == 'ENT') echo 'selected'; ?>>ENT</option>
                                    <option value="Ophthalmology" <?php if($pre_dept == 'Ophthalmology') echo 'selected'; ?>>Ophthalmology</option>
                                    <option value="Dermatology" <?php if($pre_dept == 'Dermatology') echo 'selected'; ?>>Dermatology</option>
                                    <option value="Pediatrics" <?php if($pre_dept == 'Pediatrics') echo 'selected'; ?>>Pediatrics</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Doctor</label>
                            <select class="filter-control" name="doctor_id" onchange="window.location.href='?doctor_id='+this.value+'&dept=<?php echo urlencode($dept_filter); ?>'">
                                <option value="">Select Doctor</option>
                                <?php if (!empty($doctors)): ?>
                                    <?php foreach($doctors as $d): ?>
                                        <option value="<?php echo $d['id']; ?>" <?php echo ($pre_doc_id == $d['id']) ? 'selected' : ''; ?>>
                                            <?php echo $d['name'] . ($d['is_on_leave'] > 0 ? ' (On Leave)' : ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No doctors for this department</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date</label>
                            <input type="date" id="appointment_date" name="date" class="filter-control" 
                                value="<?php echo $selected_date; ?>" 
                                min="<?php echo date('Y-m-d'); ?>"
                                onchange="window.location.href='?doctor_id=<?php echo $pre_doc_id; ?>&dept=<?php echo urlencode($dept_filter); ?>&date='+this.value">
                            <div id="date_msg" class="validation-msg"></div>
                        </div>
                    </div>

                    <?php if($selected_doc): 
                        // 1. Fetch Weekly Schedule
                        $weekly_schedule = [];
                        $sch_stmt = $conn->prepare("SELECT day_of_week, start_time, end_time, status FROM doctor_schedules WHERE doctor_id = ?");
                        if ($sch_stmt) {
                            $sch_stmt->bind_param("i", $pre_doc_id);
                            $sch_stmt->execute();
                            $sch_res = $sch_stmt->get_result();
                            while($s = $sch_res->fetch_assoc()) $weekly_schedule[$s['day_of_week']] = $s;
                            $sch_stmt->close();
                        }

                        // Fallback: If no schedule is set, use a default 9 AM - 4 PM (Mon-Sat)
                        if (empty($weekly_schedule)) {
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            foreach ($days as $d) {
                                $weekly_schedule[$d] = [
                                    'day_of_week' => $d,
                                    'start_time' => '09:00:00',
                                    'end_time' => '16:00:00',
                                    'status' => 'Available'
                                ];
                            }
                            $weekly_schedule['Sunday'] = ['status' => 'Not Available'];
                        }

                        // 2. Fetch Leaves
                        $leaves = [];
                        $l_stmt = $conn->prepare("SELECT start_date, end_date FROM doctor_leaves WHERE doctor_id = ? AND status = 'Approved' AND end_date >= CURDATE()");
                        if ($l_stmt) {
                            $l_stmt->bind_param("i", $pre_doc_id);
                            $l_stmt->execute();
                            $res = $l_stmt->get_result();
                            while($l = $res->fetch_assoc()) $leaves[] = $l;
                            $l_stmt->close();
                        }

                        // 3. Pre-calculate availability for next 21 days (extended to find enough available days)
                        $avail_dates = [];
                        $check_date = new DateTime();
                        $count_found = 0;
                        for ($i = 0; $i < 21; $i++) {
                            if ($count_found >= 7) break;
                            $curr_date_str = $check_date->format('Y-m-d');
                            $day_name = $check_date->format('l'); // Full day name (Monday, etc.)
                            
                            // Check Weekly Schedule Status
                            $sched = isset($weekly_schedule[$day_name]) ? $weekly_schedule[$day_name] : null;
                            if (!$sched || $sched['status'] == 'Not Available') {
                                $check_date->modify('+1 day');
                                continue; 
                            }

                            // Check Leave
                            $is_on_leave = false;
                            foreach ($leaves as $leave) {
                                if ($curr_date_str >= $leave['start_date'] && $curr_date_str <= $leave['end_date']) {
                                    $is_on_leave = true; break;
                                }
                            }

                            $total_daily_cap = 45;
                            $c_stmt = $conn->prepare("SELECT COUNT(*) as booked FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'Cancelled'");
                            $c_stmt->bind_param("is", $pre_doc_id, $curr_date_str);
                            $c_stmt->execute();
                            $booked_count = $c_stmt->get_result()->fetch_assoc()['booked'];
                            $c_stmt->close();

                            $avail_dates[] = [
                                'date' => $curr_date_str,
                                'day' => $check_date->format('D'),
                                'display' => $check_date->format('M d'),
                                'slots' => $total_daily_cap - $booked_count,
                                'on_leave' => $is_on_leave
                            ];
                            $count_found++;
                            $check_date->modify('+1 day');
                        }

                        // 4. Status for SELECTED DATE
                        $is_on_leave_today = false;
                        $is_not_working_today = true;
                        foreach ($avail_dates as $ad) {
                            if ($ad['date'] == $selected_date) {
                                $is_not_working_today = false;
                                if ($ad['on_leave']) $is_on_leave_today = true;
                                break;
                            }
                        }
                    ?>
                    <!-- Doctor Info -->
                    <div class="doctor-display">
                        <div class="doc-profile-left">
                            <img src="<?php echo $selected_doc['img']; ?>" class="doc-img" onerror="this.src='images/doctor-1.jpg'">
                            <div class="doc-details">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <h3><?php echo $selected_doc['name']; ?></h3>
                                    <?php if ($is_on_leave_today): ?>
                                        <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 10px; border: 1px solid #ef4444; padding: 3px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase;">Not Available Today</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 10px; border: 1px solid #10b981; padding: 3px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase;">Available</span>
                                    <?php endif; ?>
                                </div>
                                <div class="doc-qual"><?php echo $selected_doc['qual']; ?></div>
                                <div style="color:var(--primary-blue); font-weight:600; text-transform:uppercase; margin-bottom: 5px;"><?php echo $selected_doc['dept']; ?></div>
                                <div style="background: rgba(59, 130, 246, 0.1); color: var(--primary-blue); padding: 5px 12px; border-radius: 15px; display: inline-block; font-size: 0.85rem; font-weight: 700;">
                                    Consultation Fees: ₹<?php echo number_format($selected_doc['consultation_fee'], 0); ?>
                                </div>
                            </div>
                        </div>
                        <div style="flex:1;">
                            <?php if ($is_on_leave_today): ?>
                                <?php
                                $next_avail = null;
                                foreach ($avail_dates as $ad) { if (!$ad['on_leave']) { $next_avail = $ad; break; } }
                                ?>
                                <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 15px; color: #ef4444;">
                                    <h4 style="margin: 0 0 8px; font-size: 15px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-calendar-times"></i> Out of Office</h4>
                                    <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.4;">Dr. <?php echo explode(' ', $selected_doc['name'])[1] ?? $selected_doc['name']; ?> is on leave for <b><?php echo date('M d, Y', strtotime($selected_date)); ?></b>.</p>
                                    <?php if ($next_avail): ?>
                                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed rgba(239, 68, 68, 0.2); display: flex; align-items: center; gap: 10px; color: #10b981;">
                                            <i class="fas fa-calendar-check"></i>
                                            <span style="font-size: 13px; font-weight: 600;">Next Available: <?php echo $next_avail['display']; ?> (<?php echo $next_avail['day']; ?>)</span>
                                            <button type="button" onclick="window.location.href='?doctor_id=<?php echo $pre_doc_id; ?>&dept=<?php echo urlencode($dept_filter); ?>&date=<?php echo $next_avail['date']; ?>'" 
                                                    style="background: #10b981; color: white; border: none; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.3s;">Select This Date</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($is_not_working_today): ?>
                                <div style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 15px; color: #f59e0b;">
                                    <h4 style="margin: 0 0 8px; font-size: 15px;"><i class="fas fa-clock"></i> Not Consulting Today</h4>
                                    <p style="margin: 0; font-size: 12px; color: #94a3b8;">The selected date is outside this doctor's weekly consultation schedule. Please choose a date from the availability roadmap below.</p>
                                </div>
                            <?php else: ?>
                                <div style="font-weight:600; margin-bottom:10px; font-size:0.9rem; color:white;">Standard Weekly Schedule</div>
                                <table class="consult-table">
                                    <thead>
                                        <tr>
                                            <th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th><th>SUN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php 
                                            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                                            foreach($days as $day): 
                                                $s = isset($weekly_schedule[$day]) ? $weekly_schedule[$day] : null;
                                                $time_text = ($s && $s['status'] == 'Available') 
                                                    ? date('H', strtotime($s['start_time'])) . '-' . date('H', strtotime($s['end_time']))
                                                    : 'OFF';
                                                $cell_color = ($time_text == 'OFF') ? 'color: #64748b;' : 'color: #10b981; font-weight: 600;';
                                            ?>
                                                <td style="<?php echo $cell_color; ?>"><?php echo $time_text; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h3 style="color: white; font-size: 1rem; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-calendar-alt" style="color: #3b82f6;"></i> Doctor's Availability Roadmap
                            <small style="color: #94a3b8; font-weight: 400; font-size: 0.75rem;">(Next 7 Working Days)</small>
                        </h3>
                        <div style="display: flex; gap: 12px; overflow-x: auto; padding: 5px 0 15px;">
                            <?php
                            foreach ($avail_dates as $ad) {
                                $is_selected = ($ad['date'] == $selected_date) ? 'border-color: '.($ad['on_leave'] ? '#ef4444' : '#10b981').'; background: rgba('.($ad['on_leave'] ? '239, 68, 68' : '16, 185, 129').', 0.15);' : '';
                                $status_color = $ad['on_leave'] ? '#ef4444' : '#10b981';
                                $status_icon = $ad['on_leave'] ? 'fa-plane-departure' : 'fa-check-circle';
                                $status_label = $ad['on_leave'] ? 'Leave' : 'Available';
                                
                                echo '<div onclick="window.location.href=\'?doctor_id='.$pre_doc_id.'&dept='.urlencode($dept_filter).'&date='.$ad['date'].'\'" 
                                           style="min-width: 105px; cursor: pointer; border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; text-align: center; background: rgba(255,255,255,0.02); transition: 0.3s; '.$is_selected.'">
                                        <div style="font-weight: 700; color: white; font-size: 0.95rem; margin-bottom: 2px;">'.$ad['display'].'</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">'.$ad['day'].'</div>
                                        <div style="font-size: 0.7rem; color: '.$status_color.'; margin-top: 8px; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                            <i class="fas '.$status_icon.'"></i> '.$status_label.'
                                        </div>
                                      </div>';
                            }
                            ?>
                        </div>
                    </div>

                    <?php if (!$is_on_leave_today && !$is_not_working_today): ?>
                    <button type="button" class="btn-time-slot" onclick="toggleSlots()">Select Time Slot</button>
                    <?php endif; ?>

                    <div class="time-slots-panel" id="slotsPanel" style="<?php echo ($pre_doc_id) ? 'display:block;' : ''; ?>">
                        <?php 
                        $day_name = date('l', strtotime($selected_date));
                        $sch = isset($weekly_schedule[$day_name]) ? $weekly_schedule[$day_name] : null;
                        
                        // User Request: Strictly 9 AM to 4 PM
                        if ($sch && $sch['status'] == 'Available'):
                            $start = new DateTime('09:00:00');
                            $end = new DateTime('16:00:00');
                            $interval = new DateInterval('PT30M');
                            
                            echo '<span class="session-title"><i class="fas fa-clock" style="color:#3b82f6;"></i> Consulting Hours: 09:00 AM to 04:00 PM</span>';
                            echo '<div class="slots-container">';
                            
                            $curr = clone $start;
                            $slot_index = 0;
                            while ($curr < $end) {
                                renderSlotChip($curr->format('h:i A'), $booked_slots, $max_capacity, $slot_index);
                                $curr->add($interval);
                                $slot_index++;
                            }
                            echo '</div>';
                        else:
                        ?>
                            <div style="text-align: center; padding: 20px; color: var(--text-gray);">
                                <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                <p>No consulting hours defined for this day.</p>
                            </div>
                        <?php endif; ?>
                        <input type="hidden" name="time_slot" id="selectedTimeSlot" required>
                    </div>

                    <!-- Patient Details Area -->
                    <div id="patientDetailsArea" style="display:none;">
                        <input type="hidden" name="reg_status" value="yes" id="reg_yes">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin: 40px 0 20px;">
                            <h3 style="margin: 0; color: white; font-size: 1.2rem;">Patient Details</h3>
                        </div>

                        <div class="reg-form-container">
                            <div class="info-text" style="color:var(--primary-blue); margin-bottom:15px;">
                                <i class="fas fa-user-check"></i> <b>Verified Patient: <?php echo htmlspecialchars($user_data['name'] ?? ''); ?></b>
                            </div>
                            
                            <div class="form-grid">
                                <div>
                                    <label>OP Number (Verified)</label>
                                    <input type="text" name="op_number" class="form-control-input" value="<?php echo $user_data['patient_code'] ?? ''; ?>" readonly style="opacity:0.6;">
                                </div>
                                <div>
                                    <label>Mobile Number (Verified)</label>
                                    <input type="tel" name="reg_mobile" class="form-control-input" value="<?php echo $user_data['phone'] ?? ''; ?>" readonly style="opacity:0.6;">
                                </div>
                            </div>
                            <div>
                                <label>Email (Verified)</label>
                                <input type="email" name="reg_email" class="form-control-input" value="<?php echo $user_data['email'] ?? ''; ?>" readonly style="opacity:0.6;">
                            </div>
                            
                            <!-- Captcha -->
                            <div style="margin-bottom:20px;">
                                <label style="display:block; margin-bottom:8px; font-weight:600; color:var(--text-light);">Symptoms / Reason for Visit <span class="required">*</span></label>
                                <textarea name="reason" id="symptoms_reason" rows="3" class="form-control-input" placeholder="Describe your symptoms briefly (e.g., fever for 3 days, chest pain, headache)" required minlength="10" style="resize:vertical;"></textarea>
                                <div id="symptoms_msg" class="validation-msg"></div>
                            </div>

                            <!-- Consultation Mode Selection -->
                            <div style="margin-bottom:25px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                                <label style="display:block; margin-bottom:12px; font-weight:600; color:var(--text-light); border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;">
                                    <i class="fas fa-stethoscope"></i> Preferred Consultation Mode <span class="required">*</span>
                                </label>
                                <div style="display: flex; gap: 30px; padding: 5px 0;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #cbd5e1; font-size: 14px;">
                                        <input type="radio" name="consultation_mode" value="In-Person" checked style="width: 18px; height: 18px; accent-color: #3b82f6;">
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-weight: 600; color: white;">In-Person Visit</span>
                                            <small style="font-size: 11px; opacity: 0.7;">Walk-in to the hospital</small>
                                        </div>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #cbd5e1; font-size: 14px;">
                                        <input type="radio" name="consultation_mode" value="Online" style="width: 18px; height: 18px; accent-color: #10b981;">
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-weight: 600; color: #10b981;">Video Consultation</span>
                                            <small style="font-size: 11px; opacity: 0.7;">Join via secure video link</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="captcha-box">
                                <label style="display:block; margin-bottom:5px; color:var(--text-gray); font-size:0.85rem;">Security Check <span class="required">*</span></label>
                                <span class="captcha-img">5692</span>
                                <input type="text" id="captchaInput" class="captcha-input" placeholder="Code" style="padding:10px; width:100px; border:1px solid var(--border-color); background:#0f172a; color:white; border-radius:4px; transition: 0.3s;">
                                <div id="captcha_msg" class="validation-msg"></div>
                            </div>

                            <div style="margin-top:20px; color:var(--text-gray); font-size:0.9rem;">
                                <input type="checkbox" required id="terms" checked> <label for="terms">I agree to the Hospital Terms & Conditions.</label>
                            </div>

                            <button type="submit" class="btn-continue">Confirm Booking</button>
                        </div>
                    </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:50px; color:var(--text-gray); font-style:italic;">Please select a doctor to proceed with booking.</div>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSlots() {
            document.getElementById('slotsPanel').style.display = 'block';
        }
        function selectSlot(el) {
            let slots = document.querySelectorAll('.slot-chip');
            slots.forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('selectedTimeSlot').value = el.querySelector('div:first-child').innerText;
            
            // Calculate Token based on Slot index and current bookings
            // Formula: (SlotIndex * MaxCapacity) + BookedInSlot + 1
            let slotIndex = parseInt(el.getAttribute('data-index'));
            let bookedInSlot = parseInt(el.getAttribute('data-booked'));
            let maxCapacity = 5;
            let token = (slotIndex * maxCapacity) + (bookedInSlot + 1);

            // Update UI
            document.querySelector('.token-number').innerText = token;
            document.querySelector('input[name="token"]').value = token;
            
            // Show Token and Details
            document.getElementById('tokenMsg').style.display = 'block';
            document.getElementById('patientDetailsArea').style.display = 'block';
            
            // Smooth Scroll
            document.getElementById('tokenMsg').scrollIntoView({behavior: 'smooth'});
        }
        function validateCaptcha() {
            var val = document.getElementById('captchaInput').value;
            if(val !== '5692') {
                alert('Invalid Captcha Code!');
                return false;
            }
            return true;
        }

        // Live Validation
        document.addEventListener('DOMContentLoaded', function() {
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

            const dateEl = document.getElementById('appointment_date');
            const dateMsg = document.getElementById('date_msg');

            if (dateEl) {
                dateEl.addEventListener('input', function() {
                    const val = this.value;
                    if (!val) {
                        this.classList.remove('invalid', 'valid');
                        dateMsg.style.display = 'none';
                        return;
                    }

                    const parts = val.split('-');
                    const selectedDate = new Date(parts[0], parts[1] - 1, parts[2]);
                    selectedDate.setHours(0,0,0,0);
                    
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    
                    const tomorrow = new Date(today);
                    tomorrow.setDate(today.getDate() + 1);

                    if (selectedDate < today) {
                        this.classList.remove('valid');
                        this.classList.add('invalid');
                        dateMsg.innerText = "Error: Past dates are not allowed.";
                        dateMsg.classList.add('error');
                        dateMsg.style.color = '#ef4444';
                        dateMsg.style.display = 'block';
                    } else {
                        this.classList.remove('invalid');
                        this.classList.add('valid');
                        dateMsg.classList.remove('error');
                        dateMsg.style.display = 'block';
                        
                        if (selectedDate.getTime() === today.getTime()) {
                            dateMsg.innerText = "✓ Scheduled for Today";
                            dateMsg.style.color = '#10b981';
                        } else if (selectedDate.getTime() === tomorrow.getTime()) {
                            dateMsg.innerText = "✓ Scheduled for Tomorrow";
                            dateMsg.style.color = '#10b981';
                        } else {
                            dateMsg.innerText = "✓ Valid future date";
                            dateMsg.style.color = '#10b981';
                        }
                    }
                });
                // Initial check
                dateEl.dispatchEvent(new Event('input'));
            }

            const symptomsEl = document.getElementById('symptoms_reason');
            const symptomsMsg = document.getElementById('symptoms_msg');
            const captchaEl = document.getElementById('captchaInput');
            const captchaMsg = document.getElementById('captcha_msg');

            if (symptomsEl) {
                symptomsEl.addEventListener('input', function() {
                    const val = this.value.trim();
                    if (val.length === 0) {
                        this.classList.remove('invalid', 'valid');
                        symptomsMsg.innerText = "";
                        return;
                    }
                    if (val.length < 10) {
                        this.classList.remove('valid');
                        this.classList.add('invalid');
                        symptomsMsg.innerText = "Please describe your symptoms in more detail (min 10 chars).";
                        symptomsMsg.classList.add('error');
                    } else if (isGibberish(val)) {
                        this.classList.remove('valid');
                        this.classList.add('invalid');
                        symptomsMsg.innerText = "Input contains invalid or junk text.";
                        symptomsMsg.classList.add('error');
                    } else {
                        this.classList.remove('invalid');
                        this.classList.add('valid');
                        symptomsMsg.innerText = "";
                        symptomsMsg.classList.remove('error');
                    }
                });
            }

            if (captchaEl) {
                captchaEl.addEventListener('input', function() {
                    if (this.value === '5692') {
                        this.style.borderColor = '#10b981';
                        captchaMsg.innerText = "✓ Valid Code";
                        captchaMsg.style.color = '#10b981';
                        captchaMsg.style.display = 'block';
                    } else if (this.value.length >= 4) {
                        this.style.borderColor = '#ef4444';
                        captchaMsg.innerText = "✘ Incorrect Code";
                        captchaMsg.style.color = '#ef4444';
                        captchaMsg.style.display = 'block';
                    } else {
                        this.style.borderColor = 'var(--border-color)';
                        captchaMsg.style.display = 'none';
                    }
                });
            }
        });
    </script>
    <style>
        /* Override chatbot position for patient pages - position at bottom */
        .chatbot-toggler {
            bottom: 30px !important;
        }
        
        .chatbot {
            bottom: 110px !important;
        }
        
        @media (max-width: 490px) {
            .chatbot-toggler {
                bottom: 20px !important;
            }
        }
    </style>
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>
