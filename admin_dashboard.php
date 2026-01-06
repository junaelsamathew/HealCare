<?php
session_start();
include 'includes/db_connect.php';

// Simple Auth Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin') {
        // Allow
    } else {
        header("Location: login.php");
        exit();
    }
}

// Get current section from URL parameter
$section = $_GET['section'] ?? 'dashboard';

// Handle various POST actions
$success_msg = '';
$error_msg = '';

// Handle Approval/Rejection
if (isset($_POST['action']) && isset($_POST['reg_id'])) {
    $reg_id = $_POST['reg_id'];
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $conn->begin_transaction();
        try {
            $res = $conn->query("SELECT * FROM registrations WHERE registration_id = $reg_id");
            $reg = $res->fetch_assoc();
            
            $email = $reg['email'];
            $role = $reg['user_type'];
            
            $year = date("Y");
            $rand = rand(1000, 9999);
            if ($role == 'doctor') {
                $username = "HC-DR-{$year}-{$rand}";
            } else {
                $username = "HC-ST-{$year}-{$rand}";
            }

            $temp_pass = 'HealCare123';
            $password = password_hash($temp_pass, PASSWORD_DEFAULT); 

            $admin_msg = "Congratulations! Your application (ID: {$reg['app_id']}) has been approved. Your Login ID is: $username";
            $conn->query("UPDATE registrations SET status = 'Approved', admin_message = '$admin_msg' WHERE registration_id = $reg_id");

            $perms = ($role == 'doctor') ? 'View Medical Records, Write prescriptions' : 'General Access';
            $conn->query("INSERT INTO users (registration_id, username, email, password, role, permissions, force_password_change, status) 
                         VALUES ($reg_id, '$username', '$email', '$password', '$role', '$perms', 1, 'Active')");
            $new_user_id = $conn->insert_id;

            if ($role == 'doctor') {
                $spec = mysqli_real_escape_string($conn, $reg['specialization']);
                $qual = mysqli_real_escape_string($conn, $reg['highest_qualification']);
                $exp = (int)$reg['total_experience'];
                $dept = mysqli_real_escape_string($conn, $reg['dept_preference']);
                $doj = mysqli_real_escape_string($conn, $reg['date_of_joining']);
                $desig = mysqli_real_escape_string($conn, $reg['designation']);

                $conn->query("INSERT INTO doctors (user_id, specialization, qualification, experience, department, date_of_join, designation) 
                             VALUES ($new_user_id, '$spec', '$qual', $exp, '$dept', '$doj', '$desig')");
            } elseif ($role == 'staff') {
                $stype = $reg['staff_type'];
                $dept = mysqli_real_escape_string($conn, $reg['dept_preference']);
                $shift = mysqli_real_escape_string($conn, $reg['shift_preference']);
                $qual = mysqli_real_escape_string($conn, $reg['qualification_details']);
                $rel_exp = (int)$reg['relevant_experience'];
                $doj = mysqli_real_escape_string($conn, $reg['date_of_joining']);
                $desig = mysqli_real_escape_string($conn, $reg['designation'] ?? 'Staff');
                
                if ($stype == 'nurse') {
                    $conn->query("INSERT INTO nurses (user_id, department, shift, qualification, experience, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$dept', '$shift', '$qual', $rel_exp, '$doj', '$desig', 'Active')");
                } elseif ($stype == 'lab_staff') {
                    $ltype = mysqli_real_escape_string($conn, $reg['specialization']);
                    $conn->query("INSERT INTO lab_staff (user_id, lab_type, shift, qualification, experience, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$ltype', '$shift', '$qual', $rel_exp, '$doj', '$desig', 'Active')");
                } elseif ($stype == 'pharmacist') {
                    $conn->query("INSERT INTO pharmacists (user_id, qualification, experience, shift, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$qual', $rel_exp, '$shift', '$doj', '$desig', 'Active')");
                } elseif ($stype == 'canteen_staff') {
                    $crole = mysqli_real_escape_string($conn, $reg['canteen_job_role']);
                    $conn->query("INSERT INTO canteen_staff (user_id, role, shift, date_of_join, status) 
                                 VALUES ($new_user_id, '$crole', '$shift', '$doj', 'Active')");
                } elseif ($stype == 'receptionist') {
                    $langs = mysqli_real_escape_string($conn, $reg['languages_known']);
                    $conn->query("INSERT INTO receptionists (user_id, desk_no, shift, experience, qualification, date_of_join, language_known, status) 
                                 VALUES ($new_user_id, 'Desk-1', '$shift', $rel_exp, '$qual', '$doj', '$langs', 'Active')");
                }
            }

            $conn->commit();
            $success_msg = "Application Approved! Notification sent to $email. Generated ID: <strong>$username</strong>";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error: " . $e->getMessage();
        }
    } elseif ($action == 'reject') {
        $admin_msg = "We regret to inform you that your application has been rejected after review.";
        $conn->query("UPDATE registrations SET status = 'Rejected', admin_message = '$admin_msg' WHERE registration_id = $reg_id");
        $success_msg = "Application Rejected. Notification sent successfully.";
    }
}

// Fetch statistics with error handling
$today = date('Y-m-d');

// Total users
try {
    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
} catch (Exception $e) {
    $total_users = 0;
}

// Pending bills (table may not exist yet)
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM billing WHERE status = 'Pending'");
    $pending_bills = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $pending_bills = 0;
}

// Today's patients
try {
    $result = $conn->query("SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE DATE(appointment_date) = '$today'");
    $todays_patients = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $todays_patients = 0;
}

// Pharmacy stock alerts (placeholder - table may not exist yet)
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM pharmacy_stock WHERE quantity < minimum_stock");
    $pharmacy_alerts = $result ? $result->fetch_assoc()['count'] : 3;
} catch (Exception $e) {
    $pharmacy_alerts = 3; // Default placeholder
}

// Fetch data based on section
$pending_requests = $conn->query("SELECT * FROM registrations WHERE status = 'Pending' ORDER BY registered_date DESC");
$all_users = $conn->query("SELECT u.*, r.app_id FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --dark-blue: #1e293b;
            --darker-blue: #0f172a;
            --darkest-blue: #020617;
            --accent-green: #10b981;
            --accent-orange: #f59e0b;
            --accent-red: #ef4444;
            --text-white: #f8fafc;
            --text-gray: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--darkest-blue);
            color: var(--text-white);
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            background: var(--darker-blue);
            border-right: 1px solid var(--border-color);
            padding: 30px 0;
            position: fixed;
            overflow-y: auto;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-blue);
            padding: 0 30px;
            margin-bottom: 40px;
            display: block;
            text-decoration: none;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-section-title {
            padding: 0 30px;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 30px;
            color: var(--text-gray);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            gap: 12px;
        }

        .nav-link:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-blue);
        }

        .nav-link.active {
            background: rgba(59, 130, 246, 0.15);
            color: var(--primary-blue);
            border-left: 3px solid var(--primary-blue);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 40px 50px;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-title p {
            color: var(--text-gray);
            font-size: 14px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--dark-blue);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-card-title {
            font-size: 13px;
            color: var(--text-gray);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-card-value {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-card-subtitle {
            font-size: 12px;
            color: var(--text-gray);
        }

        /* Content Sections */
        .content-section {
            background: var(--dark-blue);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-success {
            background: var(--accent-green);
            color: white;
        }

        .btn-danger {
            background: var(--accent-red);
            color: white;
        }

        .btn-warning {
            background: var(--accent-orange);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            color: var(--text-gray);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-completed { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-unpaid { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-gray);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            background: var(--darker-blue);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-white);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        /* Placeholder Section */
        .placeholder-section {
            text-align: center;
            padding: 60px 20px;
        }

        .placeholder-section i {
            font-size: 64px;
            color: var(--text-gray);
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .placeholder-section h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .placeholder-section p {
            color: var(--text-gray);
            font-size: 14px;
        }
        /* Admin specific fixes for new header */
        .sidebar { top: 72px !important; height: calc(100vh - 72px) !important; }
        .main-content { margin-top: 72px !important; }
    </style>
</head>
<body>
    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-sizing: border-box;">
        <h1 style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">+ HEALCARE</h1>
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">EMERGENCY</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">(+254) 717 783 146</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-clock"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WORK HOUR</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">09:00 - 20:00 Everyday</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">LOCATION</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">Kanjirapally, Kottayam</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="admin_dashboard.php" class="logo">HEALTHCARE ADMIN</a>
        
        <div class="nav-section">
            <div class="nav-section-title">Overview</div>
            <a href="?section=dashboard" class="nav-link <?php echo $section == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">User Management</div>
            <a href="?section=pending-requests" class="nav-link <?php echo $section == 'pending-requests' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Pending Requests
            </a>
            <a href="?section=all-users" class="nav-link <?php echo $section == 'all-users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> All Users
            </a>
            <a href="?section=create-user" class="nav-link <?php echo $section == 'create-user' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Create User
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Operations</div>
            <a href="?section=appointments" class="nav-link <?php echo $section == 'appointments' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <a href="?section=doctor-scheduling" class="nav-link <?php echo $section == 'doctor-scheduling' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Doctor Scheduling
            </a>
            <a href="?section=canteen-menu" class="nav-link <?php echo $section == 'canteen-menu' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> Canteen Menu
            </a>
            <a href="?section=packages" class="nav-link <?php echo $section == 'packages' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Health Packages
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Emergency & Contact</div>
            <a href="?section=ambulance" class="nav-link <?php echo $section == 'ambulance' ? 'active' : ''; ?>">
                <i class="fas fa-ambulance"></i> Ambulance Service
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Reports & Analytics</div>
            <a href="?section=reports" class="nav-link <?php echo $section == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Revenue Reports
            </a>
            <a href="?section=complaints" class="nav-link <?php echo $section == 'complaints' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i> Complaint Logs
            </a>
        </div>

        <div class="nav-section">
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($section == 'dashboard'): ?>
            <!-- Dashboard Overview -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>. Here's what's happening today.</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--primary-blue);">
                            <i class="fas fa-user-injured"></i>
                        </div>
                    </div>
                    <div class="stat-card-title">Today's Patients</div>
                    <div class="stat-card-value" style="color: var(--primary-blue);"><?php echo $todays_patients; ?></div>
                    <div class="stat-card-subtitle">Active appointments today</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-orange);">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                    <div class="stat-card-title">Pending Bills</div>
                    <div class="stat-card-value" style="color: var(--accent-orange);"><?php echo $pending_bills; ?></div>
                    <div class="stat-card-subtitle">Awaiting payment</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-green);">
                            <i class="fas fa-bed"></i>
                        </div>
                    </div>
                    <div class="stat-card-title">Bed Occupancy</div>
                    <div class="stat-card-value" style="color: var(--accent-green);">85%</div>
                    <div class="stat-card-subtitle">170/200 beds occupied</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-red);">
                            <i class="fas fa-pills"></i>
                        </div>
                    </div>
                    <div class="stat-card-title">Pharmacy Alerts</div>
                    <div class="stat-card-value" style="color: var(--accent-red);"><?php echo $pharmacy_alerts; ?></div>
                    <div class="stat-card-subtitle">Low stock items</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Quick Actions</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <a href="?section=pending-requests" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-clock"></i> View Pending Requests
                    </a>
                    <a href="?section=create-user" class="btn btn-success" style="text-align: center;">
                        <i class="fas fa-user-plus"></i> Create New User
                    </a>
                    <a href="?section=appointments" class="btn btn-warning" style="text-align: center;">
                        <i class="fas fa-calendar-check"></i> Manage Appointments
                    </a>
                    <a href="?section=reports" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-chart-bar"></i> Generate Report
                    </a>
                </div>
            </div>

        <?php elseif ($section == 'pending-requests'): ?>
            <!-- Pending Registration Requests -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Pending Registration Requests</h1>
                    <p>Review and approve or reject staff and doctor applications</p>
                </div>
            </div>

            <div class="content-section">
                <?php if ($pending_requests->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Role</th>
                                <th>Qualification</th>
                                <th>Experience</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $pending_requests->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                        <small style="color: var(--text-gray);"><?php echo htmlspecialchars($row['email']); ?></small><br>
                                        <small style="color: var(--text-gray);"><?php echo htmlspecialchars($row['phone']); ?></small><br>
                                        <small style="color: var(--primary-blue); font-weight: 600;">ID: <?php echo htmlspecialchars($row['app_id']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-pending"><?php echo ucfirst($row['user_type']); ?></span>
                                        <?php if($row['staff_type']): ?>
                                            <br><small>(<?php echo ucfirst($row['staff_type']); ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['highest_qualification']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_experience']); ?> Years</td>
                                    <td>
                                        <?php if($row['specialization']): ?>
                                            <strong>Spec:</strong> <?php echo htmlspecialchars($row['specialization']); ?><br>
                                        <?php endif; ?>
                                        <?php if($row['dept_preference']): ?>
                                            <strong>Dept:</strong> <?php echo htmlspecialchars($row['dept_preference']); ?><br>
                                        <?php endif; ?>
                                        <?php if($row['resume_path']): ?>
                                            <a href="<?php echo $row['resume_path']; ?>" target="_blank" style="color: var(--primary-blue);">View Resume</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="reg_id" value="<?php echo $row['registration_id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success" style="font-size: 12px; padding: 8px 15px; margin-bottom: 5px;" onclick="return confirm('Approve this application?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" style="font-size: 12px; padding: 8px 15px;" onclick="return confirm('Reject this application?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-inbox"></i>
                        <h3>No Pending Requests</h3>
                        <p>All applications have been processed</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($section == 'all-users'): ?>
            <!-- All Users -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>User Management</h1>
                    <p>View and manage all registered users</p>
                </div>
            </div>

            <div class="content-section">
                <table>
                    <thead>
                        <tr>
                            <th>Username / ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>App ID</th>
                            <th>Permissions</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $all_users_display = $conn->query("SELECT u.*, r.app_id FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id ORDER BY u.created_at DESC");
                        while($row = $all_users_display->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><span class="badge badge-active"><?php echo ucfirst($row['role']); ?></span></td>
                                <td><small style="color: var(--primary-blue);"><?php echo $row['app_id'] ?? 'Manual'; ?></small></td>
                                <td><small style="color: var(--text-gray);"><?php echo htmlspecialchars($row['permissions'] ?? 'Full Access'); ?></small></td>
                                <td><span class="badge badge-active"><?php echo $row['status']; ?></span></td>
                                <td><small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small></td>
                                <td>
                                    <button class="btn btn-primary" style="font-size: 11px; padding: 5px 10px;"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-danger" style="font-size: 11px; padding: 5px 10px;"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section == 'create-user'): ?>
            <!-- Create User Form -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Create New User</h1>
                    <p>Manually create doctor, staff, or admin accounts</p>
                </div>
            </div>

            <div class="content-section">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Login ID (e.g., HC-DR-2024-001)</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Official Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Temporary Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" id="roleSelect" required onchange="toggleDoctorFields()">
                                <option value="doctor">Doctor</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>

                    <div id="doctorFields" style="display: block; background: rgba(255,255,255,0.03); padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                        <h4 style="margin-bottom: 15px; font-size: 16px;">Doctor-Specific Fields</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Specialization</label>
                                <input type="text" name="specialization">
                            </div>
                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" name="qualification">
                            </div>
                            <div class="form-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="experience">
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department">
                                    <option value="General Medicine / Cardiovascular">General Medicine / Cardiovascular</option>
                                    <option value="Gynecology">Gynecology</option>
                                    <option value="Orthopedics">Orthopedics</option>
                                    <option value="ENT">ENT</option>
                                    <option value="Ophthalmology">Ophthalmology</option>
                                    <option value="Dermatology">Dermatology</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date of Joining</label>
                                <input type="date" name="date_of_join">
                            </div>
                            <div class="form-group">
                                <label>Designation</label>
                                <select name="designation">
                                    <option value="Consultant">Consultant</option>
                                    <option value="Senior Doctor">Senior Doctor</option>
                                    <option value="Junior Doctor">Junior Doctor</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Permissions</label>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <label style="cursor:pointer;"><input type="checkbox" name="permissions[]" value="Manage Appointments"> Manage Appointments</label>
                            <label style="cursor:pointer;"><input type="checkbox" name="permissions[]" value="View Records"> View Records</label>
                            <label style="cursor:pointer;"><input type="checkbox" name="permissions[]" value="Edit Records"> Edit Records</label>
                            <label style="cursor:pointer;"><input type="checkbox" name="permissions[]" value="Billing Access"> Billing Access</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Create User Account
                    </button>
                </form>
            </div>

        <?php elseif ($section == 'appointments'): ?>
            <!-- Appointments Management -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Appointments Management</h1>
                    <p>View and manage all patient appointments</p>
                </div>
            </div>

            <div class="content-section">
                <div class="placeholder-section">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Appointments Module</h3>
                    <p>View all appointments, modify timings, and manage scheduling</p>
                </div>
            </div>

        <?php elseif ($section == 'doctor-scheduling'): ?>
            <!-- Doctor Scheduling -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Doctor Scheduling</h1>
                    <p>Manage doctor availability and assign departments</p>
                </div>
            </div>

            <div class="content-section">
                <div class="placeholder-section">
                    <i class="fas fa-user-md"></i>
                    <h3>Doctor Scheduling Module</h3>
                    <p>Add doctors, update availability, assign departments, and manage schedules</p>
                </div>
            </div>

        <?php elseif ($section == 'canteen-menu'): ?>
            <!-- Canteen Menu Management -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Canteen Menu Management</h1>
                    <p>Add food items, set prices, and view daily orders</p>
                </div>
            </div>

            <div class="content-section">
                <div class="placeholder-section">
                    <i class="fas fa-utensils"></i>
                    <h3>Canteen Management Module</h3>
                    <p>Add food items, set prices, and view daily food orders</p>
                </div>
            </div>

        <?php elseif ($section == 'packages'): ?>
            <!-- Health Packages -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Health Checkup Packages</h1>
                    <p>Create packages and set discount prices</p>
                </div>
            </div>

            <div class="content-section">
                <div class="placeholder-section">
                    <i class="fas fa-box"></i>
                    <h3>Health Packages Module</h3>
                    <p>Create checkup packages and set discount pricing</p>
                </div>
            </div>

        <?php elseif ($section == 'ambulance'): ?>
            <!-- Ambulance Service -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Ambulance Emergency Service</h1>
                    <p>Manage emergency contact numbers</p>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Emergency Contacts</h3>
                    <button class="btn btn-success"><i class="fas fa-plus"></i> Add Contact</button>
                </div>
                <div class="placeholder-section">
                    <i class="fas fa-ambulance"></i>
                    <h3>Ambulance Service Module</h3>
                    <p>Manage ambulance emergency contact numbers</p>
                </div>
            </div>

        <?php elseif ($section == 'reports'): ?>
            <!-- Revenue Reports -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Revenue Reports & Analytics</h1>
                    <p>Generate daily, weekly, and monthly reports</p>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Generate Report</h3>
                </div>
                <div class="form-grid" style="max-width: 600px;">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select>
                            <option>Daily Report</option>
                            <option>Weekly Report</option>
                            <option>Monthly Report</option>
                            <option>Custom Range</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Report Category</label>
                        <select>
                            <option>Revenue</option>
                            <option>Appointments</option>
                            <option>Patient Statistics</option>
                            <option>Department Performance</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary"><i class="fas fa-download"></i> Generate Report</button>
            </div>

        <?php elseif ($section == 'complaints'): ?>
            <!-- Complaint Logs -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Complaint Logs</h1>
                    <p>View and manage patient complaints</p>
                </div>
            </div>

            <div class="content-section">
                <div class="placeholder-section">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Complaint Logs Module</h3>
                    <p>Track and manage all patient complaints and feedback</p>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <script>
        function toggleDoctorFields() {
            const role = document.getElementById('roleSelect').value;
            const fields = document.getElementById('doctorFields');
            if (role === 'doctor') {
                fields.style.display = 'block';
            } else {
                fields.style.display = 'none';
            }
        }
        
        window.addEventListener('DOMContentLoaded', toggleDoctorFields);
    </script>
</body>
</html>
