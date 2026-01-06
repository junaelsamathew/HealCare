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
    <title>Prescriptions - HealCare</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .prescription-card {
            background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);
            padding: 25px; border-radius: 15px; margin-bottom: 20px;
        }
        .presc-header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .med-list { list-style: none; }
        .med-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed rgba(255,255,255,0.1); }
        .med-item:last-child { border-bottom: none; }
        .med-name { font-weight: 600; color: #4fc3f7; }
        .dosage { color: var(--text-gray); font-size: 13px; }
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
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link active"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header"><h1>Prescriptions</h1><p>Active and past medication lists</p></div>

            <div class="content-section" style="background: transparent; border: none; padding: 0;">
                
                <div class="prescription-card">
                    <div class="presc-header">
                        <div>
                            <h4 style="font-size: 18px;">Dr. Sarah Jenny</h4>
                            <p style="color: var(--text-gray); font-size: 13px;">Cardiology • Oct 15, 2025</p>
                        </div>
                        <a href="#" class="action-cancel" style="color: #4fc3f7;"><i class="fas fa-print"></i> Print</a>
                    </div>
                    <ul class="med-list">
                        <li class="med-item">
                            <div>
                                <span class="med-name">Atorvastatin 20mg</span>
                                <p class="dosage">1-0-1 | After Food</p>
                            </div>
                            <span style="color: var(--text-gray);">15 Days</span>
                        </li>
                        <li class="med-item">
                            <div>
                                <span class="med-name">Aspirin 75mg</span>
                                <p class="dosage">0-0-1 | Night</p>
                            </div>
                            <span style="color: var(--text-gray);">30 Days</span>
                        </li>
                    </ul>
                </div>

                <div class="prescription-card">
                    <div class="presc-header">
                        <div>
                            <h4 style="font-size: 18px;">Dr. Mark Stevens</h4>
                            <p style="color: var(--text-gray); font-size: 13px;">Neurology • Sep 05, 2025</p>
                        </div>
                        <a href="#" class="action-cancel" style="color: #4fc3f7;"><i class="fas fa-print"></i> Print</a>
                    </div>
                    <ul class="med-list">
                        <li class="med-item">
                            <div>
                                <span class="med-name">Gabapentin 300mg</span>
                                <p class="dosage">1-1-1 | Daily</p>
                            </div>
                            <span style="color: var(--text-gray);">10 Days</span>
                        </li>
                    </ul>
                </div>

            </div>
        </main>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
