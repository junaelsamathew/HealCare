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
    <title>Patients - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .search-input {
            flex: 1;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 12px;
            color: white;
            font-size: 14px;
        }
        .patient-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .patient-table th {
            text-align: left;
            padding: 15px 20px;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
        }
        .patient-row {
            background: rgba(30, 41, 59, 0.4);
            transition: all 0.3s;
        }
        .patient-row:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }
        .patient-row td {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .patient-row td:first-child { border-left: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px 0 0 12px; }
        .patient-row td:last-child { border-right: 1px solid rgba(255, 255, 255, 0.05); border-radius: 0 12px 12px 0; }
        
        .btn-view {
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(79, 195, 247, 0.1);
            color: #4fc3f7;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(79, 195, 247, 0.2);
        }
        .btn-view:hover {
            background: #4fc3f7;
            color: white;
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
                <div class="info-details"><span class="info-label">NOTIFICATIONS</span><span class="info-value">5 New Consults</span></div>
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
                <a href="doctor_patients.php" class="nav-link active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_prescriptions.php" class="nav-link"><i class="fas fa-file-prescription"></i> Prescriptions</a>
                <a href="doctor_lab_orders.php" class="nav-link"><i class="fas fa-flask"></i> Lab Orders</a>
                <a href="doctor_leave.php" class="nav-link"><i class="fas fa-calendar-minus"></i> Apply Leave</a>
                <a href="doctor_settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Assigned Patients</h1>
                <p>Viewing all patients under <?php echo $department; ?> department.</p>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search by Patient Name or ID (e.g. HC-P-2026-0001)...">
                <button class="btn-view" style="padding: 12px 30px;">Search</button>
            </div>

            <div class="content-section">
                <table class="patient-table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Age / Gender</th>
                            <th>Last Appointment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="patient-row">
                            <td>HC-P-2026-9901</td>
                            <td><strong style="color: white;">Dileep Mathew</strong></td>
                            <td>32 / Male</td>
                            <td>12 Sep 2025</td>
                            <td>
                                <a href="#" class="btn-view"><i class="fas fa-user-md"></i> Profile</a>
                                <a href="#" class="btn-view" style="margin-left: 10px;"><i class="fas fa-history"></i> History</a>
                            </td>
                        </tr>
                        <tr class="patient-row">
                            <td>HC-P-2026-8842</td>
                            <td><strong style="color: white;">Anjali Sharma</strong></td>
                            <td>28 / Female</td>
                            <td>Today</td>
                            <td>
                                <a href="#" class="btn-view"><i class="fas fa-user-md"></i> Profile</a>
                                <a href="#" class="btn-view" style="margin-left: 10px;"><i class="fas fa-history"></i> History</a>
                            </td>
                        </tr>
                        <tr class="patient-row">
                            <td>HC-P-2026-7215</td>
                            <td><strong style="color: white;">Rahul Kumar</strong></td>
                            <td>45 / Male</td>
                            <td>15 Oct 2025</td>
                            <td>
                                <a href="#" class="btn-view"><i class="fas fa-user-md"></i> Profile</a>
                                <a href="#" class="btn-view" style="margin-left: 10px;"><i class="fas fa-history"></i> History</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
