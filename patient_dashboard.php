<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check if profile is complete
$res = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $user_id");
$profile_exists = ($res->num_rows > 0);
$profile = $profile_exists ? $res->fetch_assoc() : null;

// Fetch medical history and visit records
$medical_records = [];
if ($profile_exists) {
    // Current visit records from medical_records table
    $records_res = $conn->query("
        SELECT mr.*, r.name as doctor_name, 
        (SELECT medicine_details FROM prescriptions WHERE patient_id = $user_id AND (DATE(prescription_date) = DATE(mr.created_at) OR appointment_id = mr.appointment_id) LIMIT 1) as prescription,
        (SELECT GROUP_CONCAT(test_name SEPARATOR ', ') FROM lab_orders WHERE appointment_id = mr.appointment_id) as lab_tests
        FROM medical_records mr
        LEFT JOIN users u ON mr.doctor_id = u.user_id
        LEFT JOIN registrations r ON u.registration_id = r.registration_id
        WHERE mr.patient_id = $user_id 
        ORDER BY mr.created_at DESC
    ");
    while ($row = $records_res->fetch_assoc()) {
        $medical_records[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HealCare</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js for Health Analysis -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Styles -->
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* New Dash Specific Styles */
        .notification-bell {
            position: relative;
            cursor: pointer;
            margin-right: 20px;
        }
        .bell-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 50%;
        }
        .search-container {
            flex: 1;
            max-width: 400px;
            margin: 0 40px;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 10px 20px;
            padding-left: 45px;
            border-radius: 25px;
            border: 1px solid var(--border-color);
            background: rgba(255,255,255,0.05);
            color: white;
        }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
        }
        .chart-container {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-top: 30px;
        }
        .canteen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .food-item {
            background: rgba(255,255,255,0.03);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid transparent;
        }
        .food-item:hover {
            border-color: var(--primary-blue);
            background: rgba(59, 130, 246, 0.1);
        }
        .food-item i { font-size: 24px; margin-bottom: 8px; display: block; color: var(--primary-blue); }
        .food-item span { font-size: 13px; font-weight: 500; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-online { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-offline { background: rgba(156, 163, 175, 0.1); color: #9ca3af; }
        .status-Pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-Requested { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-Scheduled { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-Approved, .status-Confirmed { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    </style>
</head>
<body>
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

    <!-- Secondary Navy Header -->
    <header class="secondary-header">
        <div class="brand-section">
            <div class="brand-icon">+</div>
            <div class="brand-name">HealCare</div>
        </div>
        <div class="user-controls">
            <?php 
            // Use name from profile if available, else session, else username
            $display_name = $profile['name'] ?? $_SESSION['full_name'] ?? $username;
            ?>
            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($display_name); ?></strong></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Patient Dashboard</h1>
                <p>Welcome back! Here's your real-time health and hospital status.</p>
            </div>

            <!-- Enhanced Stats Overview Cards -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <span class="stat-value">02</span>
                    <span class="stat-label">Upcoming Appts</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">15</span>
                    <span class="stat-label">Past Visits</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value" style="color: #f59e0b;">#12</span>
                    <span class="stat-label">Queue Status</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value" style="color: #10b981;">B-204</span>
                    <span class="stat-label">Bed Status (Allocated)</span>
                </div>
            </div>

            <!-- Health Trends Chart -->
            <div class="chart-container">
                <div class="section-head">
                    <h3>Health Analysis Trends</h3>
                </div>
                <canvas id="healthChart" height="100"></canvas>
            </div>

            <!-- Two Column Layout: Main Ops & Side Info -->
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 30px;">
                
                <!-- Appointments & Medical History -->
                <div class="content-section">
                    <div class="section-head">
                        <h3>Next Appointment</h3>
                    </div>
                    <div class="appointment-list">
                        <?php
                        // Fetch next upcoming appointment
                        $today_dt = date('Y-m-d H:i:s');
                        $appt_sql = "SELECT a.*, d.specialization, u.username as doc_name, r.name as real_doc_name 
                                     FROM appointments a 
                                     LEFT JOIN users u ON a.doctor_id = u.user_id 
                                     LEFT JOIN doctors d ON u.user_id = d.user_id 
                                     LEFT JOIN registrations r ON u.registration_id = r.registration_id
                                     WHERE a.patient_id = $user_id AND a.status IN ('Scheduled', 'Approved', 'Pending', 'Requested', 'Confirmed') AND a.appointment_date >= '$today_dt'
                                     ORDER BY a.appointment_date ASC LIMIT 1";
                        
                        $appt_res = $conn->query($appt_sql);
                        
                        if ($appt_res && $appt_res->num_rows > 0):
                            $appt = $appt_res->fetch_assoc();
                            $doc_display_name = $appt['real_doc_name'] ?? ('Dr. ' . $appt['doc_name']);
                            $appt_time = date('M d, Y \a\t h:i A', strtotime($appt['appointment_date']));
                            $specialty = $appt['specialization'] ?? $appt['department'] ?? 'General';
                        ?>
                        <div class="appointment-item">
                            <div class="doc-info">
                                <h4><?php echo htmlspecialchars($doc_display_name); ?> <span class="status-badge status-<?php echo $appt['status']; ?>"><?php echo htmlspecialchars($appt['status']); ?></span></h4>
                                <p><?php echo htmlspecialchars($specialty); ?> • <?php echo $appt_time; ?></p>
                                <p style="font-size: 12px; margin-top: 5px;"><i class="fas fa-info-circle"></i> Token: <?php echo htmlspecialchars($appt['queue_number'] ?? 'N/A'); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <a href="my_appointments.php" style="display: block; font-size: 13px; color: #4fc3f7; text-decoration: none; margin-bottom: 10px;">View All</a>
                                <a href="cancel_booking.php?id=<?php echo $appt['appointment_id']; ?>" class="action-cancel">Cancel</a>
                            </div>
                        </div>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center; color: #aaa;">
                                <p>No upcoming appointments.</p>
                                <a href="appointment_form.php" style="color: #4fc3f7; text-decoration: none; font-size: 13px; font-weight: 600;">Book Now</a>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- Lab Reports Download -->
                <div style="margin-top: 40px;">
                    <div class="section-head"><h3>Recent Lab Reports</h3></div>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px;">
                            <span><i class="fas fa-microscope" style="margin-right: 10px;"></i> Blood Test Report</span>
                            <a href="#" style="color: #4fc3f7; font-size: 14px;"><i class="fas fa-download"></i> PDF</a>
                        </div>
                    </div>
                </div>

                <!-- Visit History & Advice Section -->
                <div style="margin-top: 40px;">
                    <div class="section-head"><h3>Visit History & Doctor's Advice</h3></div>
                    <?php if(!empty($medical_records)): ?>
                        <?php foreach($medical_records as $record): ?>
                            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <div>
                                        <h4 style="color: #4fc3f7; margin-bottom: 5px;"><?php echo htmlspecialchars($record['diagnosis']); ?></h4>
                                        <p style="font-size: 12px; color: #94a3b8;">Consulted with <?php echo htmlspecialchars($record['doctor_name']); ?> • <?php echo date('M d, Y', strtotime($record['created_at'])); ?></p>
                                    </div>
                                    <span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; align-self: flex-start;">Visit Completed</span>
                                </div>
                                
                                <?php if(!empty($record['special_notes'])): ?>
                                    <div style="background: rgba(59, 130, 246, 0.05); border-left: 4px solid #3b82f6; padding: 15px; border-radius: 4px; margin-top: 10px;">
                                        <strong style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #3b82f6; margin-bottom: 5px;">Special Advice for you:</strong>
                                        <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($record['special_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if(!empty($record['prescription'])): ?>
                                    <div style="background: rgba(16, 185, 129, 0.05); border-left: 4px solid #10b981; padding: 15px; border-radius: 4px; margin-top: 10px;">
                                        <strong style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #10b981; margin-bottom: 5px;"><i class="fas fa-pills"></i> Prescribed Medication:</strong>
                                        <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($record['prescription'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if(!empty($record['lab_tests'])): ?>
                                    <div style="background: rgba(79, 195, 247, 0.05); border-left: 4px solid #4fc3f7; padding: 15px; border-radius: 4px; margin-top: 10px;">
                                        <strong style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #4fc3f7; margin-bottom: 5px;"><i class="fas fa-flask"></i> Requested Lab Tests:</strong>
                                        <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5;"><?php echo htmlspecialchars($record['lab_tests']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; color: #aaa;">
                            <p>No medical visit history available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

                <!-- Canteen & Notifications -->
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <!-- Canteen Status Card -->
                    <div style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 25px; border-radius: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="font-size: 16px; font-weight: 700;">Local Canteen</h3>
                            <a href="canteen.php" style="font-size: 12px; color: #4fc3f7; text-decoration: none;">Order Food</a>
                        </div>
                        
                        <?php
                        $latest_canteen = $conn->query("
                            SELECT co.*, cm.item_name 
                            FROM canteen_orders co
                            JOIN canteen_menu cm ON co.menu_id = cm.menu_id
                            WHERE co.patient_id = $user_id
                            ORDER BY co.created_at DESC LIMIT 1
                        ");
                        if ($latest_canteen && $latest_canteen->num_rows > 0):
                            $c_order = $latest_canteen->fetch_assoc();
                            $c_progress = 20;
                            if($c_order['order_status'] == 'Preparing') $c_progress = 60;
                            if($c_order['order_status'] == 'Delivered') $c_progress = 100;
                        ?>
                            <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid rgba(79, 195, 247, 0.1);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span style="font-size: 13px; color: #fff; font-weight: 600;"><?php echo htmlspecialchars($c_order['item_name']); ?></span>
                                    <span class="status-badge status-<?php echo $c_order['order_status']; ?>" style="font-size: 10px;"><?php echo strtoupper($c_order['order_status']); ?></span>
                                </div>
                                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden; margin-bottom: 8px;">
                                    <div style="width: <?php echo $c_progress; ?>%; height: 100%; background: #4fc3f7; box-shadow: 0 0 10px rgba(79, 195, 247, 0.4);"></div>
                                </div>
                                <p style="font-size: 11px; color: #94a3b8; margin: 0;">Last updated: <?php echo date('h:i A', strtotime($c_order['created_at'])); ?></p>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: #64748b; font-size: 13px;">
                                <i class="fas fa-utensils" style="font-size: 24px; margin-bottom: 10px; display: block; opacity: 0.3;"></i>
                                No active food orders.
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="#" style="padding: 10px 20px; background: rgba(79, 195, 247, 0.1); border: 1px solid #4fc3f7; border-radius: 8px; color: #4fc3f7; text-decoration: none; font-size: 13px; font-weight: 600; text-align: center;">Submit Feedback</a>
                </div>
            </div>

        </main>
    </div>

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>