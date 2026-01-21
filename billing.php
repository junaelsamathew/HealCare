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

            <?php
            // Check for Active Admission
            $active_adm_q = "SELECT a.*, r.room_number, w.ward_name, w.ward_type, adm_reg.name as doctor_name
                             FROM admissions a
                             JOIN rooms r ON a.room_id = r.room_id
                             JOIN wards w ON r.ward_id = w.ward_id
                             JOIN users u_doc ON a.doctor_id = u_doc.user_id
                             JOIN registrations adm_reg ON u_doc.registration_id = adm_reg.registration_id
                             WHERE a.patient_id = $user_id AND a.status = 'Admitted' LIMIT 1";
            $active_adm_res = $conn->query($active_adm_q);
            if ($active_adm_res && $active_adm_res->num_rows > 0):
                $active_adm = $active_adm_res->fetch_assoc();
                $a_date = new DateTime($active_adm['admission_date']);
                $a_days = (new DateTime())->diff($a_date)->days;
                if ($a_days == 0) $a_days = 1;

                // Ward Rates (Match doctor_discharge.php)
                $a_rates = ['General' => 500, 'Semi-Private' => 1500, 'Private' => 3000, 'ICU' => 5000, 'Emergency' => 2000];
                $a_rate = $a_rates[$active_adm['ward_type']] ?? 1000;
                $a_room = $a_days * $a_rate;
                $a_doc = $a_days * 500;
                
                // Pending Services
                $p_serv_res = $conn->query("SELECT SUM(total_amount) as total FROM billing WHERE patient_id = $user_id AND payment_status = 'Pending'");
                $p_serv_total = ($p_serv_res) ? $p_serv_res->fetch_assoc()['total'] : 0;
                
                $running_total = $a_room + $a_doc + $p_serv_total;
            ?>
            <div class="content-section" style="background: linear-gradient(135deg, #1e293b, #0f172a); border: 1px solid #f59e0b; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span class="status-pending" style="font-size: 11px; text-transform: uppercase;"><i class="fas fa-bed"></i> Active Admission</span>
                        <h2 style="color: #fff; margin: 5px 0;">Current Stay Estimate: ₹<?php echo number_format($running_total, 2); ?></h2>
                        <p style="color: #94a3b8; font-size: 13px;">
                            <?php echo $active_adm['ward_name']; ?> - Room <?php echo $active_adm['room_number']; ?> | 
                            Admitted <?php echo $a_date->format('M d, Y'); ?> (<?php echo $a_days; ?> Days)
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <small style="color: #64748b; display: block; margin-bottom: 5px;">Final bill will be generated upon discharge</small>
                        <span style="color: #f59e0b; font-weight: 700; font-size: 14px;">Total Payable on Discharge</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                            SELECT b.*, a.department, r.name as doctor_name, adm.admission_id, adm.status as adm_status
                            FROM billing b
                            LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
                            LEFT JOIN users ud ON b.doctor_id = ud.user_id
                            LEFT JOIN registrations r ON ud.registration_id = r.registration_id
                            LEFT JOIN admissions adm ON (b.patient_id = adm.patient_id AND b.bill_date BETWEEN DATE(adm.admission_date) AND IFNULL(DATE(adm.discharge_date), CURDATE()))
                            WHERE b.patient_id = $user_id
                            ORDER BY b.bill_date DESC, b.bill_id DESC
                        ";
                        $bills_res = $conn->query($bills_sql);

                        if ($bills_res && $bills_res->num_rows > 0):
                            while ($bill = $bills_res->fetch_assoc()):
                                // Skip individual bills that were merged into an Inpatient Final bill
                                if ($bill['payment_mode'] == 'Merged' && $bill['payment_status'] == 'Paid') {
                                    continue;
                                }

                                $invoice_no = "INV-" . str_pad($bill['bill_id'], 4, '0', STR_PAD_LEFT);
                                
                                // Enhanced service description with icons
                                $bill_icon = 'fa-file-invoice';
                                $is_inpatient = ($bill['admission_id'] && ($bill['adm_status'] == 'Admitted' || strpos($bill['bill_type'], 'Inpatient') !== false));
                                
                                if ($bill['bill_type'] == 'Medical Services') {
                                    $service_desc = 'Combined Medical Services (Pharmacy + Lab)';
                                    $bill_icon = 'fa-briefcase-medical';
                                } elseif ($bill['bill_type'] == 'Pharmacy') {
                                    $service_desc = 'Pharmacy / Medicines';
                                    $bill_icon = 'fa-pills';
                                } elseif ($bill['bill_type'] == 'Lab Test' || strpos($bill['bill_type'], 'Lab Test:') === 0) {
                                    $service_desc = $bill['bill_type'];
                                    $bill_icon = 'fa-flask';
                                } elseif ($bill['bill_type'] == 'Consultation') {
                                    $service_desc = 'Consultation - ' . ($bill['department'] ?? 'General');
                                    $bill_icon = 'fa-stethoscope';
                                } elseif ($bill['bill_type'] == 'Canteen') {
                                    $service_desc = 'Canteen Order';
                                    $bill_icon = 'fa-utensils';
                                } elseif (strpos($bill['bill_type'], 'Inpatient') !== false) {
                                    $service_desc = 'Hospital Stay - Final Settlement';
                                    $bill_icon = 'fa-bed';
                                } else {
                                    $service_desc = $bill['bill_type'];
                                }
                                
                                $status_class = strtolower($bill['payment_status']);
                                $badges = "";
                                if ($is_inpatient && strpos($bill['bill_type'], 'Inpatient') === false) {
                                    $badges .= '<span class="badge" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b; font-size:10px; padding:2px 6px; border-radius:4px; margin-left:5px;">In-Stay</span>';
                                }
                        ?>
                        <tr>
                            <td>#<?php echo $invoice_no; ?></td>
                            <td><?php echo date('M d, Y', strtotime($bill['bill_date'])); ?></td>
                            <td>
                                <div style="display:flex; align-items:center;">
                                    <strong><i class="fas <?php echo $bill_icon; ?>" style="margin-right: 8px; color: #4fc3f7; width:20px; text-align:center;"></i><?php echo htmlspecialchars($service_desc); ?></strong>
                                    <?php echo $badges; ?>
                                </div>
                                <small style="color: #94a3b8;">Dr. <?php echo htmlspecialchars($bill['doctor_name'] ?? 'Hospital Staff'); ?></small>
                            </td>
                            <td>₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                            <td><span class="status-<?php echo $status_class; ?>"><?php echo $bill['payment_status']; ?></span></td>
                            <td>
                                <?php if($bill['payment_status'] == 'Pending'): ?>
                                    <a href="payment_gateway.php?bill_id=<?php echo $bill['bill_id']; ?>" style="display:inline-block; background: #00aeef; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;">Pay Now</a>
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
