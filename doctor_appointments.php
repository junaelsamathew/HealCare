<?php
session_start();
include 'includes/db_connect.php';
include 'includes/email_config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch doctor info
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
    $specialization = "General Healthcare";
    $department = "General Medicine";
    $designation = "Professional Consultant";
}

$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
if (stripos($doctor_name, 'Dr.') === false && stripos($doctor_name, 'Doctor') === false) {
    $doctor_name = "Dr. " . $doctor_name;
}

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appt_id = intval($_POST['appt_id']);
    $new_status = $_POST['status'];
    
    // Validate Status Logic
    // If approving a Requested appointment -> Approved
    
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
        
        if ($stmt_upd->execute()) {
             // --- SEND NOTIFICATION EMAIL IF CANCELLED ---
             if ($new_status == 'Cancelled') {
                 try {
                     $stmt_details = $conn->prepare("SELECT a.*, r_doc.name as doctor_name, u_pat.email as patient_email, pp.name as patient_name 
                                                  FROM appointments a 
                                                  JOIN users u_pat ON a.patient_id = u_pat.user_id
                                                  JOIN patient_profiles pp ON u_pat.user_id = pp.user_id
                                                  JOIN users u_doc ON a.doctor_id = u_doc.user_id
                                                  JOIN registrations r_doc ON u_doc.registration_id = r_doc.registration_id
                                                  WHERE a.appointment_id = ?");
                     $stmt_details->bind_param("i", $appt_id);
                     $stmt_details->execute();
                     $details_res = $stmt_details->get_result();
                     
                     if ($details_res && $row = $details_res->fetch_assoc()) {
                         $mail = new PHPMailer(true);
                         configureDefaultMail($mail);
                         $mail->addAddress($row['patient_email'], $row['patient_name']);
                         $mail->isHTML(true);
                         $mail->Subject = 'Appointment Update - HealCare Hospital';
                         
                         $mail->Body = '
                         <div style="font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #eee; border-radius: 10px; color: #333;">
                             <h2 style="color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px;">Appointment Status: Cancelled</h2>
                             <p>Dear <strong>' . htmlspecialchars($row['patient_name']) . '</strong>,</p>
                             <p>We regret to inform you that your appointment has been <strong>cancelled</strong> by the doctor or hospital administration.</p>
                             
                             <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ef4444;">
                                 <p style="margin: 5px 0;"><strong>Booking ID:</strong> BK-' . $appt_id . '</p>
                                 <p style="margin: 5px 0;"><strong>Doctor:</strong> ' . htmlspecialchars($row['doctor_name']) . '</p>
                                 <p style="margin: 5px 0;"><strong>Scheduled Date:</strong> ' . date('d M Y', strtotime($row['appointment_date'])) . '</p>
                             </div>
                             
                             <p>Please contact the hospital or log in to the portal to schedule a new appointment.</p>
                             <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                             <p style="font-size: 0.8em; color: #777; text-align: center;">Sent by HealCare Hospital System</p>
                         </div>';
                         
                         $mail->send();
                     }
                 } catch (Exception $e) { }
             }

             $redirect_status = ($new_status == 'Scheduled') ? 'Approved/Scheduled' : ($_GET['status'] ?? 'All');
             header("Location: doctor_appointments.php?status=" . $redirect_status);
             exit();
        }
    }
}

// Filter Logic matches 'Filter By: All, Requested, Approved/Scheduled, Completed, Cancelled'
$current_filter = $_GET['status'] ?? 'All';

$where_conditions = ["a.doctor_id = $user_id"];

if ($current_filter == 'Requested') {
    $where_conditions[] = "a.status IN ('Requested', 'Pending')";
} elseif ($current_filter == 'Approved/Scheduled') {
    $where_conditions[] = "a.status IN ('Approved', 'Confirmed', 'Scheduled', 'Pending Lab', 'Checked-In')";
} elseif ($current_filter == 'Completed') {
    $where_conditions[] = "a.status IN ('Completed', 'Lab Completed')";
} elseif ($current_filter == 'Cancelled') {
    $where_conditions[] = "a.status = 'Cancelled'";
}

$where_clause = implode(' AND ', $where_conditions);

// Updated Query to fetch proper details
$sql_appts = "SELECT a.*, 
                    rp.name as reg_name, 
                    pp.name as profile_name,
                    pp.gender,
                    pp.age,
                    pp.phone,
                    pp.patient_code
             FROM appointments a 
             LEFT JOIN users up ON a.patient_id = up.user_id
             LEFT JOIN registrations rp ON up.registration_id = rp.registration_id
             LEFT JOIN patient_profiles pp ON a.patient_id = pp.user_id
             WHERE $where_clause
             ORDER BY a.appointment_date DESC, a.appointment_time ASC";
$res_appts = $conn->query($sql_appts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        /* Override Dashboard CSS for this specific page design */
        :root {
            --card-bg: #0f172a;
            --cat-red: #ef4444; 
            --cat-green: #10b981;
            --cat-blue: #3b82f6;
            --cat-gray: #64748b;
            --cat-orange: #f59e0b;
        }

        .main-content { padding: 40px; }
        
        .page-title { font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 5px; }
        .page-subtitle { font-size: 14px; color: #94a3b8; margin-bottom: 30px; }

        /* Filter Bar */
        .filter-bar-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            background: #0f172a;
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            width: fit-content;
        }
        .filter-label { color: #cbd5e1; font-weight: 700; font-size: 14px; margin-right: 10px; }
        .filter-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: 0.3s;
        }
        .filter-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .filter-btn.active {
            background: #1e293b;
            color: #3b82f6;
            border-color: #3b82f6;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.1);
        }

        /* Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
        @media (max-width: 1200px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .cards-grid { grid-template-columns: 1fr; } }

        /* Card Design */
        .appt-menu-card {
            background: #0f172a;
            border-radius: 16px;
            padding: 25px;
            position: relative;
            border: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 240px;
        }
        
        /* Left Colored Line */
        .appt-menu-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 15px;
            bottom: 15px;
            width: 4px;
            border-radius: 0 4px 4px 0;
        }
        
        .border-red::before { background: var(--cat-red); box-shadow: 2px 0 10px rgba(239,68,68,0.3); }
        .border-green::before { background: var(--cat-green); box-shadow: 2px 0 10px rgba(16,185,129,0.3); }
        .border-blue::before { background: var(--cat-blue); box-shadow: 2px 0 10px rgba(59,130,246,0.3); }
        .border-gray::before { background: var(--cat-gray); }
        .border-orange::before { background: var(--cat-orange); box-shadow: 2px 0 10px rgba(245,158,11,0.3); }

        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        
        .p-name { font-size: 16px; font-weight: 700; color: #fff; margin: 0 0 5px 0; }
        
        .p-details { font-size: 12px; color: #94a3b8; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 15px; }
        .p-details i { color: #64748b; }
        
        .date-badge-box {
            background: #020617;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 8px 12px;
            text-align: center;
            min-width: 50px;
        }
        .db-day { display: block; font-size: 16px; font-weight: 700; color: #3b82f6; line-height: 1; }
        .db-month { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; margin-top: 2px; }

        .info-row { margin-bottom: 12px; }
        .info-row i { width: 20px; color: #fff; opacity: 0.7; }
        .info-text { color: #cbd5e1; font-size: 13px; }
        .reason-text { color: #94a3b8; font-size: 13px; }

        .time-display {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
        }

        .status-pill-plain {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            margin-left: auto;
            color: #64748b;
        }

        .card-btn-area { margin-top: auto; padding-top: 20px; }
        .btn-card-action {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            color: #64748b;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-card-action:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        .btn-card-primary {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border-color: rgba(59, 130, 246, 0.2);
        }
        .btn-card-primary:hover {
            background: #3b82f6;
            color: #fff;
        }
        
        .btn-card-primary:hover {
            background: #3b82f6;
            color: #fff;
        }
        
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
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
        <a href="index.php" style="text-decoration:none; display: flex; align-items: center; gap: 10px;">
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

    <header class="secondary-header" style="display: flex; justify-content: flex-end; padding: 10px 40px; background: #0f172a; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div style="flex: 1;"></div>
        <div class="user-controls" style="display: flex; align-items: center; gap: 20px;">
            <span class="user-greeting" style="color: #cbd5e1; font-size: 14px;">Welcome, <strong style="color: #fff;"><?php echo $doctor_name; ?></strong></span>
            <a href="logout.php" class="btn-logout" style="padding: 6px 15px; background: transparent; border: 1px solid #3b82f6; color: #fff; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s;">Sign Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="doctor_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="doctor_patients.php" class="nav-link"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_prescriptions.php" class="nav-link"><i class="fas fa-file-prescription"></i> Prescriptions</a>

                <a href="doctor_lab_orders.php" class="nav-link"><i class="fas fa-flask"></i> Lab Orders</a>
                <a href="doctor_leave.php" class="nav-link"><i class="fas fa-calendar-minus"></i> Apply Leave</a>
                <a href="doctor_settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <h1 class="page-title">Appointments Management</h1>
            <p class="page-subtitle">Manage and track all patient appointments.</p>

            <!-- Filter Bar -->
            <div class="filter-bar-container">
                <span class="filter-label">Filter By:</span>
                <a href="?status=All" class="filter-btn <?php echo $current_filter == 'All' ? 'active' : ''; ?>">All</a>
                <a href="?status=Requested" class="filter-btn <?php echo $current_filter == 'Requested' ? 'active' : ''; ?>">Requested</a>
                <a href="?status=Approved/Scheduled" class="filter-btn <?php echo $current_filter == 'Approved/Scheduled' ? 'active' : ''; ?>">Approved/Scheduled</a>
                <a href="?status=Completed" class="filter-btn <?php echo $current_filter == 'Completed' ? 'active' : ''; ?>">Completed</a>
                <a href="?status=Cancelled" class="filter-btn <?php echo $current_filter == 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>

            <!-- Cards Grid -->
            <div class="cards-grid">
                <?php if ($res_appts->num_rows > 0): ?>
                    <?php while($appt = $res_appts->fetch_assoc()): ?>
                        <?php 
                            // Determin Border Color & Status Display
                            $status = $appt['status'];
                            $border_class = 'border-gray';
                            if ($status == 'Cancelled') $border_class = 'border-red';
                            elseif ($status == 'Completed' || $status == 'Lab Completed') $border_class = 'border-green';
                            elseif ($status == 'Approved' || $status == 'Scheduled' || $status == 'Confirmed' || $status == 'Pending Lab') $border_class = 'border-blue';
                            elseif ($status == 'Requested' || $status == 'Pending') $border_class = 'border-orange'; 
                            elseif ($status == 'Lab Completed') $border_class = 'border-green';
                            $date_obj = new DateTime($appt['appointment_date']);
                            $day = $date_obj->format('d');
                            $month = $date_obj->format('M');
                            $time = date('h:i A', strtotime($appt['appointment_time'] ?? $appt['appointment_date']));
                            
                            $p_name = !empty($appt['profile_name']) ? $appt['profile_name'] : $appt['reg_name'];
                            $gender = !empty($appt['gender']) ? $appt['gender'] : 'N/A';
                            $age = !empty($appt['age']) ? $appt['age'] . ' Yrs' : 'N/A';
                            $phone = !empty($appt['phone']) ? $appt['phone'] : 'N/A';
                        ?>
                        <div class="appt-menu-card <?php echo $border_class; ?>">
                            <div class="card-header">
                                <div>
                                    <h3 class="p-name"><?php echo htmlspecialchars($p_name); ?></h3>
                                    <div class="p-details">
                                        <span><i class="fas fa-venus-mars"></i> <?php echo $gender; ?></span> • 
                                        <span><?php echo $age; ?></span> • 
                                        <span><i class="fas fa-phone"></i> <?php echo $phone; ?></span>
                                    </div>
                                </div>
                                <div class="date-badge-box">
                                    <span class="db-day"><?php echo $day; ?></span>
                                    <span class="db-month"><?php echo $month; ?></span>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-text"><i class="fas fa-info-circle"></i> Reason: <span class="reason-text"><?php echo (!empty($appt['reason']) && $appt['reason'] !== '0') ? htmlspecialchars($appt['reason']) : 'General Consultation'; ?></span></span>
                            </div>

                             <div class="time-display">
                                <i class="far fa-clock"></i> <?php echo $time; ?>
                                <?php if($appt['consultation_mode'] == 'Online'): ?>
                                    <span style="margin-left: 10px; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700;">
                                        <i class="fas fa-video"></i> ONLINE
                                    </span>
                                <?php else: ?>
                                    <span style="margin-left: 10px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700;">
                                        <i class="fas fa-hospital"></i> CLINIC
                                    </span>
                                <?php endif; ?>
                                <span class="status-pill-plain"><?php echo strtoupper($status); ?></span>
                            </div>

                            <?php if(!empty($appt['meeting_link'])): ?>
                                <div style="margin-top: 10px; padding: 8px; background: rgba(16, 185, 129, 0.05); border: 1px dashed #10b981; border-radius: 6px; font-size: 11px;">
                                    <strong style="color: #10b981;">Meeting Link:</strong> 
                                    <a href="<?php echo htmlspecialchars($appt['meeting_link']); ?>" target="_blank" style="color: #fff; text-decoration: underline; word-break: break-all;">
                                        Join Meeting <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="card-btn-area">
                                <?php if($status == 'Requested' || $status == 'Pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                        
                                        <?php if($appt['consultation_mode'] == 'Online'): ?>
                                            <div style="margin-bottom: 12px;">
                                                <input type="url" name="meeting_link" placeholder="Paste Google Meet / Jitsi link here" 
                                                       style="width: 100%; padding: 8px; background: #020617; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #fff; font-size: 12px;" required>
                                            </div>
                                        <?php endif; ?>

                                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                            <button type="submit" name="status" value="Scheduled" class="btn-card-action btn-card-primary" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.2);">
                                                <i class="fas fa-check-circle"></i> Accept
                                            </button>
                                            <button type="submit" name="status" value="Cancelled" class="btn-card-action" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">
                                                <i class="fas fa-times-circle"></i> Decline
                                            </button>
                                        </div>
                                    </form>
                                <?php elseif($status == 'Approved' || $status == 'Scheduled' || $status == 'Confirmed' || $status == 'Lab Completed' || $status == 'Pending Lab' || $status == 'Checked-In' || $status == 'Waiting'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                        <div style="display:grid; grid-template-columns: 1fr; gap:10px;">
                                             <?php 
                                                $is_lab = ($status == 'Pending Lab' || $status == 'Lab Completed');
                                                $btn_text = $is_lab ? 'Review Lab & Consult' : 'Start Consultation';
                                             ?>
                                             <a href="doctor_dashboard.php?patient_id=<?php echo $appt['patient_id']; ?>&appt_id=<?php echo $appt['appointment_id']; ?>" class="btn-card-action btn-card-primary" style="text-align:center; text-decoration:none;"><?php echo $btn_text; ?></a>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-card-action" disabled>Closed (<?php echo $status; ?>)</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b;">
                        <p>No appointments found in this category.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
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
