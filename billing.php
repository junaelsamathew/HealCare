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
    <title>Billing & Payments - HealCare</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .bill-table { width: 100%; border-collapse: collapse; }
        .bill-table th { text-align: left; padding: 15px; border-bottom: 1px solid var(--border-color); color: var(--text-gray); font-size: 13px; }
        .bill-table td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        .status-paid { color: #10b981; font-weight: 600; }
        .status-pending { color: #f59e0b; font-weight: 600; }
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
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link active"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header"><h1>Billing & Payments</h1><p>View your invoices and payment history</p></div>

            <div class="content-section">
                <div class="section-head"><h3>Recent Invoices</h3></div>
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Date</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#INV-9901</td>
                            <td>Oct 15, 2025</td>
                            <td>Consultation - Cardiology</td>
                            <td>$150.00</td>
                            <td><span class="status-paid">Paid</span></td>
                            <td><a href="#" style="color: #4fc3f7; text-decoration: none;"><i class="fas fa-file-invoice"></i> PDF</a></td>
                        </tr>
                        <tr>
                            <td>#INV-9844</td>
                            <td>Sep 22, 2025</td>
                            <td>Laboratory - Blood Test</td>
                            <td>$45.00</td>
                            <td><span class="status-paid">Paid</span></td>
                            <td><a href="#" style="color: #4fc3f7; text-decoration: none;"><i class="fas fa-file-invoice"></i> PDF</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
