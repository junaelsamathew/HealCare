<?php
session_start();
include 'includes/db_connect.php';

// Check if logged in
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user_role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Identify specific staff type for staff role
$staff_type = '';
if ($role == 'staff') {
    $c = $conn->query("SELECT 'nurse' as t FROM nurses WHERE user_id=$user_id UNION SELECT 'lab_staff' FROM lab_staff WHERE user_id=$user_id UNION SELECT 'pharmacist' FROM pharmacists WHERE user_id=$user_id UNION SELECT 'receptionist' FROM receptionists WHERE user_id=$user_id UNION SELECT 'canteen_staff' FROM canteen_staff WHERE user_id=$user_id");
    if($c && $r = $c->fetch_assoc()) $staff_type = $r['t'];
}

// Map roles for RBAC checks
$effective_role = $role;
if ($role == 'staff' && $staff_type) $effective_role = $staff_type;

// Determine Dashboard URL based on role
$dashboard_url = 'index.php';
switch($effective_role) {
    case 'admin': $dashboard_url = 'admin_dashboard.php'; break;
    case 'doctor': $dashboard_url = 'doctor_dashboard.php'; break;
    case 'nurse': $dashboard_url = 'staff_nurse_dashboard.php'; break;
    case 'lab_staff': $dashboard_url = 'staff_lab_staff_dashboard.php'; break;
    case 'pharmacist': $dashboard_url = 'staff_pharmacist_dashboard.php'; break;
    case 'receptionist': $dashboard_url = 'staff_receptionist_dashboard.php'; break;
    case 'canteen_staff': $dashboard_url = 'staff_canteen_staff_dashboard.php?section=reports'; break;
    default: $dashboard_url = 'staff_dashboard.php';
}

// Define Report Access Matrix
$report_access = [
    'overall_revenue' => ['admin'],
    'consultation_revenue' => ['admin', 'doctor'],
    'dept_revenue' => ['admin'],
    'appointment_report' => ['admin', 'doctor', 'receptionist'],
    'lab_revenue' => ['admin', 'lab_staff'],
    'pharmacy_sales' => ['admin', 'pharmacist'],
    'payment_mode' => ['admin'],
    'patient_visit' => ['admin', 'doctor'],
    'doctor_performance' => ['admin', 'doctor'],
    'canteen_daily_sales' => ['admin', 'canteen_staff'],
    'canteen_item_sales' => ['admin', 'canteen_staff'],
    'canteen_payments' => ['admin', 'canteen_staff'],
    'canteen_stock' => ['admin', 'canteen_staff'],
    'canteen_revenue' => ['admin'], // Legacy fallback
    
    // Receptionist Reports
    'receptionist_appointment' => ['admin', 'receptionist'],
    'receptionist_registration' => ['admin', 'receptionist'],
    'receptionist_checkin' => ['admin', 'receptionist'],

    // Pharmacist Reports
    'pharmacist_sales' => ['admin', 'pharmacist'],
    'pharmacist_stock' => ['admin', 'pharmacist'],
    'pharmacist_expiry' => ['admin', 'pharmacist'],

    // Nurse Reports
    'nurse_vitals' => ['admin', 'nurse'],
    'nurse_care' => ['admin', 'nurse']
];

function can_view($report_id, $role) {
    global $report_access;
    return in_array($role, $report_access[$report_id] ?? []);
}

$view = $_GET['view'] ?? 'reports';
$type = $_GET['type'] ?? '';
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-d');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Console - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --primary: #3b82f6; 
            --primary-glow: rgba(59, 130, 246, 0.5);
            --bg: #020617; 
            --sidebar: #0f172a;
            --card: #0f172a; 
            --text: #f8fafc; 
            --text-dim: #94a3b8; 
            --border: rgba(255,255,255,0.08); 
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: var(--bg); color: var(--text); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 30px 0; }
        .sidebar-logo { padding: 0 30px 40px; }
        .sidebar-logo h1 { font-size: 24px; font-weight: 800; letter-spacing: -1px; }
        .sidebar-nav { flex: 1; }
        .nav-link { 
            display: flex; align-items: center; gap: 15px; padding: 16px 30px; 
            color: var(--text-dim); text-decoration: none; font-size: 14px; font-weight: 500;
            transition: 0.3s; border-left: 4px solid transparent;
        }
        .nav-link:hover { background: rgba(255,255,255,0.03); color: #fff; }
        .nav-link.active { background: rgba(59, 130, 246, 0.1); color: var(--primary); border-left-color: var(--primary); }
        .nav-link i { font-size: 18px; width: 20px; text-align: center; }

        /* Main Content */
        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .top-bar { height: 80px; padding: 0 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top:0; background: rgba(2,6,23,0.8); backdrop-filter: blur(10px); z-index: 100; }
        .content { padding: 40px; }

        .btn { padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 8px; transition: 0.3s; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 14px var(--primary-glow); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .card { background: var(--card); border-radius: 20px; border: 1px solid var(--border); padding: 30px; }
        .report-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .report-card { background: var(--card); border: 1px solid var(--border); padding: 25px; border-radius: 20px; cursor: pointer; transition: 0.3s; position: relative; overflow: hidden; }
        .report-card:hover { border-color: var(--primary); transform: translateY(-5px); background: rgba(59, 130, 246, 0.03); }
        .report-card i { font-size: 24px; color: var(--primary); margin-bottom: 20px; display: block; }
        .report-card h3 { font-size: 17px; margin-bottom: 10px; }
        .report-card p { font-size: 13px; color: var(--text-dim); line-height: 1.6; }

        .filters { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; background: rgba(255,255,255,0.02); padding: 20px; border-radius: 16px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-size: 11px; font-weight: 700; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; }
        .filter-group input, .filter-group select { background: #020617; border: 1px solid var(--border); padding: 12px 16px; border-radius: 10px; color: #fff; font-size: 14px; outline: none; transition: 0.3s; }
        .filter-group input:focus { border-color: var(--primary); }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 18px; font-size: 11px; text-transform: uppercase; color: var(--text-dim); border-bottom: 1px solid var(--border); font-weight: 700; letter-spacing: 1px; }
        td { padding: 18px; font-size: 14px; border-bottom: 1px solid var(--border); color: #cbd5e1; }
        tr:hover td { background: rgba(255,255,255,0.01); }

        .analytics-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
        .chart-card { background: var(--card); border-radius: 24px; padding: 30px; border: 1px solid var(--border); }
        .chart-card h3 { margin-bottom: 25px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        
        .upload-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: var(--sidebar); width: 500px; padding: 40px; border-radius: 24px; border: 1px solid var(--border); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; background: rgba(59, 130, 246, 0.1); color: var(--primary); text-transform: uppercase; }

        @media print {
            .sidebar, .top-bar, .filters, .btn-group { display: none !important; }
            body { background: white; color: black; }
            .main { overflow: visible; height: auto; display: block; }
            .content { padding: 0; }
            .card { border: none; padding: 0; }
            table { color: black !important; width: 100%; }
            th, td { border-bottom: 1px solid #ddd !important; color: black !important; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-logo">
            <h1>+ HEALCARE</h1>
        </div>
        <nav class="sidebar-nav">
            <a href="?view=reports" class="nav-link <?php echo $view == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i> Functional Reports
            </a>

            <?php if ($role == 'admin'): ?>
            <a href="?view=repository" class="nav-link <?php echo $view == 'repository' ? 'active' : ''; ?>">
                <i class="fas fa-folder-open"></i> Document Repository
            </a>
            <?php endif; ?>
        </nav>
        <div style="padding: 20px;">
            <a href="<?php echo $dashboard_url; ?>" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </aside>

    <main class="main">
        <header class="top-bar">
            <h2>Management Console</h2>
            <div style="display: flex; gap: 20px; align-items: center;">
                <span class="badge"><?php echo $effective_role; ?> Access</span>
                <button class="btn btn-primary" onclick="openReportModal('<?php echo $type; ?>')">
                    <i class="fas fa-upload"></i> Upload Report
                </button>
            </div>
        </header>

        <div class="content">
            <?php if ($view == 'reports'): ?>
                <?php if (!$type): ?>
                    <div style="margin-bottom: 40px;">
                        <h2 style="font-size: 28px; margin-bottom: 10px;">Operational Intelligence</h2>
                        <p style="color:var(--text-dim);">Access role-specific financial and operational data.</p>
                    </div>
                    <div class="report-grid">
                        <?php
                        $reports = [
                            'overall_revenue' => ['icon' => 'fa-coins', 'title' => 'Overall Revenue', 'desc' => 'Total hospital income across all departments.'],
                            'consultation_revenue' => ['icon' => 'fa-user-md', 'title' => 'Consultation Revenue', 'desc' => 'Income from doctor visits and consults.'],
                            'dept_revenue' => ['icon' => 'fa-hospital-user', 'title' => 'Department Revenue', 'desc' => 'Revenue split by medical department.'],
                            'appointment_report' => ['icon' => 'fa-calendar-check', 'title' => 'Appointment Report', 'desc' => 'Scheduling success and cancellation metrics.'],
                            'lab_revenue' => ['icon' => 'fa-vials', 'title' => 'Laboratory Revenue', 'desc' => 'Insights into diagnostic service earnings.'],
                            'pharmacy_sales' => ['icon' => 'fa-pills', 'title' => 'Pharmacy Sales', 'desc' => 'Medication sales and inventory financial report.'],
                            'payment_mode' => ['icon' => 'fa-credit-card', 'title' => 'Payment Mode Report', 'desc' => 'Breakdown by Cash, Card, and Insurance.'],
                            'patient_visit' => ['icon' => 'fa-users', 'title' => 'Patient Visit Report', 'desc' => 'Demographic trends and daily visit volume.'],
                            'doctor_performance' => ['icon' => 'fa-chart-line', 'title' => 'Doctor Performance', 'desc' => 'Consultation counts and performance stats.'],
                            'canteen_daily_sales' => ['icon' => 'fa-utensils', 'title' => 'Daily Sales Report', 'desc' => 'Daily food sales tracking.'],
                            'canteen_item_sales' => ['icon' => 'fa-list-ul', 'title' => 'Item-Wise Sales', 'desc' => 'Popular items and menu performance.'],
                            'canteen_payments' => ['icon' => 'fa-money-bill-wave', 'title' => 'Payment Collection', 'desc' => 'Cash vs UPI revenue analysis.'],
                            'canteen_stock' => ['icon' => 'fa-boxes', 'title' => 'Stock Usage Report', 'desc' => 'Inventory usage and wastage.'],
                            'canteen_revenue' => ['icon' => 'fa-hamburger', 'title' => 'Canteen Revenue', 'desc' => 'Earnings from food services and canteen orders.'],

                            // Receptionist
                            'receptionist_appointment' => ['icon' => 'fa-calendar-check', 'title' => 'Appointment Booking', 'desc' => 'Scheduled appointments report.'],
                            'receptionist_registration' => ['icon' => 'fa-user-plus', 'title' => 'Patient Registration', 'desc' => 'New patient registrations.'],
                            'receptionist_checkin' => ['icon' => 'fa-door-open', 'title' => 'Daily Check-In/Out', 'desc' => 'Patient arrival and departure logs.'],

                            // Pharmacist
                            'pharmacist_sales' => ['icon' => 'fa-receipt', 'title' => 'Medicine Sales', 'desc' => 'Daily medicine sales records.'],
                            'pharmacist_stock' => ['icon' => 'fa-cubes', 'title' => 'Stock Usage', 'desc' => 'Stock remaining and usage statistics.'],
                            'pharmacist_expiry' => ['icon' => 'fa-hourglass-end', 'title' => 'Expiry Alerts', 'desc' => 'Medicines nearing expiration.'],

                            // Nurse
                            'nurse_vitals' => ['icon' => 'fa-heartbeat', 'title' => 'Vital Signs', 'desc' => 'Patient vitals monitoring logs.'],
                            'nurse_care' => ['icon' => 'fa-user-nurse', 'title' => 'Patient Care/Duty', 'desc' => 'Nursing care and duty reports.']
                        ];
                        foreach ($reports as $rid => $rdata):
                            if (can_view($rid, $effective_role)): ?>
                            <div class="report-card" onclick="location.href='?view=reports&type=<?php echo $rid; ?>'">
                                <i class="fas <?php echo $rdata['icon']; ?>"></i>
                                <h3><?php echo $rdata['title']; ?></h3>
                                <p><?php echo $rdata['desc']; ?></p>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                        <div>
                            <h2 style="font-size: 24px;"><?php echo ucwords(str_replace('_', ' ', $type)); ?></h2>
                            <p style="color:var(--text-dim);">Generating results for <?php echo date('M d', strtotime($start_date)).' - '.date('M d, Y', strtotime($end_date)); ?></p>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print PDF</button>
                            <button class="btn btn-outline" onclick="exportTableToExcel('reportTable')"><i class="fas fa-file-excel"></i> Export Excel</button>
                        </div>
                    </div>

                    <div class="filters" style="align-items: flex-end; flex-wrap: wrap;">
                        <form method="POST" id="dateForm" style="display:contents;">
                            <div class="filter-group">
                                <label>Search Report</label>
                                <div style="position:relative;">
                                    <i class="fas fa-search" style="position:absolute; left:12px; top:14px; color:var(--text-dim); font-size:12px;"></i>
                                    <input type="text" id="tableSearch" placeholder="Search by name, ID, status..." style="padding-left: 35px; width: 250px;">
                                </div>
                            </div>
                            <div class="filter-group">
                                <label>Date Range</label>
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" class="btn btn-outline" onclick="setDateRange('today')" style="padding: 10px 15px; font-size: 12px;">TD</button>
                                    <button type="button" class="btn btn-outline" onclick="setDateRange('month')" style="padding: 10px 15px; font-size: 12px;">MO</button>
                                    <button type="button" class="btn btn-outline" onclick="setDateRange('year')" style="padding: 10px 15px; font-size: 12px;">YR</button>
                                </div>
                            </div>
                            <div class="filter-group">
                                <label>From</label>
                                <input type="date" name="start_date" id="startDate" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="filter-group">
                                <label>To</label>
                                <input type="date" name="end_date" id="endDate" value="<?php echo $end_date; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary" style="height: 42px;"><i class="fas fa-sync-alt"></i></button>
                        </form>
                    </div>

                    <div class="card">
                        <?php
                        // (SQL logic from previous implementation remains here, slightly cleaned)
                        $headers = []; $query = "";
                        switch($type) {
                            case 'overall_revenue':
                                $headers = ['Date', 'Bill ID', 'Type', 'Amount', 'Mode', 'Status'];
                                $query = "SELECT bill_date, bill_id, bill_type, total_amount, payment_mode, payment_status FROM billing WHERE bill_date BETWEEN '$start_date' AND '$end_date' AND payment_status = 'Paid' ORDER BY bill_date DESC";
                                break;
                            case 'consultation_revenue':
                                $headers = ['Date', 'Token', 'Doctor', 'Patient', 'Fee', 'Status'];
                                $doc_filter = ($effective_role == 'doctor') ? "AND a.doctor_id = $user_id" : "";
                                $query = "SELECT a.appointment_date, a.queue_number, d.specialization, r.name as patient_name, a.consultation_fee, a.status 
                                          FROM appointments a 
                                          JOIN users u ON a.doctor_id = u.user_id 
                                          JOIN doctors d ON u.user_id = d.user_id
                                          JOIN users p ON a.patient_id = p.user_id
                                          JOIN registrations r ON p.registration_id = r.registration_id
                                          WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date' AND a.status = 'Completed' $doc_filter";
                                break;
                            case 'lab_revenue':
                                $headers = ['Date', 'Test ID', 'Test Name', 'Type', 'Patient', 'Status'];
                                $query = "SELECT l.created_at, l.labtest_id, l.test_name, l.test_type, r.name, l.status 
                                          FROM lab_tests l 
                                          JOIN users u ON l.patient_id = u.user_id
                                          JOIN registrations r ON u.registration_id = r.registration_id
                                          WHERE DATE(l.created_at) BETWEEN '$start_date' AND '$end_date'
                                          ORDER BY l.created_at DESC";
                                break;
                            case 'appointment_report':
                            case 'receptionist_appointment':
                                $headers = ['Date', 'Time', 'Specialist', 'Patient', 'Type', 'Status'];
                                $doc_filter = ($effective_role == 'doctor') ? "AND a.doctor_id = (SELECT doctor_id FROM doctors WHERE user_id = $user_id)" : "";
                                $query = "SELECT a.appointment_date, a.appointment_time, r.name as doctor, r2.name as patient, a.appointment_type, a.status 
                                          FROM appointments a 
                                          JOIN users u ON a.doctor_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id
                                          JOIN users u2 ON a.patient_id = u2.user_id JOIN registrations r2 ON u2.registration_id = r2.registration_id
                                          WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date' $doc_filter";
                                break;
                            case 'canteen_revenue':
                                $headers = ['Order Date', 'Order ID', 'Category', 'Total', 'Status'];
                                $query = "SELECT order_date, order_id, delivery_location, total_amount, order_status FROM canteen_orders WHERE order_date BETWEEN '$start_date' AND '$end_date' AND order_status = 'Delivered'";
                                break;
                            case 'pharmacy_sales':
                            case 'pharmacist_sales':
                                 $headers = ['Date', 'Bill ID', 'Price', 'Tax (5%)', 'Total', 'Patient'];
                                 $query = "SELECT bill_date, bill_id, total_amount*0.95 as net, total_amount*0.05 as tax, total_amount, patient_id FROM billing WHERE bill_type = 'Pharmacy' AND bill_date BETWEEN '$start_date' AND '$end_date'";
                                 break;
                            case 'payment_mode':
                                 $headers = ['Payment Mode', 'Transaction Count', 'Total Revenue'];
                                 $query = "SELECT payment_mode, COUNT(bill_id), SUM(total_amount) FROM billing WHERE payment_status = 'Paid' AND bill_date BETWEEN '$start_date' AND '$end_date' GROUP BY payment_mode";
                                 break;
                            case 'patient_visit':
                                 $headers = ['Visit Date', 'New Patients', 'Relapses/Followups', 'Total Visits'];
                                 $query = "SELECT appointment_date, SUM(CASE WHEN appointment_type='General' THEN 1 ELSE 0 END), SUM(CASE WHEN appointment_type='Emergency' THEN 1 ELSE 0 END), COUNT(*) 
                                           FROM appointments WHERE appointment_date BETWEEN '$start_date' AND '$end_date' GROUP BY appointment_date";
                                 break;
                            case 'doctor_performance':
                                 $headers = ['Doctor ID', 'Name', 'Department', 'Consultations', 'Revenue Generated'];
                                 $doc_filter = ($effective_role == 'doctor') ? "AND d.user_id = $user_id" : "";
                                 $query = "SELECT d.doctor_id, rg.name, d.specialization, COUNT(a.appointment_id) as total_c, SUM(a.consultation_fee) as rev
                                           FROM doctors d
                                           JOIN users u ON d.user_id = u.user_id
                                           JOIN registrations rg ON u.registration_id = rg.registration_id
                                           LEFT JOIN appointments a ON d.doctor_id = a.doctor_id AND a.status = 'Completed'
                                           WHERE (a.appointment_date IS NULL OR a.appointment_date BETWEEN '$start_date' AND '$end_date') $doc_filter
                                           GROUP BY d.doctor_id";
                                 break;
                            case 'dept_revenue':
                                 $headers = ['Department', 'Services Count', 'Revenue'];
                                 $query = "SELECT specialization, COUNT(appointment_id), SUM(consultation_fee) FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.status = 'Completed' AND a.appointment_date BETWEEN '$start_date' AND '$end_date' GROUP BY specialization";
                                 break;
                        }
                        if ($query):
                            $res = $conn->query($query);
                            if ($res && $res->num_rows > 0): ?>
                            <table id="reportTable">
                                <thead><tr><?php foreach($headers as $h) echo "<th>$h</th>"; ?></tr></thead>
                                <tbody>
                                    <?php while($row = $res->fetch_assoc()): ?>
                                    <tr><?php foreach($row as $v) echo "<td>".htmlspecialchars($v)."</td>"; ?></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: echo "<p style='color: #64748b; padding: 20px 0;'>No generated analytics found for this period. Please check uploaded documents below.</p>"; endif; endif; ?>
                    </div>

                    <!-- Uploaded Documents Section -->
                    <div class="card" style="margin-top: 30px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                             <h3 style="font-size: 18px; margin-bottom: 20px;">Uploaded Documents (Manual)</h3>
                        </div>
                        <?php
                        // Intelligent mapping of view types to upload keys
                        $keywords = [];
                        $check_category = true;

                        switch($type) {
                            case 'consultation_revenue': $keywords = ['Consultation', 'Revenue']; break;
                            case 'appointment_report': $keywords = ['Appointment', 'Consultation', 'Diagnosis']; break;
                            case 'patient_visit': $keywords = ['Patient', 'Visit', 'Treatment']; break;
                            case 'doctor_performance': $keywords = ['Performance']; break;
                            
                            // Canteen Specific Reports (Strict Matching)
                            case 'canteen_daily_sales': 
                                $keywords = ['Daily Sales Report', 'Daily Sales']; 
                                $check_category = false; 
                                break;
                            case 'canteen_item_sales': 
                                $keywords = ['Item-Wise Sales Report', 'Menu Performance Report']; 
                                $check_category = false; 
                                break;
                            case 'canteen_payments': 
                                $keywords = ['Payment Collection Report', 'Monthly Revenue Report']; 
                                $check_category = false; 
                                break;
                            case 'canteen_stock': 
                                $keywords = ['Stock Usage Report', 'Inventory Report']; 
                                $check_category = false; 
                                break;

                            // Receptionist
                            case 'receptionist_appointment':
                                $keywords = ['Appointment Booking Report', 'Appointment Booking'];
                                $check_category = false;
                                break;
                            case 'receptionist_registration':
                                $keywords = ['Patient Registration Report', 'Patient Registration'];
                                $check_category = false;
                                break;
                            case 'receptionist_checkin':
                                $keywords = ['Daily Check-In / Check-Out Report', 'Daily Check-In', 'Check-Out'];
                                $check_category = false;
                                break;

                            // Pharmacist
                            case 'pharmacist_sales':
                                $keywords = ['Medicine Sales Report', 'Medicine Sales'];
                                $check_category = false;
                                break;
                            case 'pharmacist_stock':
                                $keywords = ['Stock Usage / Remaining Stock Report', 'Stock Usage', 'Remaining Stock'];
                                $check_category = false;
                                break;
                            case 'pharmacist_expiry':
                                $keywords = ['Expiry Alert Report', 'Expiry Alert'];
                                $check_category = false;
                                break;

                            // Nurse
                            case 'nurse_vitals':
                                $keywords = ['Vital Signs Monitoring Report', 'Vital Signs'];
                                $check_category = false;
                                break;
                            case 'nurse_care':
                                $keywords = ['Patient Care / Duty Report', 'Patient Care', 'Duty Report'];
                                $check_category = false;
                                break;
                            
                            // Lab Staff
                            case 'lab_daily':
                                $keywords = ['Daily Test Report', 'Daily Test'];
                                $check_category = false;
                                break;
                            case 'lab_analytics':
                                $keywords = ['Test Type Analysis', 'Analysis'];
                                $check_category = false;
                                break;
                            case 'lab_equipment':
                                $keywords = ['Equipment Utilization', 'Equipment'];
                                $check_category = false;
                                break;
                            case 'lab_revenue': 
                                $keywords = ['Monthly Lab Revenue', 'Lab Revenue']; 
                                $check_category = false; 
                                break;

                            case 'dept_revenue': $keywords = ['Department', 'Revenue']; break;
                            case 'lab_revenue': $keywords = ['Lab', 'Test']; break;
                            case 'pharmacy_sales': $keywords = ['Pharmacy', 'Medicine', 'Sales']; break; 
                            case 'canteen_revenue': $keywords = ['Canteen', 'Food']; break; // Legacy fallback
                            default: $keywords = explode('_', $type);
                        }
                        
                        $like_clauses = ["report_type = '$type'"];
                        foreach($keywords as $k) {
                            $like_clauses[] = "report_type LIKE '%$k%'";
                            $like_clauses[] = "report_title LIKE '%$k%'";
                            if ($check_category) {
                                $like_clauses[] = "report_category LIKE '%$k%'";
                            }
                        }
                        $type_sql = "(" . implode(' OR ', $like_clauses) . ")";

                        $manual_query = "SELECT m.*, r.name as u_name FROM manual_reports m JOIN users u ON m.user_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id WHERE $type_sql AND (m.report_date BETWEEN '$start_date' AND '$end_date') ";
                        if ($effective_role != 'admin') {
                            $manual_query .= " AND m.user_id = $user_id";
                        }
                        $manual_query .= " ORDER BY m.report_date DESC";
                        
                        $m_res = $conn->query($manual_query);
                        if ($m_res && $m_res->num_rows > 0):
                        ?>
                        <table id="manualReportTable" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <th style="padding: 12px; text-align: left; color: #94a3b8; cursor: pointer;">Date <i class="fas fa-sort" style="font-size:10px;"></i></th>
                                    <th style="padding: 12px; text-align: left; color: #94a3b8; cursor: pointer;">Title <i class="fas fa-sort" style="font-size:10px;"></i></th>
                                    <th style="padding: 12px; text-align: left; color: #94a3b8; cursor: pointer;">Uploaded By <i class="fas fa-sort" style="font-size:10px;"></i></th>
                                    <th style="padding: 12px; text-align: left; color: #94a3b8;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($doc = $m_res->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($doc['report_date'])); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($doc['report_title']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($doc['u_name']); ?></td>
                                    <td style="padding: 12px;">
                                        <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-eye"></i> View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p style="color: #64748b; padding: 10px 0;">No manual documents uploaded for this specific report type in this period.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>



            <?php elseif ($view == 'repository'): ?>
                <div style="margin-bottom: 40px;">
                    <h2 style="font-size: 28px; margin-bottom: 10px;">Document Repository</h2>
                    <p style="color:var(--text-dim);">Centralized storage for all uploaded PDF reports and manual archives.</p>
                </div>

                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3>All Uploaded Documents</h3>
                        <input type="text" id="repoSearch" placeholder="Search files..." style="background: #020617; border: 1px solid var(--border); padding: 8px 15px; border-radius: 8px; color: #fff; width: 200px;">
                    </div>

                    <table id="repoTable">
                        <thead>
                            <tr>
                                <th style="cursor:pointer;">Date <i class="fas fa-sort"></i></th>
                                <th style="cursor:pointer;">Report Title <i class="fas fa-sort"></i></th>
                                <th style="cursor:pointer;">Department <i class="fas fa-sort"></i></th>
                                <th style="cursor:pointer;">Uploaded By <i class="fas fa-sort"></i></th>
                                <th>File</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $repo_query = ($role == 'admin') ? "SELECT m.*, r.name as u_name FROM manual_reports m JOIN users u ON m.user_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id ORDER BY m.created_at DESC" : "SELECT m.*, r.name as u_name FROM manual_reports m JOIN users u ON m.user_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id WHERE m.user_id = $user_id ORDER BY m.created_at DESC";
                            $repo_res = $conn->query($repo_query);
                            if ($repo_res && $repo_res->num_rows > 0):
                                while($doc = $repo_res->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($doc['report_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($doc['report_title']); ?></strong></td>
                                <td><span class="badge"><?php echo strtoupper($doc['user_role']); ?></span></td>
                                <td><?php echo htmlspecialchars($doc['u_name']); ?></td>
                                <td><i class="fas fa-file-pdf" style="color:#ef4444;"></i> PDF Document</td>
                                <td>
                                    <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-outline" style="padding: 5px 12px; font-size: 11px;"><i class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:50px;">No documents found in the repository.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Upload Modal removed (using included component) -->


    <script>
        // Set Date Range Logic
        function setDateRange(type) {
            const today = new Date();
            let start = new Date();
            let end = new Date();

            if (type === 'today') {
                // Already set
            } else if (type === 'month') {
                start = new Date(today.getFullYear(), today.getMonth(), 1);
            } else if (type === 'year') {
                start = new Date(today.getFullYear(), 0, 1);
            }

            // Format to YYYY-MM-DD
            const fmt = (d) => d.toISOString().split('T')[0];
            document.getElementById('startDate').value = fmt(start);
            document.getElementById('endDate').value = fmt(end);
            document.getElementById('dateForm').submit();
        }

        // Universal Table Search
        function setupSearch(inputId, tableIds) {
            const input = document.getElementById(inputId);
            if (!input) return;

            input.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                tableIds.forEach(tblId => {
                    const table = document.getElementById(tblId);
                    if (table) {
                        const tr = table.getElementsByTagName('tr');
                        for (let i = 1; i < tr.length; i++) { // Skip header
                            let txtValue = tr[i].textContent || tr[i].innerText;
                            tr[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
                        }
                    }
                });
            });
        }
        setupSearch('tableSearch', ['reportTable', 'manualReportTable']);
        setupSearch('repoSearch', ['repoTable']);

        // Universal Table Sort
        document.querySelectorAll('th').forEach(th => {
            th.addEventListener('click', function() {
                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const index = Array.from(th.parentNode.children).indexOf(th);
                const asc = this.asc = !this.asc;
                
                rows.sort((a, b) => {
                    const valA = a.children[index].innerText;
                    const valB = b.children[index].innerText;
                    return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
                });

                rows.forEach(row => tbody.appendChild(row));
            });
        });

        <?php if ($view == 'analytics'): ?>
        // 1. Revenue Chart
        new Chart(document.getElementById('revenueTrendChart'), {
            type: 'line',
            data: {
                labels: <?php echo $labels_rev; ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo $values_rev; ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // 2. Dept Chart
        new Chart(document.getElementById('deptDistChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo $labels_dept; ?>,
                datasets: [{
                    data: <?php echo $values_dept; ?>,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // 3. Traffic Chart
        new Chart(document.getElementById('trafficChart'), {
            type: 'bar',
            data: {
                labels: <?php echo $labels_traf; ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo $values_traf; ?>,
                    backgroundColor: '#3b82f6',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
        <?php endif; ?>

        function exportTableToExcel(tableID, filename = 'Hospital_Report'){
            var downloadLink;
            var dataType = 'application/vnd.ms-excel';
            var tableSelect = document.getElementById(tableID);
            var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
            filename = filename + '.xls';
            downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            if(navigator.msSaveOrOpenBlob){
                var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
                navigator.msSaveOrOpenBlob( blob, filename);
            } else {
                downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
                downloadLink.download = filename;
                downloadLink.click();
            }
        }
    </script>
    <?php 
    // Set staff_type for the modal usage
    $staff_type = $effective_role;
    include 'includes/report_upload_modal.php'; 
    ?>
</body>
</html>
