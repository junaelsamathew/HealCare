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

$doctor_name = "Dr. " . htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);

// 1. Handle Leave Submission
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_leave'])) {
    $type = $_POST['leave_type'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO doctor_leaves (doctor_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $type, $start, $end, $reason);
    if ($stmt->execute()) {
        $msg = "success";
    } else {
        $msg = "error";
    }
}

// 2. Fetch Leave History
$leaves = [];
$stmt = $conn->prepare("SELECT * FROM doctor_leaves WHERE doctor_id = ? ORDER BY applied_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $leaves[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Leave - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .leave-form {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 40px;
        }
        .leave-history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .leave-history-table th { text-align: left; padding: 15px 20px; color: #94a3b8; font-size: 13px; font-weight: 500; }
        .leave-row { background: rgba(255, 255, 255, 0.02); }
        .leave-row td { padding: 20px; border-top: 1px solid rgba(255, 255, 255, 0.05); }
        .leave-row td:first-child { border-radius: 12px 0 0 12px; }
        .leave-row td:last-child { border-radius: 0 12px 12px 0; }
        
        .status-pill {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
        .status-approved { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-calendar-minus"></i></div>
                <div class="info-details"><span class="info-label">ANNUAL LEAVE</span><span class="info-value">12 Days Rem.</span></div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-history"></i></div>
                <div class="info-details"><span class="info-label">HISTORY</span><span class="info-value">3 Total Applications</span></div>
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
                <a href="doctor_lab_orders.php" class="nav-link"><i class="fas fa-flask"></i> Lab Orders</a>
                <a href="doctor_leave.php" class="nav-link active"><i class="fas fa-calendar-minus"></i> Apply Leave</a>
                <a href="doctor_settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Leave Management</h1>
                <p>Request absences and track your leave application status.</p>
            </div>

            <div class="leave-form">
                <h2 style="color: white; margin-bottom: 25px; font-size: 1.2rem;">Apply for New Leave</h2>
                
                <?php if ($msg == 'success'): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <i class="fas fa-check-circle"></i> Leave request submitted successfully!
                    </div>
                <?php elseif ($msg == 'error'): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.2);">
                        <i class="fas fa-exclamation-circle"></i> There was an error submitting your request.
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="apply_leave" value="1">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-size: 13px; color: #94a3b8; font-weight: 600;">Leave Type</label>
                            <select name="leave_type" required style="width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 10px; color: #fff;">
                                <option>Casual Leave</option>
                                <option>Sick Leave</option>
                                <option>Emergency Leave</option>
                                <option>Academic/Training</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-size: 13px; color: #94a3b8; font-weight: 600;">Start Date</label>
                            <input type="date" name="start_date" required style="width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 10px; color: #fff;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-size: 13px; color: #94a3b8; font-weight: 600;">End Date</label>
                            <input type="date" name="end_date" required style="width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 10px; color: #fff;">
                        </div>
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 8px; font-size: 13px; color: #94a3b8; font-weight: 600;">Reason for Leave</label>
                        <textarea name="reason" rows="3" required style="width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 10px; color: #fff;" placeholder="Provide a brief explanation..."></textarea>
                    </div>
                    <button type="submit" style="background: #2563eb; color: #fff; width: 100%; padding: 14px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Submit Leave Application</button>
                </form>
            </div>

            <div class="content-section">
                <h2 style="color: white; margin-bottom: 20px;">Your Leave History</h2>
                <table class="leave-history-table">
                    <thead>
                        <tr>
                            <th>Dates</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaves)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 40px;">No leave applications found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($leaves as $l): ?>
                                <tr class="leave-row">
                                    <td><?php echo date('M d', strtotime($l['start_date'])); ?> - <?php echo date('M d, Y', strtotime($l['end_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($l['leave_type']); ?></td>
                                    <td><?php echo htmlspecialchars($l['reason']); ?></td>
                                    <td><span class="status-pill status-<?php echo strtolower($l['status']); ?>"><?php echo $l['status']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
