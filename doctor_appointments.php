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
    $specialization = "General Healthcare";
    $department = "General Medicine";
    $designation = "Professional Consultant";
}

$doctor_name = "Dr. " . htmlspecialchars($_SESSION['username']);

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appt_id = intval($_POST['appt_id']);
    $new_status = $_POST['status'];
    // Verify ownership
    $check_own = $conn->query("SELECT appointment_id FROM appointments WHERE appointment_id = $appt_id AND doctor_id = $user_id");
    if ($check_own->num_rows > 0) {
        $stmt_upd = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt_upd->bind_param("si", $new_status, $appt_id);
        if ($stmt_upd->execute()) {
            echo "<script>alert('Appointment updated to $new_status'); window.location.href='doctor_appointments.php';</script>";
        }
    }
}

// Fetch Appointments
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

// Logic for tabs could be added here, for now showing Today + Upcoming sorted
$sql_appts = "SELECT a.*, 
                    rp.name as reg_name, 
                    pp.name as profile_name
             FROM appointments a 
             LEFT JOIN users up ON a.patient_id = up.user_id
             LEFT JOIN registrations rp ON up.registration_id = rp.registration_id
             LEFT JOIN patient_profiles pp ON a.patient_id = pp.user_id
             WHERE a.doctor_id = $user_id AND a.status != 'Cancelled' 
             ORDER BY a.appointment_date ASC";
$res_appts = $conn->query($sql_appts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .tabs {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 10px;
        }
        .tab {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        .tab.active {
            color: #4fc3f7;
            border-bottom-color: #4fc3f7;
        }
        .appointment-card {
            background: rgba(30, 41, 59, 0.4);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .appointment-card:hover { 
            background: rgba(255, 255, 255, 0.05);
            transform: scale(1.01);
        }
        .time-badge {
            background: rgba(79, 195, 247, 0.1);
            color: #4fc3f7;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
        }
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-Scheduled, .status-Confirmed { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-Approved { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-Completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .status-Requested { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        
        .btn-action {
            background: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        .btn-action:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: #4fc3f7;
            color: #4fc3f7;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-calendar-alt"></i></div>
                <div class="info-details"><span class="info-label">DATE</span><span class="info-value"><?php echo date('d M Y'); ?></span></div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-bell"></i></div>
                <div class="info-details"><span class="info-label">NOTIFICATIONS</span><span class="info-value">3 Pending Updates</span></div>
            </div>
        </div>
    </header>

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
            <div class="dashboard-header">
                <h1>Appointment Manager</h1>
                <p>Manage your daily schedule and consultation updates.</p>
            </div>

            <div class="tabs">
                <a href="#" class="tab active">Appointments</a>
                <!-- <a href="#" class="tab">Upcoming</a>
                <a href="#" class="tab">Completed</a>
                <a href="#" class="tab">Cancelled</a> -->
            </div>

            <!-- Dynamic List -->
            <?php if ($res_appts->num_rows > 0): ?>
                <?php while($appt = $res_appts->fetch_assoc()): ?>
                    <div class="appointment-card">
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div class="time-badge">
                                <?php echo date('h:i A', strtotime($appt['appointment_time'] ?? $appt['appointment_date'])); ?>
                                <br><small style="font-size:10px;"><?php echo date('M d', strtotime($appt['appointment_date'])); ?></small>
                            </div>
                            <div>
                                <?php $p_display_name = $appt['profile_name'] ?? $appt['reg_name'] ?? 'Unknown Patient'; ?>
                                <h3 style="color: white; margin-bottom: 5px;"><?php echo htmlspecialchars($p_display_name); ?></h3>
                                <p style="font-size: 13px; color: #94a3b8;">
                                    <i class="fas fa-hashtag"></i> Token: <?php echo htmlspecialchars($appt['queue_number'] ?? 'N/A'); ?> • 
                                    ID: <?php echo $appt['patient_id'] ?? 'Walk-in'; ?>
                                </p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <span class="status-badge status-<?php echo str_replace(' ', '', $appt['status']); ?>"><?php echo $appt['status']; ?></span>
                            
                            <?php if ($appt['status'] == 'Requested' || $appt['status'] == 'Pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                    <button type="submit" name="status" value="Approved" class="btn-action" style="background:#10b981; color:white; border:none;"><i class="fas fa-check"></i> Approve</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($appt['status'] == 'Approved' || $appt['status'] == 'Checked-In' || $appt['status'] == 'Scheduled' || $appt['status'] == 'Confirmed'): ?>
                                <a href="doctor_dashboard.php?patient_id=<?php echo $appt['patient_id']; ?>&appt_id=<?php echo $appt['appointment_id']; ?>" class="btn-action" style="color:#3b82f6; border-color:#3b82f6; text-decoration:none;"><i class="fas fa-stethoscope"></i> Consult</a>
                                <?php if($appt['status'] != 'Scheduled' && $appt['status'] != 'Approved' && $appt['status'] != 'Confirmed'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                        <button type="submit" name="status" value="Completed" class="btn-action" style="color:#10b981; border-color:#10b981;"><i class="fas fa-check-double"></i> Complete</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($appt['status'] != 'Completed' && $appt['status'] != 'Cancelled'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Reject/Cancel appointment?');">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                    <button type="submit" name="status" value="Cancelled" class="btn-action" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2);"><i class="fas fa-times"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:#aaa;">
                    <i class="fas fa-calendar-times" style="font-size:40px; margin-bottom:20px; opacity:0.5;"></i>
                    <p>No appointments found.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
