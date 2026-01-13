<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$doctor_username = $_SESSION['username'];

if (!isset($_GET['id'])) {
    header("Location: doctor_patients.php");
    exit();
}

$patient_id = intval($_GET['id']);

// Fetch Patient Details
$query = "
    SELECT 
        u.user_id,
        r.name,
        r.email,
        r.phone,
        p.gender,
        p.date_of_birth,
        p.blood_group,
        p.address,
        p.medical_history,
        p.allergies,
        p.patient_code
    FROM users u
    JOIN registrations r ON u.registration_id = r.registration_id
    LEFT JOIN patient_profiles p ON u.user_id = p.user_id
    WHERE u.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    echo "Patient not found.";
    exit();
}

// Calculate Age
$age = 'N/A';
if (!empty($patient['date_of_birth'])) {
    $dob = new DateTime($patient['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y . ' Years';
}

// Fetch Latest Vitals
$vitals_query = "SELECT * FROM patient_vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1";
$stmt_v = $conn->prepare($vitals_query);
$stmt_v->bind_param("i", $patient_id);
$stmt_v->execute();
$vitals = $stmt_v->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .profile-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-item label {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .info-item span {
            color: #fff;
            font-size: 16px;
            font-weight: 500;
        }
        .section-title {
            color: #4fc3f7;
            font-size: 18px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }
        .metric-card {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }
        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }
        .metric-label {
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="doctor_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="doctor_patients.php" class="nav-link active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <a href="doctor_patients.php" style="color: #94a3b8; text-decoration: none; font-size: 13px;"><i class="fas fa-arrow-left"></i> Back to Patients</a>
                    <h1 style="margin-top: 10px;">Patient Profile</h1>
                </div>
            </div>

            <div class="profile-card">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                    <div style="width: 80px; height: 80px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; color: white;">
                        <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h2 style="color: white; margin: 0;"><?php echo htmlspecialchars($patient['name']); ?></h2>
                        <span style="color: #94a3b8; font-size: 14px;">ID: <?php echo htmlspecialchars($patient['patient_code'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="section-title">Personal Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Age</label>
                        <span><?php echo $age; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Gender</label>
                        <span><?php echo htmlspecialchars(ucfirst($patient['gender'] ?? 'N/A')); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Blood Group</label>
                        <span style="color: #ef4444; font-weight: 700;"><?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <span><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <span><?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <div class="section-title">Medical Overview</div>
                <div class="info-grid">
                    <div class="info-item" style="grid-column: span 2;">
                        <label>Medical History / Conditions</label>
                        <p style="color: #cbd5e1; line-height: 1.6; margin-top: 5px;">
                            <?php echo !empty($patient['medical_history']) ? nl2br(htmlspecialchars($patient['medical_history'])) : 'No recorded medical history.'; ?>
                        </p>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <label>Allergies</label>
                        <p style="color: #ef4444; line-height: 1.6; margin-top: 5px;">
                            <?php echo !empty($patient['allergies']) ? nl2br(htmlspecialchars($patient['allergies'])) : 'None reported.'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($vitals): ?>
            <div class="profile-card">
                <div class="section-title">Latest Vitals <small style="font-size: 12px; color: #94a3b8; font-weight: normal;">(Recorded on <?php echo date('d M Y, h:i A', strtotime($vitals['recorded_at'])); ?>)</small></div>
                <div class="info-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="metric-card">
                        <div class="metric-value" style="color: #3b82f6;"><?php echo $vitals['heart_rate'] ?? '--'; ?></div>
                        <div class="metric-label">Heart Rate (bpm)</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" style="color: #ef4444;"><?php echo ($vitals['blood_pressure_systolic'] && $vitals['blood_pressure_diastolic']) ? $vitals['blood_pressure_systolic'].'/'.$vitals['blood_pressure_diastolic'] : '--'; ?></div>
                        <div class="metric-label">Blood Pressure</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" style="color: #10b981;"><?php echo $vitals['temperature'] ?? '--'; ?></div>
                        <div class="metric-label">Temperature (°F)</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" style="color: #f59e0b;"><?php echo $vitals['oxygen_saturation'] ?? '--'; ?></div>
                        <div class="metric-label">SpO2 (%)</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
