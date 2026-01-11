<?php
session_start();
include 'includes/db_connect.php';

// Authentication Check
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$staff_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);

// Handle POST Requests
$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. New Patient Registration
    if (isset($_POST['action']) && $_POST['action'] === 'register_patient') {
        $p_name = mysqli_real_escape_string($conn, $_POST['p_name']);
        $p_phone = mysqli_real_escape_string($conn, $_POST['p_phone']);
        $p_dept = mysqli_real_escape_string($conn, $_POST['p_dept']);
        $p_age = mysqli_real_escape_string($conn, $_POST['p_age']);
        $p_gender = mysqli_real_escape_string($conn, $_POST['p_gender']);

        // Generate Patient ID
        $year = date("Y");
        $rand = rand(1000, 9999);
        $p_code = "HC-P-{$year}-{$rand}";
        
        // Create User Account (Optional, usually for Portal Access)
        // For Quick Reg, we might just store in patient_profiles linked to a new user
        // Password = Phone number as default
        $password = password_hash($p_phone, PASSWORD_DEFAULT);
        
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO users (username, password, role, status) VALUES ('$p_code', '$password', 'patient', 'Active')");
            $new_user_id = $conn->insert_id;
            
            $conn->query("INSERT INTO patient_profiles (user_id, patient_code, name, phone, gender, department_visit) VALUES ($new_user_id, '$p_code', '$p_name', '$p_phone', '$p_gender', '$p_dept')");
            $new_patient_id = $conn->insert_id;
            
            // Auto Book Appointment if desired? Let's just register.
            $msg = "Patient Registered! ID: <strong>$p_code</strong>";
            $msg_type = "success";
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error: " . $e->getMessage();
            $msg_type = "error";
        }
    }

    // 2. Book Appointment
    if (isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
        $p_name = mysqli_real_escape_string($conn, $_POST['b_name']);
        $p_dept = mysqli_real_escape_string($conn, $_POST['b_dept']);
        $b_date = mysqli_real_escape_string($conn, $_POST['b_date']); // YYYY-MM-DD
        $b_time = mysqli_real_escape_string($conn, $_POST['b_time']); // HH:MM
        
        $full_datetime = $b_date . ' ' . $b_time;
        $token = "TK-" . rand(100, 999);
        
        if ($conn->query("INSERT INTO appointments (patient_name, department, appointment_date, token_no, status) VALUES ('$p_name', '$p_dept', '$full_datetime', '$token', 'Scheduled')")) {
            $msg = "Appointment Booked! Token: $token";
            $msg_type = "success";
        } else {
            $msg = "Error Booking: " . $conn->error;
            $msg_type = "error";
        }
    }

    // 3. Update Status (Check In / Check Out)
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $appt_id = $_POST['appt_id'];
        $new_status = $_POST['status'];
        $conn->query("UPDATE appointments SET status = '$new_status' WHERE appointment_id = $appt_id");
        $msg = "Status Updated to $new_status";
        $msg_type = "success";
    }

    // 4. Cancel / Reschedule
    if (isset($_POST['action']) && $_POST['action'] === 'reschedule') {
        $appt_id = $_POST['appt_id'];
        $new_date = $_POST['new_date'];
        $new_time = $_POST['new_time'];
        $full_datetime = $new_date . ' ' . $new_time;
        
        $conn->query("UPDATE appointments SET appointment_date = '$full_datetime', status = 'Rescheduled' WHERE appointment_id = $appt_id");
        $msg = "Appointment Rescheduled.";
        $msg_type = "success";
    }
}

// Fetch Data
$today = date('Y-m-d');
$queue_result = $conn->query("SELECT * FROM appointments WHERE DATE(appointment_date) = '$today' ORDER BY appointment_date ASC");
$calendar_result = $conn->query("SELECT * FROM appointments WHERE appointment_date >= '$today' ORDER BY appointment_date ASC LIMIT 10");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        /* Inherit Styles from previous dashboard.css + specific overrides */
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent-blue: #3b82f6;
            --border-soft: rgba(255, 255, 255, 0.05);
        }

        .reception-top-bar { background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .secondary-nav { background: #0f172a; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .btn-logout-alt { background: transparent; border: 1px solid #3b82f6; color: #fff; padding: 8px 25px; border-radius: 20px; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .btn-logout-alt:hover { background: #3b82f6; }
        .dashboard-body { display: grid; grid-template-columns: 260px 1fr; height: calc(100vh - 140px); background: #020617; }
        .side-nav { background: #020617; padding: 20px 0; border-right: 1px solid var(--border-soft); }
        .nav-item { display: flex; align-items: center; padding: 15px 30px; color: #94a3b8; text-decoration: none; font-size: 14px; gap: 15px; transition: 0.3s; }
        .nav-item.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-left: 4px solid #3b82f6; }
        .main-ops { padding: 40px; overflow-y: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card-new { background: #0f172a; padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); }
        .stat-card-new h2 { font-size: 24px; color: #3b82f6; margin-bottom: 5px; }
        
        /* Queue Table */
        .queue-table { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .queue-table th { text-align: left; padding: 15px; color: #64748b; font-size: 12px; border-bottom: 1px solid var(--border-soft); }
        .queue-table td { padding: 18px 15px; color: #cbd5e1; font-size: 14px; border-bottom: 1px solid var(--border-soft); }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        
        .pill-Scheduled { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .pill-Waiting { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .pill-Checked-In { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .pill-Completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .pill-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .btn-check { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; border: none; cursor: pointer; color: white; display: inline-flex; align-items: center; gap: 5px; }
        .btn-green { background: #10b981; }
        .btn-red { background: #ef4444; }
        .btn-blue { background: #3b82f6; }
        .btn-orange { background: #f59e0b; }

        /* Calendar Widget */
        .calendar-widget { background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 20px; margin-top: 30px; }
        .quick-actions-bar { background: rgba(30, 41, 59, 0.4); padding: 30px; border-radius: 12px; border: 1px solid var(--border-soft); }
        .btn-action-main { background: rgba(255,255,255,0.03); border: 1px solid var(--border-soft); color: #fff; padding: 10px 25px; border-radius: 20px; cursor: pointer; font-size: 13px; transition: 0.3s; margin-right: 10px; }
        .btn-action-main:hover { border-color: #3b82f6; color: #3b82f6; }

        /* Modals */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-box { background: #0f172a; border: 1px solid var(--border-soft); width: 90%; max-width: 500px; padding: 30px; border-radius: 20px; }
        .form-group-staff { margin-bottom: 15px; }
        .form-group-staff label { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 5px; }
        .form-group-staff input, .form-group-staff select { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border-soft); padding: 10px; border-radius: 8px; color: #fff; }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
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

    <!-- Secondary Nav -->
    <div class="secondary-nav">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: #3b82f6; color:#fff; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold;">+</div>
            <h2 style="color:#fff; font-size:20px;">HealCare</h2>
        </div>
        <div style="display: flex; align-items: center; gap: 30px;">
            <span class="staff-label"><?php echo $staff_name; ?></span>
            <a href="logout.php" class="btn-logout-alt">Log Out</a>
        </div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <a href="#" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="#" class="nav-item"><i class="fas fa-calendar-alt"></i> Reception / Queue</a>
            <a href="#" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Patient Billing</a>
            <a href="reports_manager.php" class="nav-item"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <?php if($msg): ?>
                <div style="background: <?php echo $msg_type == 'success' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; color: #fff; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 30px;">
                <h1 style="color:#fff; font-size: 28px;">Reception Dashboard</h1>
                <p style="color:#64748b; font-size:14px;">Manage patient flow and appointments.</p>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-bar">
                <h4 style="color:#fff; font-size:16px; margin-bottom: 15px;">Quick Actions</h4>
                <div style="display: flex; flex-wrap: wrap;">
                    <button class="btn-action-main" onclick="openModal('regModal')"><i class="fas fa-user-plus"></i> New Registration</button>
                    <button class="btn-action-main" onclick="openModal('bookModal')"><i class="fas fa-calendar-plus"></i> Book Appointment</button>
                    <button class="btn-action-main"><i class="fas fa-file-invoice"></i> Generate Bill</button>
                    <button class="btn-action-main" onclick="location.href='reports_manager.php?view=repository'"><i class="fas fa-file-export"></i> Archive Daily Report</button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
                <!-- Live Queue -->
                <div>
                    <h3 style="color:#fff; margin-bottom: 20px;">Today's Queue & Appointments</h3>
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th>Token</th>
                                <th>Patient Name</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($queue_result->num_rows > 0): ?>
                                <?php while($appt = $queue_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($appt['token_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appt['appointment_date'])); ?></td>
                                        <td><span class="status-pill pill-<?php echo str_replace(' ', '-', $appt['status']); ?>"><?php echo $appt['status']; ?></span></td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <!-- Check In / Out Logic -->
                                                <?php if($appt['status'] == 'Scheduled'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                        <input type="hidden" name="status" value="Checked-In">
                                                        <button class="btn-check btn-green"><i class="fas fa-check"></i> Check In</button>
                                                    </form>
                                                    <button class="btn-check btn-orange" onclick="openReschedule(<?php echo $appt['appointment_id']; ?>)"><i class="fas fa-clock"></i></button>
                                                <?php elseif($appt['status'] == 'Checked-In'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                        <input type="hidden" name="status" value="Completed">
                                                        <button class="btn-check btn-blue"><i class="fas fa-sign-out-alt"></i> Check Out</button>
                                                    </form>
                                                <?php elseif($appt['status'] == 'Completed'): ?>
                                                    <span style="color: #10b981; font-size: 11px;"><i class="fas fa-check-double"></i> Done</span>
                                                <?php endif; ?>
                                                
                                                <?php if($appt['status'] != 'Cancelled' && $appt['status'] != 'Completed'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appt_id" value="<?php echo $appt['appointment_id']; ?>">
                                                        <input type="hidden" name="status" value="Cancelled">
                                                        <button class="btn-check btn-red" title="Cancel"><i class="fas fa-times"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding: 20px;">No appointments for today.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Calendar Widget -->
                <div class="calendar-widget">
                    <h4 style="color: #fff; margin-bottom: 15px;"><i class="fas fa-calendar-alt"></i> Upcoming Schedule</h4>
                    <div style="font-size: 12px; color: #cbd5e1; display: flex; flex-direction: column; gap: 10px;">
                        <?php if ($calendar_result->num_rows > 0): ?>
                            <?php while($cal = $calendar_result->fetch_assoc()): ?>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-soft); padding-bottom: 5px;">
                                    <span><?php echo date('M d, h:i A', strtotime($cal['appointment_date'])); ?> - <?php echo htmlspecialchars($cal['patient_name']); ?></span>
                                    <span style="color: #3b82f6;"><?php echo htmlspecialchars($cal['department']); ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No upcoming appointments.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Registration Modal -->
    <div id="regModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; margin-bottom: 25px;">
                <h3 style="color: #fff;">New Patient Registration</h3>
                <i class="fas fa-times" style="cursor: pointer; color: #64748b;" onclick="closeModal('regModal')"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="register_patient">
                <div class="form-group-staff"><label>Full Name</label><input type="text" name="p_name" required></div>
                <div class="form-group-staff"><label>Phone Number</label><input type="text" name="p_phone" required></div>
                <div class="form-group-staff"><label>Gender</label><select name="p_gender"><option>Male</option><option>Female</option></select></div>
                <div class="form-group-staff"><label>Age</label><input type="number" name="p_age"></div>
                <div class="form-group-staff"><label>Initial Dept</label><select name="p_dept"><option>General Medicine</option><option>ENT</option><option>Dental</option></select></div>
                <button type="submit" style="width: 100%; padding: 12px; background: #3b82f6; border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer;">Register Patient</button>
            </form>
        </div>
    </div>

    <!-- Book Appointment Modal -->
    <div id="bookModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; margin-bottom: 25px;">
                <h3 style="color: #fff;">Book Appointment</h3>
                <i class="fas fa-times" style="cursor: pointer; color: #64748b;" onclick="closeModal('bookModal')"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="book_appointment">
                <div class="form-group-staff"><label>Patient Name</label><input type="text" name="b_name" required placeholder="Search or Type Name"></div>
                <div class="form-group-staff"><label>Department</label><select name="b_dept"><option>General Medicine</option><option>ENT</option><option>Dental</option></select></div>
                <div class="form-group-staff"><label>Date</label><input type="date" name="b_date" required min="<?php echo date('Y-m-d'); ?>"></div>
                <div class="form-group-staff"><label>Time</label><input type="time" name="b_time" required></div>
                <button type="submit" style="width: 100%; padding: 12px; background: #10b981; border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer;">Confirm Booking</button>
            </form>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="color: white; margin-bottom: 20px;">Reschedule Appointment</h3>
            <form method="POST">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" id="resch_appt_id" name="appt_id">
                <div class="form-group-staff"><label>New Date</label><input type="date" name="new_date" required></div>
                <div class="form-group-staff"><label>New Time</label><input type="time" name="new_time" required></div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeModal('rescheduleModal')" style="flex:1; padding: 10px; background: #333; color: white; border: none; border-radius: 8px;">Cancel</button>
                    <button type="submit" style="flex:1; padding: 10px; background: #f59e0b; color: white; border: none; border-radius: 8px;">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function openReschedule(id) {
            document.getElementById('resch_appt_id').value = id;
            openModal('rescheduleModal');
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
