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

$doctor_name = "Dr. " . htmlspecialchars($_SESSION['username']); // Fallback, will be overwritten by logic below if needed.
$doctor_full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
if (stripos($doctor_full_name, 'Dr.') === false && stripos($doctor_full_name, 'Doctor') === false) {
    $doctor_name = "Dr. " . $doctor_full_name;
} else {
    $doctor_name = $doctor_full_name;
}

// Fetch Assigned Patients for Dropdown
$dropdown_patients = [];
$dp_query = "
    SELECT DISTINCT u.user_id, r.name, pp.patient_code
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    JOIN registrations r ON u.registration_id = r.registration_id
    LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
    WHERE a.doctor_id = ?
    ORDER BY r.name ASC
";
$stmt_dp = $conn->prepare($dp_query);
$stmt_dp->bind_param("i", $user_id);
$stmt_dp->execute();
$res_dp = $stmt_dp->get_result();
while ($row = $res_dp->fetch_assoc()) {
    $dropdown_patients[] = $row;
}

// Fetch Available Lab Tests (from Packages + History + Defaults)
$available_tests = [];

// 1. From Health Packages
$pkg_q = $conn->query("SELECT included_tests FROM health_packages");
while($pkg = $pkg_q->fetch_assoc()) {
    if(!empty($pkg['included_tests'])) {
        $parts = explode(',', $pkg['included_tests']);
        foreach($parts as $p) {
            $t = trim($p);
            if(!empty($t) && !in_array($t, $available_tests)) {
                $available_tests[] = $t;
            }
        }
    }
}

// 2. From Past Lab Tests (History)
$hist_q = $conn->query("SELECT DISTINCT test_name FROM lab_tests");
while($hist = $hist_q->fetch_assoc()) {
    $t = trim($hist['test_name']);
    if(!empty($t) && !in_array($t, $available_tests)) {
        $available_tests[] = $t;
    }
}

// 3. Robust Defaults (aligned with Lab Staff types)
$defaults = [
    'Blood Test (CBC)', 'Lipid Profile', 'Thyroid Profile', 'Blood Sugar (Fasting)', 'Blood Sugar (PP)', 
    'Urine Culture', 'Kidney Function Test', 'Liver Function Test', 
    'X-Ray (Chest)', 'X-Ray (Fracture)', 'MRI Scan', 'CT Scan', 
    'Ultrasound (Abdominal)', 'Doppler Scan', 
    'ECG', 'Hearing Test', 'Eye Test'
];
foreach($defaults as $d) {
    if(!in_array($d, $available_tests)) {
        $available_tests[] = $d;
    }
}
sort($available_tests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Orders - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .order-form {
            background: rgba(30, 41, 59, 0.4);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .order-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .form-label { display: block; margin-bottom: 8px; font-size: 13px; color: #94a3b8; font-weight: 600; }
        .form-input { 
            width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); 
            padding: 12px; border-radius: 10px; color: #fff; outline: none;
        }
        .form-input option {
            background: #1e293b;
            color: #fff;
        }
        .order-card {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .priority-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .urgent { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .normal { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-flask"></i></div>
                <div class="info-details"><span class="info-label">DATE</span><span class="info-value"><?php echo date('d M Y'); ?></span></div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-file-medical"></i></div>
                <div class="info-details"><span class="info-label">LAB STATUS</span><span class="info-value">2 Done / 1 Pending</span></div>
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
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_prescriptions.php" class="nav-link"><i class="fas fa-file-prescription"></i> Prescriptions</a>
                <a href="doctor_lab_orders.php" class="nav-link active"><i class="fas fa-flask"></i> Lab Orders</a>
                <a href="doctor_leave.php" class="nav-link"><i class="fas fa-calendar-minus"></i> Apply Leave</a>
                <a href="doctor_settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Lab Test Requests</h1>
                <p>Order pathological or radiological tests and track their status.</p>
            </div>

            <div class="order-form">
                <h2 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Create Lab Test Request</h2>
                <form>
                    <div class="order-grid">
                        <div>
                            <label class="form-label">Select Patient</label>
                            <select class="form-input" name="patient_id" required>
                                <option value="">-- Select Patient --</option>
                                <?php foreach($dropdown_patients as $p): ?>
                                    <option value="<?php echo $p['user_id']; ?>">
                                        <?php echo htmlspecialchars($p['name']) . ' (' . ($p['patient_code'] ?? 'ID: '.$p['user_id']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Test Type</label>
                            <select class="form-input" name="test_type" required>
                                <option value="">-- Select Test --</option>
                                <?php foreach($available_tests as $test): ?>
                                    <option value="<?php echo htmlspecialchars($test); ?>"><?php echo htmlspecialchars($test); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Priority</label>
                            <select class="form-input">
                                <option value="normal">Normal</option>
                                <option value="urgent">Urgent / STAT</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label class="form-label">Reason / Clinical Details</label>
                        <textarea class="form-input" rows="2" placeholder="e.g. For pre-surgical screening..."></textarea>
                    </div>
                    <button type="submit" style="background: #1e40af; color: #fff; width: 100%; padding: 12px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Send Request to Lab</button>
                </form>
            </div>

            <div class="content-section">
                <h2 style="color: white; margin-bottom: 20px;">Recent Lab Orders</h2>
                <div class="order-card">
                    <div>
                        <h4 style="color: white; margin-bottom: 5px;">CBC & Liver Profile</h4>
                        <p style="font-size: 13px; color: #94a3b8;">Patient: Rahul Kumar • Requested: Today, 10:15 AM</p>
                    </div>
                    <div style="text-align: right;">
                        <span class="priority-badge normal">Normal</span><br>
                        <span style="font-size: 11px; color: #fbbf24; font-weight: 600;">Status: Pending</span>
                    </div>
                </div>

                <div class="order-card">
                    <div>
                        <h4 style="color: white; margin-bottom: 5px;">Chest X-Ray (AP/Lateral)</h4>
                        <p style="font-size: 13px; color: #94a3b8;">Patient: Anjali Sharma • Requested: Yesterday</p>
                    </div>
                    <div style="text-align: right; display: flex; align-items: center; gap: 20px;">
                        <div style="text-align: right;">
                            <span class="priority-badge urgent">Urgent</span><br>
                            <span style="font-size: 11px; color: #10b981; font-weight: 600;">Status: Completed</span>
                        </div>
                        <a href="#" class="btn-view"><i class="fas fa-eye"></i> View Results</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
