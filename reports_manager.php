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
    'canteen_revenue' => ['admin', 'canteen_staff']
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
            <a href="?view=analytics" class="nav-link <?php echo $view == 'analytics' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Data Analytics
            </a>
            <?php endif; ?>
            <a href="?view=repository" class="nav-link <?php echo $view == 'repository' ? 'active' : ''; ?>">
                <i class="fas fa-folder-open"></i> Document Repository
            </a>
        </nav>
        <div style="padding: 20px;">
            <a href="javascript:history.back()" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </aside>

    <main class="main">
        <header class="top-bar">
            <h2>Management Console</h2>
            <div style="display: flex; gap: 20px; align-items: center;">
                <span class="badge"><?php echo $effective_role; ?> Access</span>
                <button class="btn btn-primary" onclick="document.getElementById('uploadModal').style.display='flex'">
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
                            'canteen_revenue' => ['icon' => 'fa-hamburger', 'title' => 'Canteen Revenue', 'desc' => 'Earnings from food services and canteen orders.']
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

                    <form class="filters" method="POST">
                        <div class="filter-group">
                            <label>From Date</label>
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="filter-group">
                            <label>To Date</label>
                            <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Refresh Report</button>
                    </form>

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
                                $doc_filter = ($effective_role == 'doctor') ? "AND doctor_id = (SELECT doctor_id FROM doctors WHERE user_id = $user_id)" : "";
                                $query = "SELECT a.appointment_date, a.queue_number, d.specialization, r.name as patient_name, a.consultation_fee, a.status 
                                          FROM appointments a 
                                          JOIN users u ON a.doctor_id = u.user_id 
                                          JOIN doctors d ON u.user_id = d.user_id
                                          JOIN users p ON a.patient_id = p.user_id
                                          JOIN registrations r ON p.registration_id = r.registration_id
                                          WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date' AND a.status = 'Completed' $doc_filter";
                                break;
                            case 'lab_revenue':
                                $headers = ['Date', 'Order ID', 'Test Name', 'Category', 'Patient', 'Amount'];
                                $query = "SELECT b.bill_date, l.order_id, l.test_name, l.lab_category, r.name, b.total_amount 
                                          FROM lab_orders l 
                                          JOIN billing b ON l.order_id = b.appointment_id AND b.bill_type = 'Lab'
                                          JOIN users u ON l.patient_id = u.user_id
                                          JOIN registrations r ON u.registration_id = r.registration_id
                                          WHERE b.bill_date BETWEEN '$start_date' AND '$end_date'";
                                break;
                            case 'appointment_report':
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
                        <?php else: echo "<p>No records found.</p>"; endif; endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($view == 'analytics'): ?>
                <div style="margin-bottom: 40px;">
                    <h2 style="font-size: 28px; margin-bottom: 10px;">Executive Analytics</h2>
                    <p style="color:var(--text-dim);">Visual data study and comparative department analysis.</p>
                </div>

                <div class="analytics-grid">
                    <div class="chart-card">
                        <h3><i class="fas fa-chart-line"></i> Revenue Trends (Monthly)</h3>
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3><i class="fas fa-chart-pie"></i> Department Distribution</h3>
                        <canvas id="deptDistChart"></canvas>
                    </div>
                    <div class="chart-card" style="grid-column: span 2;">
                        <h3><i class="fas fa-chart-bar"></i> Daily Consultation Traffic</h3>
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>

                <?php
                // Fetch Data for Charts
                // 1. Monthly Revenue
                $rev_data = $conn->query("SELECT DATE_FORMAT(bill_date, '%b') as month, SUM(total_amount) as total FROM billing WHERE payment_status = 'Paid' GROUP BY month ORDER BY bill_date ASC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
                $labels_rev = json_encode(array_column($rev_data, 'month'));
                $values_rev = json_encode(array_column($rev_data, 'total'));

                // 2. Dept Distribution
                $dept_data = $conn->query("SELECT specialization, COUNT(appointment_id) as count FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id GROUP BY specialization")->fetch_all(MYSQLI_ASSOC);
                $labels_dept = json_encode(array_column($dept_data, 'specialization'));
                $values_dept = json_encode(array_column($dept_data, 'count'));

                // 3. Traffic
                $traffic_data = $conn->query("SELECT DATE_FORMAT(appointment_date, '%d %b') as day, COUNT(*) as count FROM appointments GROUP BY day ORDER BY appointment_date DESC LIMIT 7")->fetch_all(MYSQLI_ASSOC);
                $labels_traf = json_encode(array_reverse(array_column($traffic_data, 'day')));
                $values_traf = json_encode(array_reverse(array_column($traffic_data, 'count')));
                ?>

            <?php elseif ($view == 'repository'): ?>
                <div style="margin-bottom: 40px;">
                    <h2 style="font-size: 28px; margin-bottom: 10px;">Document Repository</h2>
                    <p style="color:var(--text-dim);">Centralized storage for all uploaded PDF reports and manual archives.</p>
                </div>

                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3>All Uploaded Documents</h3>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Report Title</th>
                                <th>Department</th>
                                <th>Uploaded By</th>
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

    <!-- Upload Modal -->
    <div class="upload-modal" id="uploadModal">
        <div class="modal-content">
            <h3 style="margin-bottom:20px;"><i class="fas fa-file-upload"></i> Upload New Report</h3>
            <form action="upload_report_handler.php" method="POST" enctype="multipart/form-data">
                <div class="filter-group" style="margin-bottom:20px;">
                    <label>Report Title</label>
                    <input type="text" name="report_title" placeholder="e.g. Monthly Lab Summary" required style="width:100%;">
                </div>
                <div class="filter-group" style="margin-bottom:20px;">
                    <label>Report Date</label>
                    <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required style="width:100%;">
                </div>
                <div class="filter-group" style="margin-bottom:20px;">
                    <label>Department / Category</label>
                    <select name="department" style="width:100%;">
                        <option>General</option>
                        <option>Laboratory</option>
                        <option>Pharmacy</option>
                        <option>Canteen</option>
                        <option>Medical Records</option>
                    </select>
                </div>
                <div class="filter-group" style="margin-bottom:30px;">
                    <label>PDF Document</label>
                    <input type="file" name="report_file" accept=".pdf" required style="width:100%; border: 1px dashed var(--border); padding: 30px; text-align: center;">
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">Submit Report</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('uploadModal').style.display='none'" style="flex:1; justify-content:center;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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
</body>
</html>
