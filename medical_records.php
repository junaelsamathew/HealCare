<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - HealCare</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .record-card {
            background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);
            padding: 20px; border-radius: 12px; margin-bottom: 15px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .btn-download {
            background: rgba(59, 130, 246, 0.1); color: var(--primary-blue);
            padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;
        }
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
                <a href="book_appointment.php" class="nav-link">Book Appointment</a>
                <a href="my_appointments.php" class="nav-link">My Appointments</a>
                <a href="medical_records.php" class="nav-link active"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header"><h1>Medical Records</h1><p>Access your diagnosis reports and lab results</p></div>

            <div class="content-section">
                <div class="section-head"><h3>Recent Diagnosis Reports</h3></div>
                
                <div class="record-card">
                    <div>
                        <h4 style="margin-bottom: 5px;">General Checkup Report</h4>
                        <p style="color: var(--text-gray); font-size: 13px;">Follow-up visit • Oct 10, 2025</p>
                    </div>
                    <a href="#" class="btn-download"><i class="fas fa-download"></i> PDF</a>
                </div>

                <div class="record-card">
                    <div>
                        <h4 style="margin-bottom: 5px;">Blood Test Analysis</h4>
                        <p style="color: var(--text-gray); font-size: 13px;">Lab Result • Sep 22, 2025</p>
                    </div>
                    <a href="#" class="btn-download"><i class="fas fa-download"></i> PDF</a>
                </div>
            </div>

            <div class="content-section" style="margin-top: 30px;">
                <div class="section-head"><h3>Radiology / Scans</h3></div>
                <div class="empty-state"><p>No scan reports available yet.</p></div>
            </div>
        </main>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
