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
        .status-scheduled { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
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
                <a href="#" class="tab active">Today's Appointments</a>
                <a href="#" class="tab">Upcoming</a>
                <a href="#" class="tab">Completed</a>
                <a href="#" class="tab">Cancelled</a>
            </div>

            <!-- Today's List -->
            <div class="appointment-card">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="time-badge">09:30 AM</div>
                    <div>
                        <h3 style="color: white; margin-bottom: 5px;">Dileep Mathew</h3>
                        <p style="font-size: 13px; color: #94a3b8;"><i class="fas fa-hashtag"></i> HC-P-2026-9901 • Routine Checkup</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <span class="status-badge status-scheduled">Scheduled</span>
                    <button class="btn-action"><i class="fas fa-check"></i> Mark Completed</button>
                    <button class="btn-action" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2);"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>

            <div class="appointment-card">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="time-badge">11:15 AM</div>
                    <div>
                        <h3 style="color: white; margin-bottom: 5px;">Anjali Sharma</h3>
                        <p style="font-size: 13px; color: #94a3b8;"><i class="fas fa-hashtag"></i> HC-P-2026-8842 • Viral Fever</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <span class="status-badge status-scheduled">Scheduled</span>
                    <button class="btn-action"><i class="fas fa-check"></i> Mark Completed</button>
                    <button class="btn-action" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2);"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>

            <div class="appointment-card">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="time-badge">02:00 PM</div>
                    <div>
                        <h3 style="color: white; margin-bottom: 5px;">Suresh Raina</h3>
                        <p style="font-size: 13px; color: #94a3b8;"><i class="fas fa-hashtag"></i> HC-P-2026-3121 • Follow-up</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <span class="status-badge status-completed">Completed</span>
                    <button class="btn-action" disabled style="opacity: 0.5; cursor: default;">View Rx</button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
