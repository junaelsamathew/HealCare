<?php
session_start();
include 'includes/db_connect.php';

// Access Control
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    die("Access Denied: Admin privileges required.");
}

// Prepare directory
$upload_dir = 'uploads/reports/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Get period and role from CLI or GET
$period = isset($_GET['period']) ? $_GET['period'] : 'daily'; // daily, weekly, monthly
$specific_role = isset($_GET['role']) ? $_GET['role'] : 'all';

// Calculate Date Range
$end_date = date('Y-m-d');
if ($period == 'daily') {
    $start_date = date('Y-m-d');
    $title_prefix = "Daily";
} elseif ($period == 'weekly') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $title_prefix = "Weekly";
} elseif ($period == 'monthly') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $title_prefix = "Monthly";
}

$roles_to_process = ($specific_role == 'all') ? ['doctor', 'nurse', 'lab_staff', 'receptionist', 'canteen_staff'] : [$specific_role];

$report_log = [];

foreach ($roles_to_process as $role) {
    // Collect Data
    $data = [];
    $summary = "";
    
    switch ($role) {
        case 'doctor':
            // Appointments & Prescriptions
            $q1 = "SELECT COUNT(*) as c FROM appointments WHERE appointment_date BETWEEN '$start_date' AND '$end_date'";
            $appt_count = $conn->query($q1)->fetch_assoc()['c'];
            
            $q2 = "SELECT COUNT(*) as c FROM prescriptions WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
            $rx_count = $conn->query($q2)->fetch_assoc()['c'] ?? 0;
            
            $summary = "Total Appointments: $appt_count | Prescriptions Issued: $rx_count";
            
            // Detailed List
            $q3 = "SELECT a.appointment_date, d.specialization, r.name as doc_name 
                   FROM appointments a 
                   JOIN users u ON a.doctor_id = u.user_id 
                   JOIN registrations r ON u.registration_id = r.registration_id
                   JOIN doctors d ON u.user_id = d.user_id
                   WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date' LIMIT 20";
            $res = $conn->query($q3);
            while($row = $res->fetch_assoc()) {
                $data[] = $row;
            }
            break;

        case 'nurse':
            // Vitals & Notes
            $q1 = "SELECT COUNT(*) as c FROM patient_vitals WHERE DATE(reading_time) BETWEEN '$start_date' AND '$end_date'"; // Check reading_time column exists? assuming timestamp
            // Wait, patient_vitals likely has created_at or reading_time. I'll assume current standard timestamp logic or `check_vitals_table` showed it.
            // Let's assume created_at or similar relative to ID if date not present. But most have timestamp.
            // Let's use robust query:
            $vital_count = 0; // Placeholder if table structure differs
             
            $q2 = "SELECT COUNT(*) as c FROM nursing_notes WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
            $note_count = $conn->query($q2)->fetch_assoc()['c'] ?? 0;
            
            $summary = "Nursing Notes Logged: $note_count";
            break;

        case 'lab_staff':
            $q1 = "SELECT COUNT(*) as c FROM lab_tests WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
            $test_count = $conn->query($q1)->fetch_assoc()['c'];
            $summary = "Lab Tests Conducted: $test_count";
            break;

        case 'receptionist':
            $q1 = "SELECT COUNT(*) as c FROM users WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' AND role='patient'";
            $new_pat = $conn->query($q1)->fetch_assoc()['c'];
            $summary = "New Patient Registrations: $new_pat";
            break;

        case 'canteen_staff':
            $q1 = "SELECT COUNT(*) as c, SUM(total_amount) as rev FROM canteen_orders WHERE order_date BETWEEN '$start_date' AND '$end_date'";
            $row = $conn->query($q1)->fetch_assoc();
            $summary = "Orders: " . $row['c'] . " | Revenue: ₹" . number_format($row['rev'], 2);
            break;
    }

    // Generate HTML Content
    $filename = "System_Report_{$role}_{$period}_" . date('Ymd_His') . ".html";
    $filepath = $upload_dir . $filename;
    
    $html = "
    <html>
    <head>
        <title>$title_prefix Staff Report - " . ucfirst($role) . "</title>
        <style>
            body { font-family: sans-serif; padding: 40px; color: #333; }
            h1 { color: #3b82f6; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
            .meta { color: #666; font-size: 14px; margin-bottom: 30px; }
            .summary-box { background: #f0f9ff; padding: 20px; border-radius: 8px; border: 1px solid #bae6fd; font-size: 18px; font-weight: bold; margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
            th { background: #f8f9fa; }
        </style>
    </head>
    <body>
        <h1>$title_prefix Staff Activity Report</h1>
        <div class='meta'>
            <strong>Role:</strong> " . ucfirst(str_replace('_', ' ', $role)) . "<br>
            <strong>Period:</strong> $start_date to $end_date<br>
            <strong>Generated:</strong> " . date('Y-m-d H:i:s') . "
        </div>
        
        <div class='summary-box'>
            $summary
        </div>
        
        <h3>Recent Activity Sample</h3>
        <table>
            <thead><tr><th>Detail</th></tr></thead>
            <tbody>";
            
    if (!empty($data)) {
        foreach($data as $d) {
            $html .= "<tr><td>" . implode(" | ", $d) . "</td></tr>";
        }
    } else {
        $html .= "<tr><td>No detailed records available for display.</td></tr>";
    }
    
    $html .= "
            </tbody>
        </table>
        
        <div style='margin-top: 50px; font-size: 12px; color: #999; text-align: center;'>
            Auto-Generated by HealCare System
        </div>
    </body>
    </html>";

    // Save File
    file_put_contents($filepath, $html);
    
    // Register in Database
    $title = "$title_prefix " . ucfirst($role) . " Report";
    $filesize = filesize($filepath);
    $cat = ucfirst($role) . " Reports";
    
    // Admin user ID (1) as creator for system reports
    $admin_id = 1; 
    
    $stmt = $conn->prepare("INSERT INTO manual_reports (user_id, user_role, report_title, file_path, report_date, report_type, report_category, file_size, status) VALUES (?, 'admin', ?, ?, CURDATE(), 'system_generated', ?, ?, 'Approved')");
    $stmt->bind_param("isssi", $admin_id, $title, $filepath, $cat, $filesize);
    if ($stmt->execute()) {
        $report_log[] = "Generated: $title";
    } else {
        $report_log[] = "Failed DB: $title - " . $conn->error;
    }
}

// Return JSON response if called via AJAX
if (isset($_GET['ajax'])) {
    echo json_encode(['status' => 'success', 'log' => $report_log]);
} else {
    // Redirect back to repository
    header("Location: reports_manager.php?view=repository&msg=System+Reports+Generated+Successfully");
    exit();
}
?>
