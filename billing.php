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
                        <?php
                        $bills_sql = "
                            SELECT b.*, a.department, r.name as doctor_name
                            FROM billing b
                            LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
                            LEFT JOIN users ud ON b.doctor_id = ud.user_id
                            LEFT JOIN registrations r ON ud.registration_id = r.registration_id
                            WHERE b.patient_id = $user_id
                            ORDER BY b.bill_date DESC
                        ";
                        $bills_res = $conn->query($bills_sql);

                        if ($bills_res && $bills_res->num_rows > 0):
                            while ($bill = $bills_res->fetch_assoc()):
                                $invoice_no = "INV-" . str_pad($bill['bill_id'], 4, '0', STR_PAD_LEFT);
                                $service_desc = $bill['bill_type'] . " - " . ($bill['department'] ?? 'General');
                                $status_class = strtolower($bill['payment_status']);
                        ?>
                        <tr>
                            <td>#<?php echo $invoice_no; ?></td>
                            <td><?php echo date('M d, Y', strtotime($bill['bill_date'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($service_desc); ?></strong><br>
                                <small style="color: #94a3b8;">Dr. <?php echo htmlspecialchars($bill['doctor_name'] ?? 'Hospital Staff'); ?></small>
                            </td>
                            <td>$<?php echo number_format($bill['total_amount'], 2); ?></td>
                            <td><span class="status-<?php echo $status_class; ?>"><?php echo $bill['payment_status']; ?></span></td>
                            <td>
                                <?php if($bill['payment_status'] == 'Pending'): ?>
                                    <a href="payment_process.php?bill_id=<?php echo $bill['bill_id']; ?>" style="display:inline-block; background: #00aeef; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;">Pay Now</a>
                                <?php else: ?>
                                    <a href="generate_receipt_pdf.php?bill_id=<?php echo $bill['bill_id']; ?>" style="color: #4fc3f7; text-decoration: none; font-size: 13px;"><i class="fas fa-file-invoice"></i> Receipt</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #aaa;">No invoices found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
