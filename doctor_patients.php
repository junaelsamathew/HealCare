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

$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
if (stripos($doctor_name, 'Dr.') === false && stripos($doctor_name, 'Doctor') === false) {
    $doctor_name = "Dr. " . $doctor_name;
}
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
<<<<<<< HEAD
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
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">EMERGENCY</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">(+91) 953 904 5609</span>
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
=======
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
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="doctor_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="doctor_patients.php" class="nav-link active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
<<<<<<< HEAD
                <a href="create_appointment.php" class="nav-link"><i class="fas fa-plus-circle"></i> Create Appointment</a>
=======
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
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
                        <?php
                        // Fetch patients who have had appointments with this doctor
                        $p_query = "
                            SELECT 
                                u.user_id as patient_user_id,
                                r.name as patient_name,
                                r.email as patient_email,
                                p.gender,
                                p.date_of_birth,
                                MAX(a.appointment_date) as last_appointment
                            FROM appointments a
                            JOIN users u ON a.patient_id = u.user_id
                            JOIN registrations r ON u.registration_id = r.registration_id
                            LEFT JOIN patient_profiles p ON u.user_id = p.user_id
                            WHERE a.doctor_id = ?
                            GROUP BY u.user_id
                            ORDER BY last_appointment DESC
                        ";
                        
                        $stmt = $conn->prepare($p_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                // Calculate Age
                                $age = 'N/A';
                                if (!empty($row['date_of_birth'])) {
                                    $dob = new DateTime($row['date_of_birth']);
                                    $now = new DateTime();
                                    $diff = $now->diff($dob);
                                    $age = $diff->y;
                                }
                                
                                // Format Gender
                                $gender = !empty($row['gender']) ? ucfirst($row['gender']) : 'Unknown';
                                
                                // Format Last Appointment
                                $last_appt = date('d M Y', strtotime($row['last_appointment']));
                                if (date('Y-m-d') == date('Y-m-d', strtotime($row['last_appointment']))) {
                                    $last_appt = 'Today';
                                }
                                
                                // Generate Display ID (Mocking the format requested: HC-P-YEAR-ID)
                                $display_id = 'HC-P-' . date('Y') . '-' . str_pad($row['patient_user_id'], 4, '0', STR_PAD_LEFT);
                        ?>
                        <tr class="patient-row">
                            <td><?php echo $display_id; ?></td>
                            <td>
                                <strong style="color: white;"><?php echo htmlspecialchars($row['patient_name']); ?></strong>
                                <br><small style="color: #64748b;"><?php echo htmlspecialchars($row['patient_email']); ?></small>
                            </td>
                            <td><?php echo $age; ?> / <?php echo $gender; ?></td>
                            <td><?php echo $last_appt; ?></td>
                            <td>
                                <a href="doctor_patient_profile.php?id=<?php echo $row['patient_user_id']; ?>" class="btn-view"><i class="fas fa-user-md"></i> Profile</a>
                                <a href="doctor_patient_history.php?id=<?php echo $row['patient_user_id']; ?>" class="btn-view" style="margin-left: 10px;"><i class="fas fa-history"></i> History</a>
                            </td>
                        </tr>
                        <?php endwhile; 
                        else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;">
                                No assigned patients found. Patients will appear here once they book an appointment with you.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div></body>
</html>
