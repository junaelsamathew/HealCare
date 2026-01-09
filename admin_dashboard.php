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
    } elseif ($action == 'add_user') {
        $name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $perms = isset($_POST['permissions']) ? implode(', ', $_POST['permissions']) : 'General Access';

        $conn->begin_transaction();
        try {
            // Check if username/email exists
            $check = $conn->query("SELECT user_id FROM users WHERE username = '$username' OR email = '$email'");
            if ($check->num_rows > 0) {
                throw new Exception("Username or Email already exists!");
            }

            // Insert into Registrations first to have a name/profile link
            $stmt_reg = $conn->prepare("INSERT INTO registrations (name, email, user_type, status, password) VALUES (?, ?, ?, 'Approved', ?)");
            $stmt_reg->bind_param("ssss", $name, $email, $role, $password);
            $stmt_reg->execute();
            $registration_id = $conn->insert_id;

            // Insert into Users
            $stmt = $conn->prepare("INSERT INTO users (registration_id, username, email, password, role, permissions, status, force_password_change) VALUES (?, ?, ?, ?, ?, ?, 'Active', 1)");
            $stmt->bind_param("isssss", $registration_id, $username, $email, $password, $role, $perms);
            $stmt->execute();
            $new_user_id = $conn->insert_id;

            if ($role == 'doctor') {
                $spec = mysqli_real_escape_string($conn, $_POST['specialization']);
                $qual = mysqli_real_escape_string($conn, $_POST['qualification']);
                $exp = (int)$_POST['experience'];
                $dept = mysqli_real_escape_string($conn, $_POST['department']);
                $doj = mysqli_real_escape_string($conn, $_POST['date_of_join']);
                $desig = mysqli_real_escape_string($conn, $_POST['designation']);

                $conn->query("INSERT INTO doctors (user_id, specialization, qualification, experience, department, date_of_join, designation) 
                             VALUES ($new_user_id, '$spec', '$qual', $exp, '$dept', '$doj', '$desig')");
            }

            $conn->commit();
            $success_msg = "User account created successfully! ID: <strong>$username</strong>";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error: " . $e->getMessage();
        }
    } elseif ($action == 'add_ambulance') {
        $driver = mysqli_real_escape_string($conn, $_POST['driver_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
        $v_no = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
        $v_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        
        $sql = "INSERT INTO ambulance_contacts (driver_name, phone_number, vehicle_number, vehicle_type, location, availability) 
                VALUES ('$driver', '$phone', '$v_no', '$v_type', '$location', 'Available')";
        if ($conn->query($sql)) {
            $success_msg = "Ambulance contact added successfully!";
        } else {
            $error_msg = "Error: " . $conn->error;
        }
    } elseif ($action == 'delete_ambulance') {
        $c_id = (int)$_POST['contact_id'];
        if ($conn->query("DELETE FROM ambulance_contacts WHERE contact_id = $c_id")) {
            $success_msg = "Contact deleted successfully.";
        } else {
            $error_msg = "Error deleting contact.";
        }
    } elseif ($action == 'update_doctor_schedule') {
        $doc_id = (int)$_POST['doctor_id'];
        $day = mysqli_real_escape_string($conn, $_POST['day_of_week']);
        $start = mysqli_real_escape_string($conn, $_POST['start_time']);
        $end = mysqli_real_escape_string($conn, $_POST['end_time']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        $sql = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, status) 
                VALUES ($doc_id, '$day', '$start', '$end', '$status')
                ON DUPLICATE KEY UPDATE start_time = '$start', end_time = '$end', status = '$status'";
        if ($conn->query($sql)) {
            $success_msg = "Doctor schedule updated successfully!";
        } else {
            $error_msg = "Error updating schedule: " . $conn->error;
        }
    } elseif ($action == 'update_doctor_availability') {
        $doc_id = (int)$_POST['doctor_id'];
        $availability = mysqli_real_escape_string($conn, $_POST['availability_status']);
        if ($conn->query("UPDATE doctors SET availability_status = '$availability' WHERE user_id = $doc_id")) {
            $success_msg = "Doctor availability updated!";
        } else {
            $error_msg = "Error updating availability.";
        }
    } elseif ($action == 'update_doctor_dept') {
        $doc_id = (int)$_POST['doctor_id'];
        $dept = mysqli_real_escape_string($conn, $_POST['department']);
        $spec = mysqli_real_escape_string($conn, $_POST['specialization']);
        if ($conn->query("UPDATE doctors SET department = '$dept', specialization = '$spec' WHERE user_id = $doc_id")) {
            $success_msg = "Doctor department/specialization updated!";
        } else {
            $error_msg = "Error updating department.";
        }
    } elseif ($action == 'save_menu_item') {
        $name = mysqli_real_escape_string($conn, $_POST['food_name']);
        $cat = mysqli_real_escape_string($conn, $_POST['meal_category']);
        $diet = mysqli_real_escape_string($conn, $_POST['diet_type']);
        $price = (float)$_POST['price'];
        $desc = mysqli_real_escape_string($conn, $_POST['description']);
        $avail = mysqli_real_escape_string($conn, $_POST['availability']);
        $mid = isset($_POST['menu_id']) ? (int)$_POST['menu_id'] : null;

        if ($mid) {
            $sql = "UPDATE canteen_menu SET item_name='$name', item_category='$cat', diet_type='$diet', price=$price, description='$desc', availability='$avail' WHERE menu_id=$mid";
        } else {
            $sql = "INSERT INTO canteen_menu (item_name, item_category, diet_type, price, description, availability) VALUES ('$name', '$cat', '$diet', $price, '$desc', '$avail')";
        }
        if ($conn->query($sql)) {
            $success_msg = "Menu item saved successfully!";
        } else {
            $error_msg = "Error saving menu item: " . $conn->error;
        }
    } elseif ($action == 'delete_menu_item') {
        $mid = (int)$_POST['menu_id'];
        if ($conn->query("DELETE FROM canteen_menu WHERE menu_id = $mid")) {
            $success_msg = "Menu item deleted!";
        } else {
            $error_msg = "Error deleting item.";
        }
    } elseif ($action == 'update_order_status') {
        $oid = (int)$_POST['order_id'];
        $status = mysqli_real_escape_string($conn, $_POST['new_status']);
        if ($conn->query("UPDATE canteen_orders SET order_status = '$status' WHERE order_id = $oid")) {
            $success_msg = "Order #$oid status updated!";
        } else {
            $error_msg = "Error updating order status.";
        }
    } elseif ($action == 'save_package') {
        $name = mysqli_real_escape_string($conn, $_POST['package_name']);
        $desc = mysqli_real_escape_string($conn, $_POST['description']);
        $tests = mysqli_real_escape_string($conn, $_POST['included_tests']);
        $actual = (float)$_POST['actual_price'];
        $discount_p = (float)$_POST['discount_price'];
        $percent = (int)$_POST['discount_percent'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $pid = isset($_POST['package_id']) ? (int)$_POST['package_id'] : null;

        if ($pid) {
            $sql = "UPDATE health_packages SET package_name='$name', package_description='$desc', included_tests='$tests', original_price=$actual, discounted_price=$discount_p, discount_percentage=$percent, status='$status' WHERE package_id=$pid";
        } else {
            $sql = "INSERT INTO health_packages (package_name, package_description, included_tests, original_price, discounted_price, discount_percentage, status) VALUES ('$name', '$desc', '$tests', $actual, $discount_p, $percent, '$status')";
        }
        if ($conn->query($sql)) {
            $success_msg = "Health package saved successfully!";
        } else {
            $error_msg = "Error saving health package: " . $conn->error;
        }
    } elseif ($action == 'delete_package') {
        $pid = (int)$_POST['package_id'];
        if ($conn->query("DELETE FROM health_packages WHERE package_id = $pid")) {
            $success_msg = "Health package deleted!";
        } else {
            $error_msg = "Error deleting package.";
        }
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--dark-blue);
            margin: 10% auto;
            padding: 30px;
            border: 1px solid var(--border-color);
            width: 500px;
            border-radius: 20px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            color: var(--text-gray);
            font-size: 24px;
            cursor: pointer;
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
                    <a href="?section=packages" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-box"></i> Health Packages
                    </a>
                </div>
            </div>

            <!-- Health Packages Sneak-Peek -->
            <div class="content-section" style="background: transparent; border: none; padding: 0;">
                <div class="section-header">
                    <h3 class="section-title">Health Packages Preview</h3>
                    <a href="?section=packages" style="color: var(--primary-blue); font-size: 14px; text-decoration: none; font-weight: 600;">Manage All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php
                    $preview_pkgs = $conn->query("SELECT * FROM health_packages WHERE status = 'Active' ORDER BY created_at DESC LIMIT 3");
                    if ($preview_pkgs && $preview_pkgs->num_rows > 0):
                        while($p = $preview_pkgs->fetch_assoc()):
                            $icon = 'fa-file-medical';
                            if (stripos($p['package_name'], 'Basic') !== false) $icon = 'fa-user-check';
                            elseif (stripos($p['package_name'], 'Comprehensive') !== false) $icon = 'fa-heartbeat';
                            elseif (stripos($p['package_name'], 'Diabetes') !== false) $icon = 'fa-file-prescription';
                    ?>
                        <div style="background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                                <div style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary-blue);">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <h4 style="margin: 0; color: #1e293b; font-size: 16px;"><?php echo htmlspecialchars($p['package_name']); ?></h4>
                            </div>
                            <p style="color: #64748b; font-size: 13px; line-height: 1.5; margin-bottom: 15px;"><?php echo htmlspecialchars($p['package_description']); ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                                <div style="color: #1e293b; font-weight: 800; font-size: 18px;">₹<?php echo number_format($p['discounted_price'], 0); ?></div>
                                <div style="font-size: 11px; background: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 4px; font-weight: 700;"><?php echo $p['discount_percentage']; ?>% OFF</div>
                            </div>
                        </div>
                    <?php endwhile; endif; ?>
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
                            <label>Full Name</label>
                            <input type="text" name="full_name" placeholder="Enter user's full name" required>
                        </div>
                        <div class="form-group">
                            <label>Official Email</label>
                            <input type="email" name="email" placeholder="e.g. name@healcare.com" required>
                        </div>
                        <div class="form-group">
                            <label>Username / Login ID</label>
                            <input type="text" name="username" placeholder="e.g. HC-DR-2024-001" required>
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

                    <div id="doctorFields" style="display: block; background: rgba(255,255,255,0.03); padding: 25px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); margin: 20px 0;">
                        <h4 style="margin-bottom: 20px; font-size: 16px; color: var(--primary-blue); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Doctor-Specific Fields</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Specialization</label>
                                <input type="text" name="specialization" placeholder="e.g. Cardiologist">
                            </div>
                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" name="qualification" placeholder="e.g. MBBS, MD">
                            </div>
                            <div class="form-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="experience" placeholder="e.g. 5">
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
                <?php
                // Fetch All Appointments
                $all_appts = $conn->query("
                    SELECT a.*, 
                           rd.name as doctor_name, 
                           rp.name as patient_name_reg,
                           pp.name as patient_name_prof
                    FROM appointments a
                    LEFT JOIN users ud ON a.doctor_id = ud.user_id
                    LEFT JOIN registrations rd ON ud.registration_id = rd.registration_id
                    LEFT JOIN users up ON a.patient_id = up.user_id
                    LEFT JOIN registrations rp ON up.registration_id = rp.registration_id
                    LEFT JOIN patient_profiles pp ON a.patient_id = pp.user_id
                    ORDER BY a.appointment_date DESC
                ");
                ?>

                <?php if ($all_appts && $all_appts->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Queue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($appt = $all_appts->fetch_assoc()): 
                                $p_name = $appt['patient_name_prof'] ?? $appt['patient_name_reg'] ?? 'Walk-in/Unknown';
                                $d_name = $appt['doctor_name'] ?? 'Unassigned';
                            ?>
                                <tr>
                                    <td><small style="color:var(--primary-blue);">#<?php echo $appt['appointment_id']; ?></small></td>
                                    <td><strong><?php echo htmlspecialchars($p_name); ?></strong></td>
                                    <td><?php echo htmlspecialchars($d_name); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($appt['appointment_time'] ?? $appt['appointment_date'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($appt['department']); ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($appt['status'] == 'Scheduled' ? 'active' : ($appt['status'] == 'Requested' ? 'pending' : 'completed')); ?>"><?php echo $appt['status']; ?></span></td>
                                    <td><?php echo $appt['queue_number'] ?? '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Appointments Found</h3>
                        <p>There are no appointments in the system yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($section == 'doctor-scheduling'): ?>
            <!-- Doctor Scheduling -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Doctor Scheduling</h1>
                    <p>Manage doctor availability, assign departments, and manage weekly schedules</p>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">All Doctors</h3>
                    <a href="?section=create-user" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Doctor</a>
                </div>

                <?php
                $doctors_sql = "SELECT d.*, r.name, u.username, u.email 
                                FROM doctors d 
                                JOIN users u ON d.user_id = u.user_id 
                                JOIN registrations r ON u.registration_id = r.registration_id 
                                ORDER BY d.department ASC";
                $doctors_res = $conn->query($doctors_sql);
                
                if ($doctors_res && $doctors_res->num_rows > 0):
                    $current_dept = '';
                    while($doc = $doctors_res->fetch_assoc()):
                        if ($current_dept != $doc['department']):
                            $current_dept = $doc['department'];
                            echo '<div style="background: rgba(59, 130, 246, 0.05); padding: 10px 20px; border-radius: 8px; margin: 30px 0 15px; border-left: 4px solid var(--primary-blue);">';
                            echo '<h4 style="color: var(--primary-blue); font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">' . htmlspecialchars($current_dept ?: 'Unassigned Dept') . '</h4>';
                            echo '</div>';
                        endif;
                ?>
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s; hover: background: rgba(255,255,255,0.04);">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="width: 50px; height: 50px; background: var(--primary-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                                    <?php echo substr($doc['name'], 0, 1); ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 16px;">DR. <?php echo htmlspecialchars($doc['name']); ?></h4>
                                    <p style="font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($doc['specialization']); ?> • <?php echo htmlspecialchars($doc['username']); ?></p>
                                    <span class="badge badge-<?php 
                                        echo ($doc['availability_status'] == 'Available' ? 'active' : ($doc['availability_status'] == 'Busy' ? 'pending' : 'rejected')); 
                                    ?>" style="margin-top: 5px; display: inline-block;">
                                        <?php echo $doc['availability_status'] ?: 'Available'; ?>
                                    </span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button onclick="openScheduleModal(<?php echo $doc['user_id']; ?>, '<?php echo htmlspecialchars($doc['name']); ?>')" class="btn btn-primary" style="font-size: 12px; padding: 8px 15px;"><i class="fas fa-calendar-alt"></i> Schedule</button>
                                <button onclick="openDeptModal(<?php echo $doc['user_id']; ?>, '<?php echo htmlspecialchars($doc['department']); ?>', '<?php echo htmlspecialchars($doc['specialization']); ?>')" class="btn btn-warning" style="font-size: 12px; padding: 8px 15px;"><i class="fas fa-building"></i> Dept</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_doctor_availability">
                                    <input type="hidden" name="doctor_id" value="<?php echo $doc['user_id']; ?>">
                                    <select name="availability_status" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 12px; border-radius: 6px; background: var(--darkest-blue); color: white; border: 1px solid var(--border-color);">
                                        <option value="Available" <?php echo $doc['availability_status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="Busy" <?php echo $doc['availability_status'] == 'Busy' ? 'selected' : ''; ?>>Busy</option>
                                        <option value="On Leave" <?php echo $doc['availability_status'] == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                <?php endwhile; else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-user-md"></i>
                        <h3>No Doctors Found</h3>
                        <p>Start by adding doctors from the "Create User" section.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Schedule Modal -->
            <div id="scheduleModal" class="modal">
                <div class="modal-content" style="width: 600px;">
                    <span class="close-modal" onclick="closeModal('scheduleModal')">&times;</span>
                    <h3 id="scheduleTitle" style="margin-bottom: 25px;">Manage Schedule</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_doctor_schedule">
                        <input type="hidden" name="doctor_id" id="sched_doc_id">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Day of Week</label>
                                <select name="day_of_week" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="Available">Available</option>
                                    <option value="Not Available">Not Available</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Start Time</label>
                                <input type="time" name="start_time" value="09:00" required>
                            </div>
                            <div class="form-group">
                                <label>End Time</label>
                                <input type="time" name="end_time" value="17:00" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 20px;">Save Schedule Entry</button>
                    </form>
                    
                    <div style="margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <h4 style="font-size: 14px; margin-bottom: 15px;">Current Weekly Schedule:</h4>
                        <div id="scheduleList" style="font-size: 12px; color: var(--text-gray);">
                            <!-- Will be populated via JS or shown in next reload -->
                            <p>Select a doctor and update entries. Each entry overwrites previous setting for that day.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dept Modal -->
            <div id="deptModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('deptModal')">&times;</span>
                    <h3>Assign Department</h3>
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="update_doctor_dept">
                        <input type="hidden" name="doctor_id" id="dept_doc_id">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Department</label>
                            <select name="department" id="dept_select" required>
                                <option value="General Medicine / Cardiovascular">General Medicine / Cardiovascular</option>
                                <option value="Gynecology">Gynecology</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="ENT">ENT</option>
                                <option value="Ophthalmology">Ophthalmology</option>
                                <option value="Dermatology">Dermatology</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Specialization</label>
                            <input type="text" name="specialization" id="spec_input" required>
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%;">Update Details</button>
                    </form>
                </div>
            </div>

        <?php elseif ($section == 'canteen-menu'): ?>
            <!-- Canteen Menu Management -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Canteen Management</h1>
                    <p>Track live orders and manage hospital food menu</p>
                </div>
            </div>

            <div class="content-section">
                <!-- Live Orders Section -->
                <div class="section-header">
                    <h3 class="section-title">Today's Live Orders</h3>
                </div>
                
                <div style="margin-bottom: 30px;">
                    <?php
                    $today = date('Y-m-d');
                    $active_orders = $conn->query("
                        SELECT co.*, cm.item_name, cm.item_category,
                               COALESCE(pp.name, r.name) as pname
                        FROM canteen_orders co
                        JOIN canteen_menu cm ON co.menu_id = cm.menu_id
                        JOIN users u ON co.patient_id = u.user_id
                        LEFT JOIN registrations r ON u.registration_id = r.registration_id
                        LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
                        WHERE co.order_status IN ('Placed', 'Preparing')
                        ORDER BY co.created_at DESC
                    ");
                    if ($active_orders && $active_orders->num_rows > 0):
                    ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>#Order</th>
                                    <th>Patient</th>
                                    <th>Item</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($o = $active_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $o['order_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($o['pname']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($o['item_name']); ?><br>
                                            <small style="color:var(--text-gray);"><?php echo $o['item_category']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo ($o['order_status'] == 'Placed' ? 'pending' : 'active'); ?>">
                                                <?php echo $o['order_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                                <?php if ($o['order_status'] == 'Placed'): ?>
                                                    <button type="submit" name="new_status" value="Preparing" class="btn btn-primary" style="font-size: 11px; padding: 5px 10px;">Prepare</button>
                                                <?php else: ?>
                                                    <button type="submit" name="new_status" value="Delivered" class="btn btn-success" style="font-size: 11px; padding: 5px 10px;">Deliver</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="placeholder-section" style="padding: 30px;">
                            <i class="fas fa-receipt"></i>
                            <p>No active orders for today yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Menu Management Section -->
                <div class="section-header" style="margin-top: 50px;">
                    <h3 class="section-title">Food Menu Management</h3>
                    <button onclick="openMenuModal()" class="btn btn-success"><i class="fas fa-plus"></i> Add Food Item</button>
                </div>

                <?php
                $menu_items = $conn->query("SELECT * FROM canteen_menu ORDER BY item_category, item_name");
                if ($menu_items && $menu_items->num_rows > 0):
                ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($m = $menu_items->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($m['item_name']); ?></strong><br>
                                        <small style="color:var(--text-gray);"><?php echo htmlspecialchars($m['diet_type']); ?></small>
                                    </td>
                                    <td><?php echo $m['item_category']; ?></td>
                                    <td><strong style="color:var(--primary-blue);">₹<?php echo number_format($m['price'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($m['availability'] == 'Available' ? 'active' : 'rejected'); ?>">
                                            <?php echo $m['availability']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap: 8px;">
                                            <button onclick='editMenuItem(<?php echo json_encode($m); ?>)' class="btn btn-primary" style="font-size: 11px; padding: 5px 10px;"><i class="fas fa-edit"></i></button>
                                            <form method="POST" onsubmit="return confirm('Delete this item?')">
                                                <input type="hidden" name="action" value="delete_menu_item">
                                                <input type="hidden" name="menu_id" value="<?php echo $m['menu_id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 5px 10px;"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-utensils"></i>
                        <p>No food items found. Start by adding to your menu.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Canteen Menu Modal -->
            <div id="canteenMenuModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('canteenMenuModal')">&times;</span>
                    <h3 id="menuModalTitle" style="margin-bottom: 25px;">Add Food Item</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_menu_item">
                        <input type="hidden" name="menu_id" id="item_id">
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Food Item Name</label>
                            <input type="text" name="food_name" id="item_name" required>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="meal_category" id="item_cat">
                                    <option>Morning / Breakfast</option>
                                    <option>Lunch</option>
                                    <option>Evening Snacks</option>
                                    <option>Dinner</option>
                                    <option>Night Food</option>
                                    <option>Other Food Items</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Price (₹)</label>
                                <input type="number" step="0.01" name="price" id="item_price" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Diet Type</label>
                                <select name="diet_type" id="item_diet">
                                    <option value="Normal">Normal</option>
                                    <option value="Diabetic">Diabetic</option>
                                    <option value="Low-Salt">Low-Salt</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="availability" id="item_avail">
                                    <option>Available</option>
                                    <option>Out of Stock</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Short Description</label>
                            <textarea name="description" id="item_desc" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success" style="width: 100%;">Save Menu Item</button>
                    </form>
                </div>
            </div>

        <?php elseif ($section == 'packages'): ?>
            <!-- Health Packages Section -->
            <div style="margin: -40px -50px 40px -50px; background: #1e40af; padding: 60px 50px; text-align: center; color: white;">
                <h1 style="font-size: 42px; font-weight: 800; margin-bottom: 15px;">Health Packages</h1>
                <p style="font-size: 18px; opacity: 0.9; max-width: 600px; margin: 0 auto;">Comprehensive checkups for a healthier you. Book directly.</p>
            </div>

            <div class="content-section" style="background: transparent; border: none; padding: 0;">
                <div class="section-header" style="margin-bottom: 30px;">
                    <h3 class="section-title" style="color: white;">Package Management</h3>
                    <button onclick="openPackageModal()" class="btn btn-success"><i class="fas fa-plus"></i> Create New Package</button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-top: 20px;">
                    <?php
                    $pkgs = $conn->query("SELECT * FROM health_packages ORDER BY created_at DESC");
                    if ($pkgs && $pkgs->num_rows > 0):
                        while($p = $pkgs->fetch_assoc()):
                            // Determine icon based on name
                            $icon = 'fa-file-medical';
                            $icon_bg = 'rgba(16, 185, 129, 0.1)';
                            $icon_color = '#10b981';
                            
                            if (stripos($p['package_name'], 'Basic') !== false) {
                                $icon = 'fa-user-check';
                            } elseif (stripos($p['package_name'], 'Comprehensive') !== false) {
                                $icon = 'fa-heartbeat';
                            } elseif (stripos($p['package_name'], 'Diabetes') !== false) {
                                $icon = 'fa-file-prescription';
                            }
                    ?>
                        <div style="background: #ffffff; border-radius: 24px; padding: 35px; display: flex; flex-direction: column; position: relative; transition: all 0.3s; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 25px;">
                                <div style="width: 50px; height: 50px; background: <?php echo $icon_bg; ?>; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: <?php echo $icon_color; ?>;">
                                    <i class="fas <?php echo $icon; ?>" style="font-size: 22px;"></i>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick='editPackage(<?php echo json_encode($p); ?>)' class="btn" style="background: #f1f5f9; color: #64748b; font-size: 11px; padding: 8px;"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this package?')">
                                        <input type="hidden" name="action" value="delete_package">
                                        <input type="hidden" name="package_id" value="<?php echo $p['package_id']; ?>">
                                        <button type="submit" class="btn" style="background: #fee2e2; color: #ef4444; font-size: 11px; padding: 8px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>

                            <h3 style="margin: 0 0 10px; font-size: 22px; color: #1e293b; font-weight: 700;"><?php echo htmlspecialchars($p['package_name']); ?></h3>
                            <p style="font-size: 14px; color: #64748b; margin-bottom: 25px; line-height: 1.6;"><?php echo htmlspecialchars($p['package_description']); ?></p>

                            <div style="margin-bottom: 30px; flex-grow: 1;">
                                <h4 style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 15px; font-weight: 800;">Includes:</h4>
                                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                                    <?php 
                                    $tests = explode(',', $p['included_tests']);
                                    foreach($tests as $t): 
                                    ?>
                                        <li style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 10px;">
                                            <i class="fas fa-check" style="color: #10b981; font-size: 12px;"></i> 
                                            <?php echo trim(htmlspecialchars($t)); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div style="display: flex; align-items: center; gap: 15px; margin-top: auto; padding-top: 25px; border-top: 1px solid #f1f5f9;">
                                <div style="font-size: 30px; font-weight: 800; color: #1e293b;">₹<?php echo number_format($p['discounted_price'], 0); ?></div>
                                <div style="font-size: 16px; text-decoration: line-through; color: #94a3b8; font-weight: 500;">₹<?php echo number_format($p['original_price'], 0); ?></div>
                                <div style="font-size: 12px; background: #fee2e2; color: #ef4444; padding: 4px 10px; border-radius: 6px; font-weight: 700;"><?php echo $p['discount_percentage']; ?>% OFF</div>
                            </div>
                            
                            <button class="btn btn-primary" style="width: 100%; margin-top: 25px; padding: 12px; font-weight: 700; background: #1e40af; border-radius: 12px;">Select Package</button>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="placeholder-section" style="grid-column: 1/-1; background: rgba(255,255,255,0.02); border-radius: 20px; padding: 60px;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                            <h3 style="color: white;">No Packages Found</h3>
                            <p>Start by creating a new health checkup package.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Health Package Modal -->
            <div id="packageModal" class="modal">
                <div class="modal-content" style="width: 600px;">
                    <span class="close-modal" onclick="closeModal('packageModal')">&times;</span>
                    <h3 id="pkgModalTitle" style="margin-bottom: 25px;">Create New Package</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_package">
                        <input type="hidden" name="package_id" id="pkg_id">
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Package Name</label>
                            <input type="text" name="package_name" id="pkg_name" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Short Description</label>
                            <textarea name="description" id="pkg_desc" rows="2" required></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Included Tests (Comma separated)</label>
                            <input type="text" name="included_tests" id="pkg_tests" placeholder="e.g. CBC, Lipid Profile, X-Ray" required>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Actual Price (₹)</label>
                                <input type="number" name="actual_price" id="pkg_actual" oninput="calculateDiscount()" required>
                            </div>
                            <div class="form-group">
                                <label>Discounted Price (₹)</label>
                                <input type="number" name="discount_price" id="pkg_discount" oninput="calculateDiscount()" required>
                            </div>
                            <div class="form-group">
                                <label>Discount %</label>
                                <input type="number" name="discount_percent" id="pkg_percent" readonly>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Status</label>
                            <select name="status" id="pkg_status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success" style="width: 100%;">Save Package Details</button>
                    </form>
                </div>
            </div>

        <?php elseif ($section == 'ambulance'): ?>
            <!-- Ambulance Service -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Ambulance Emergency Service</h1>
                    <p>Manage emergency contact numbers and ambulance availability</p>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Emergency Contacts</h3>
                    <button onclick="openModal('ambulanceModal')" class="btn btn-success"><i class="fas fa-plus"></i> Add Contact</button>
                </div>
                
                <?php
                $ambulances = $conn->query("SELECT * FROM ambulance_contacts ORDER BY availability ASC, created_at DESC");
                if ($ambulances && $ambulances->num_rows > 0):
                ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Driver Name</th>
                                <th>Phone Number</th>
                                <th>Vehicle Info</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($amb = $ambulances->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($amb['driver_name']); ?></strong></td>
                                    <td><span style="color:var(--primary-blue); font-weight:600;"><?php echo htmlspecialchars($amb['phone_number']); ?></span></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($amb['vehicle_type']); ?></small><br>
                                        <strong><?php echo htmlspecialchars($amb['vehicle_number']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($amb['location']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($amb['availability'] == 'Available' ? 'active' : ($amb['availability'] == 'On Duty' ? 'pending' : 'rejected')); ?>">
                                            <?php echo $amb['availability']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this contact?')">
                                            <input type="hidden" name="action" value="delete_ambulance">
                                            <input type="hidden" name="contact_id" value="<?php echo $amb['contact_id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 5px 10px;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-ambulance"></i>
                        <h3>No Active Ambulances</h3>
                        <p>Start by adding emergency ambulance contacts.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Ambulance Modal -->
            <div id="ambulanceModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('ambulanceModal')">&times;</span>
                    <h3 style="margin-bottom: 25px;">Add New Emergency Contact</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_ambulance">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Driver Name</label>
                            <input type="text" name="driver_name" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Phone Number</label>
                            <input type="text" name="phone_number" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Vehicle Number</label>
                            <input type="text" name="vehicle_number" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type">
                                <option value="Basic Life Support">Basic Life Support</option>
                                <option value="Advanced Life Support">Advanced Life Support</option>
                                <option value="Patient Transport">Patient Transport</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Base Location</label>
                            <input type="text" name="location" required>
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%;">Save Contact</button>
                    </form>
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

        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        function openScheduleModal(id, name) {
            document.getElementById('sched_doc_id').value = id;
            document.getElementById('scheduleTitle').innerText = 'Schedule for Dr. ' + name;
            openModal('scheduleModal');
        }

        function openDeptModal(id, dept, spec) {
            document.getElementById('dept_doc_id').value = id;
            document.getElementById('dept_select').value = dept;
            document.getElementById('spec_input').value = spec;
            openModal('deptModal');
        }

        function openMenuModal() {
            document.getElementById('menuModalTitle').innerText = 'Add Food Item';
            document.getElementById('item_id').value = '';
            document.getElementById('item_name').value = '';
            document.getElementById('item_price').value = '';
            document.getElementById('item_desc').value = '';
            openModal('canteenMenuModal');
        }

        function editMenuItem(item) {
            document.getElementById('menuModalTitle').innerText = 'Edit Food Item';
            document.getElementById('item_id').value = item.menu_id;
            document.getElementById('item_name').value = item.item_name;
            document.getElementById('item_cat').value = item.item_category;
            document.getElementById('item_diet').value = item.diet_type;
            document.getElementById('item_price').value = item.price;
            document.getElementById('item_avail').value = item.availability;
            document.getElementById('item_desc').value = item.description;
            openModal('canteenMenuModal');
        }

        function openPackageModal() {
            document.getElementById('pkgModalTitle').innerText = 'Create New Package';
            document.getElementById('pkg_id').value = '';
            document.getElementById('pkg_name').value = '';
            document.getElementById('pkg_desc').value = '';
            document.getElementById('pkg_tests').value = '';
            document.getElementById('pkg_actual').value = '';
            document.getElementById('pkg_discount').value = '';
            document.getElementById('pkg_percent').value = '';
            openModal('packageModal');
        }

        function editPackage(p) {
            document.getElementById('pkgModalTitle').innerText = 'Edit Health Package';
            document.getElementById('pkg_id').value = p.package_id;
            document.getElementById('pkg_name').value = p.package_name;
            document.getElementById('pkg_desc').value = p.package_description;
            document.getElementById('pkg_tests').value = p.included_tests;
            document.getElementById('pkg_actual').value = p.original_price;
            document.getElementById('pkg_discount').value = p.discounted_price;
            document.getElementById('pkg_percent').value = p.discount_percentage;
            document.getElementById('pkg_status').value = p.status;
            openModal('packageModal');
        }

        function calculateDiscount() {
            const actual = parseFloat(document.getElementById('pkg_actual').value) || 0;
            const discountP = parseFloat(document.getElementById('pkg_discount').value) || 0;
            if (actual > 0) {
                const percent = Math.round(((actual - discountP) / actual) * 100);
                document.getElementById('pkg_percent').value = percent > 0 ? percent : 0;
            } else {
                document.getElementById('pkg_percent').value = 0;
            }
        }
    </script>
</body>
</html>
