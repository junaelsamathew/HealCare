<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Fetch Lab Staff Details
$res = $conn->query("SELECT * FROM lab_staff WHERE user_id = $user_id");
$lab = $res->fetch_assoc();
$lab_type = $lab['lab_type'] ?? 'Blood / Pathology Lab';

// Derive search pattern for loose matching
$search_pattern = "%" . $lab_type . "%";
if (stripos($lab_type, 'Pathology') !== false) {
    $search_pattern = '%Pathology%';
} elseif (stripos($lab_type, 'X-Ray') !== false || stripos($lab_type, 'Imaging') !== false) {
    $search_pattern = '%Imaging%'; // Matches 'X-Ray / Imaging Lab'
    if (stripos($lab_type, 'X-Ray') !== false) $search_pattern = '%X-Ray%';
} elseif (stripos($lab_type, 'Diagnostic') !== false) {
    $search_pattern = '%Diagnostic%';
} elseif (stripos($lab_type, 'Ultrasound') !== false) {
    $search_pattern = '%Ultrasound%';
}

// Fetch current name dynamically
$name_q = $conn->query("SELECT r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.user_id = $user_id");
$name_row = $name_q->fetch_assoc();
$display_name = $name_row['name'] ?? ($_SESSION['full_name'] ?? $_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Technician Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
<<<<<<< HEAD
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
=======
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
    <style>
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent-blue: #4fc3f7;
            --border-soft: rgba(255, 255, 255, 0.05);
        }

        .reception-top-bar { background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .secondary-nav { background: #0f172a; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .dashboard-body { display: grid; grid-template-columns: 260px 1fr; height: calc(100vh - 140px); background: #020617; }
        .side-nav { background: #020617; padding: 20px 0; border-right: 1px solid var(--border-soft); }
        .nav-item { display: flex; align-items: center; padding: 15px 30px; color: #94a3b8; text-decoration: none; font-size: 14px; gap: 15px; transition: 0.3s; }
        .nav-item.active { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; border-left: 4px solid #4fc3f7; }
        .main-ops { padding: 40px; overflow-y: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card-new { background: #0f172a; padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); }
        .stat-card-new h2 { font-size: 24px; color: #4fc3f7; margin-bottom: 5px; }

        .test-request-card {
            background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 25px;
            margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;
        }
        .btn-upload { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; border: 1px solid #4fc3f7; padding: 10px 20px; border-radius: 10px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.3s; }
        .btn-upload:hover { background: #4fc3f7; color: #fff; }

        .sample-status-bar { display: flex; gap: 10px; margin-top: 20px; }
        .status-dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.1); position: relative; }
        .status-dot.active { background: #4fc3f7; box-shadow: 0 0 10px #4fc3f7; }
        .status-dot.active::after { content: ''; position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%); font-size: 10px; color: #4fc3f7; font-weight: bold; white-space: nowrap; }
<<<<<<< HEAD
        
        /* Brand Animation */
        .brand-letter {
            display: inline-block;
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .brand-letter.visible {
            opacity: 1;
            transform: translateY(0);
        }
=======
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
    </style>
</head>
<body>

    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
<<<<<<< HEAD
        <a href="index.php" class="logo-main" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img src="images/healcare_logo.jpg" alt="HealCare" style="height: 50px;">
            <span class="animated-brand" style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">HEALCARE HOSPITAL</span>
        </a>
=======
        <h1 style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">+ HEALCARE</h1>
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">EMERGENCY</span>
<<<<<<< HEAD
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">(+91) 953 904 5609</span>
                </div>
            </div>
            
=======
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
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
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

    <div class="secondary-nav">
        <div style="display: flex; align-items: center; gap: 15px;"><div style="background: #4fc3f7; color:#fff; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold;">T</div><h2 style="color:#fff; font-size:20px;">Technician Panel</h2></div>
        <div style="display: flex; align-items: center;"><span class="staff-label" style="color: #94a3b8; font-size: 14px; margin-right: 15px;"><?php echo htmlspecialchars($display_name); ?></span><a href="logout.php" style="color: #94a3b8; text-decoration: none; border: 1px solid #4fc3f7; padding: 5px 20px; border-radius: 20px;">Log Out</a></div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <?php $section = $_GET['section'] ?? 'dashboard'; ?>
            <a href="?section=dashboard" class="nav-item <?php echo $section == 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-vials"></i> Pending Tests</a>
            <a href="?section=conducted" class="nav-item <?php echo $section == 'conducted' ? 'active' : ''; ?>"><i class="fas fa-microscope"></i> Conducted Tests</a>
            <a href="?section=completed" class="nav-item <?php echo $section == 'completed' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Completed Reports</a>
<<<<<<< HEAD
            <a href="?section=patient_history" class="nav-item <?php echo $section == 'patient_history' ? 'active' : ''; ?>"><i class="fas fa-history"></i> Patient History</a>
            <a href="?section=archive" class="nav-item <?php echo $section == 'archive' ? 'active' : ''; ?>"><i class="fas fa-archive"></i> Archive</a>
            <a href="?section=reports" class="nav-item <?php echo $section == 'reports' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Lab Reports</a>
=======
            <a href="?section=archive" class="nav-item <?php echo $section == 'archive' ? 'active' : ''; ?>"><i class="fas fa-archive"></i> Archive</a>
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <?php if (!isset($_GET['section']) || $_GET['section'] == 'dashboard'): ?>
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="color:#fff; font-size: 28px;">Lab Operations: <span style="color:#4fc3f7;"><?php echo htmlspecialchars($lab_type); ?></span></h1>
                        <p style="color:#64748b; font-size:14px;">Managing requests for <?php echo htmlspecialchars($lab_type); ?></p>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-badge status-online">System Online</span>
                    </div>
                </div>

                <?php
                // Fetch Counts
                $q_pending = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE test_type LIKE '$search_pattern' AND status = 'Pending'");
                $pending_count = $q_pending->fetch_assoc()['count'];

                $q_conducted = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE category_id = (SELECT category_id FROM lab_categories WHERE category_name = '$lab_type' LIMIT 1) AND status = 'Conducted'");
                $cond_count = $q_conducted->fetch_assoc()['count'];

<<<<<<< HEAD
                $q_completed = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE category_id = (SELECT category_id FROM lab_categories WHERE category_name = '$lab_type' LIMIT 1) AND status = 'Completed' AND DATE(updated_at) = CURDATE()");

                $completed_today = $q_completed->fetch_assoc()['count'];

                // Greeting Logic
                include_once 'includes/greeting_logic.php';
                ?>

                <!-- Personalized Greeting Banner -->
                <div style="background: linear-gradient(135deg, #0f172a, #1e293b); padding: 30px; border-radius: 16px; border: 1px solid var(--border-soft); margin-bottom: 30px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(79, 195, 247, 0.05); border-radius: 50%; filter: blur(20px);"></div>
                    <div style="position: relative; z-index: 1;">
                        <h2 style="color: #fff; font-size: 24px; margin-bottom: 5px;"><?php echo $greeting; ?>, <?php echo htmlspecialchars($display_name); ?></h2>
                        <p style="color: #64748b; font-size: 14px;">Welcome back. Today you have <strong style="color: #4fc3f7;"><?php echo $pending_count; ?> pending</strong> lab requests to process.</p>
                    </div>
                </div>

=======
                $q_completed = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE test_type LIKE '$search_pattern' AND status = 'Completed' AND DATE(created_at) = CURRENT_DATE");
                $completed_today = $q_completed->fetch_assoc()['count'];
                ?>

>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
                <!-- Quick Archive -->
                <div style="background: linear-gradient(135deg, #0f172a, #1e293b); padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="color: #fff; margin-bottom: 5px; font-size: 16px;"><i class="fas fa-file-upload" style="color: #4fc3f7;"></i> Laboratory Documentation</h3>
                        <p style="color: #64748b; font-size: 12px;">Archive manual test summaries or complex diagnostic reports.</p>
                    </div>
<<<<<<< HEAD
                    <div style="flex-shrink: 0;">
                        <button onclick="openReportModal()" style="background: #4fc3f7; color: #020617; padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 13px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 4px 12px rgba(79, 195, 247, 0.3);">
                            <i class="fas fa-upload"></i> Upload Report
                        </button>
                    </div>
=======
                    <button onclick="openReportModal()" style="background: #4fc3f7; color: #020617; text-decoration: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 12px; border: none; cursor: pointer;">
                        <i class="fas fa-upload"></i> Upload Report
                    </button>
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
                </div>

                <div class="stats-grid">
                    <div class="stat-card-new"><h2><?php echo str_pad($pending_count, 2, '0', STR_PAD_LEFT); ?></h2><p>Pending Requests</p></div>
                    <div class="stat-card-new"><h2><?php echo str_pad($cond_count, 2, '0', STR_PAD_LEFT); ?></h2><p>Conducted Tests</p></div>
                    <div class="stat-card-new"><h2><?php echo str_pad($completed_today, 2, '0', STR_PAD_LEFT); ?></h2><p>Completed (Today)</p></div>
                    <div class="stat-card-new"><h2>00</h2><p>Urgent (STAT)</p></div>
                </div>

                <h3 style="color:#fff; margin-bottom: 20px;">Pending Requests</h3>

                <?php
                // Fetch Pending Orders
                $sql_orders = "
                    SELECT lo.*, 
                           rp.name as patient_name, 
                           rd.name as doctor_name
                    FROM lab_tests lo
                    JOIN users up ON lo.patient_id = up.user_id
                    JOIN registrations rp ON up.registration_id = rp.registration_id
                    JOIN users ud ON lo.doctor_id = ud.user_id
                    JOIN registrations rd ON ud.registration_id = rd.registration_id
                    WHERE lo.category_id = (SELECT category_id FROM lab_categories WHERE category_name = '$lab_type' LIMIT 1) AND lo.status = 'Pending'
                    ORDER BY lo.created_at ASC
                ";
                $res_orders = $conn->query($sql_orders);

                if ($res_orders && $res_orders->num_rows > 0):
                    while($order = $res_orders->fetch_assoc()):
                ?>
                <div class="test-request-card">
                    <div>
                        <span style="font-size: 11px; color: #4fc3f7; font-weight: 800; text-transform: uppercase;">
                            <?php echo htmlspecialchars($lab_type); ?> • ID: #LAB-<?php echo $order['labtest_id']; ?>
                        </span>
                        <h4 style="color:#fff; margin: 10px 0; font-size: 18px;"><?php echo htmlspecialchars($order['test_name']); ?></h4>
<<<<<<< HEAD
                         <p style="font-size: 13px; color: #94a3b8; margin-bottom: 20px;">
                             Patient: <?php echo htmlspecialchars($order['patient_name']); ?> • Requested by: <?php 
                                $r_doc = $order['doctor_name'];
                                echo htmlspecialchars((stripos($r_doc, 'Dr.') === 0) ? $r_doc : 'Dr. ' . $r_doc);
                             ?>
                         </p>
=======
                        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 20px;">
                            Patient: <?php echo htmlspecialchars($order['patient_name']); ?> • Requested by: Dr. <?php echo htmlspecialchars($order['doctor_name']); ?>
                        </p>
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
                        
                        <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; margin-top: 10px;">
                            <small style="color: #4fc3f7; text-transform: uppercase; font-size: 10px; font-weight: bold;">Doctor Instructions:</small>
                            <p style="color: #cbd5e1; font-size: 12px; margin-top: 5px;"><?php echo nl2br(htmlspecialchars($order['instructions'] ?: 'No special instructions.')); ?></p>
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft); display:flex; align-items:center; justify-content:center; flex-direction: column;">
                        <?php if (($order['payment_status'] ?? 'Pending') == 'Paid'): ?>
                            <form action="update_lab_status.php" method="POST" style="text-align:center;">
                                <input type="hidden" name="order_id" value="<?php echo $order['labtest_id']; ?>">
                                <input type="hidden" name="status" value="Conducted">
                                <i class="fas fa-microscope" style="font-size: 40px; color: #4fc3f7; margin-bottom: 20px;"></i>
                                <h4 style="color: #fff; margin-bottom: 10px;">Ready to Process?</h4>
                                <p style="color: #64748b; font-size: 12px; margin-bottom: 20px;">Payment Verified. Mark sample as received.</p>
                                <button type="submit" style="background: #4fc3f7; color: #020617; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; transition:0.3s; box-shadow: 0 4px 12px rgba(79, 195, 247, 0.3);">Accept & Start</button>
                            </form>
                        <?php else: ?>
                             <div id="payment-step-1-<?php echo $order['labtest_id']; ?>" style="text-align:center;">
                                <div style="width: 60px; height: 60px; background: rgba(245, 158, 11, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                    <i class="fas fa-qrcode" style="font-size: 24px; color: #f59e0b;"></i>
                                </div>
                                <h4 style="color: #fff; margin-bottom: 10px;">Payment Required</h4>
                                <p style="color: #64748b; font-size: 12px; margin-bottom: 20px;">Collect payment before processing test.</p>
                                <button onclick="generateQR(<?php echo $order['labtest_id']; ?>)" style="background: #f59e0b; color: #020617; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; transition:0.3s; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);">
                                    <i class="fas fa-qrcode" style="margin-right: 8px;"></i> Generate QR Code
                                </button>
                            </div>
                            
                            <div id="payment-step-loading-<?php echo $order['labtest_id']; ?>" style="display:none; text-align:center;">
                                 <div style="margin-bottom: 20px;">
                                    <i class="fas fa-circle-notch fa-spin" style="font-size: 30px; color: #4fc3f7;"></i>
                                 </div>
                                 <p style="color: #94a3b8; font-size: 12px;">Generating Secure Payment QR...</p>
                            </div>

                            <div id="payment-step-2-<?php echo $order['labtest_id']; ?>" style="display:none; text-align:center;">
<<<<<<< HEAD
                                <div style="background: white; padding: 15px; border-radius: 12px; display: inline-block; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=augustinejoyaljose@okaxis%26pn=Augustine%20joyal%20Jose%26cu=INR" alt="Hospital UPI QR" style="display: block; width: 180px; height: 180px;">
                                </div>
                                <p style="color: #cbd5e1; font-weight: 600; font-size: 14px; margin-bottom: 5px;">Hospital GPay Scanner</p>
                                <p style="color: #64748b; font-size: 12px; margin-bottom: 20px;">UPI ID: augustinejoyaljose@okaxis</p>
                                <form id="paymentForm-<?php echo $order['labtest_id']; ?>" action="mark_lab_paid.php" method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $order['labtest_id']; ?>">
                                    <button type="button" onclick="verifyPayment(<?php echo $order['labtest_id']; ?>)" style="background: #10b981; color: #fff; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; transition:0.3s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); width: 100%;">
                                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i> Verify Payment
=======
                                <div style="background: white; padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 20px;">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=HealCare_Lab_Payment_<?php echo $order['labtest_id']; ?>" alt="Payment QR" style="display: block;">
                                </div>
                                <p style="color: #64748b; font-size: 12px; margin-bottom: 20px;">Ask patient to scan & pay</p>
                                <form action="mark_lab_paid.php" method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $order['labtest_id']; ?>">
                                    <button type="submit" style="background: #10b981; color: #fff; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; transition:0.3s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i> Payment Done
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                ?>
                <?php endif; ?>

            <?php elseif ($_GET['section'] == 'conducted'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Tests in Progress (Conducted)</h1>
                    <p style="color:#64748b; font-size:14px;">Active analysis and reporting.</p>
                </div>

                <?php
                $sql_proc = "
                    SELECT lo.*, rp.name as patient_name, rd.name as doctor_name
                    FROM lab_tests lo
                    JOIN users up ON lo.patient_id = up.user_id JOIN registrations rp ON up.registration_id = rp.registration_id
                    JOIN users ud ON lo.doctor_id = ud.user_id JOIN registrations rd ON ud.registration_id = rd.registration_id
                    WHERE lo.category_id = (SELECT category_id FROM lab_categories WHERE category_name = '$lab_type' LIMIT 1) AND lo.status = 'Conducted'
                    ORDER BY lo.updated_at ASC
                ";
                $res_proc = $conn->query($sql_proc);
                if ($res_proc && $res_proc->num_rows > 0):
                    while($order = $res_proc->fetch_assoc()):
                ?>
                <div class="test-request-card">
                    <div>
                        <span style="font-size: 11px; color: #f59e0b; font-weight: 800; text-transform: uppercase;">
                            IN PROGRESS • ID: #LAB-<?php echo $order['labtest_id']; ?>
                        </span>
                        <h4 style="color:#fff; margin: 10px 0; font-size: 18px;"><?php echo htmlspecialchars($order['test_name']); ?></h4>
                        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 20px;">
                            Patient: <span style="color:#fff;"><?php echo htmlspecialchars($order['patient_name']); ?></span>
                        </p>
                        <div style="background: rgba(245, 158, 11, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.2);">
                            <i class="fas fa-flask" style="color: #f59e0b;"></i> <span style="color: #f59e0b; font-size: 12px; font-weight: bold;">Analysis in Progress</span>
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft);">
                        <form action="finalize_lab_report.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?php echo $order['labtest_id']; ?>">
                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 13px; color: #cbd5e1;">Final Test Results</span>
                                </div>
                                <textarea name="result_summary" placeholder="Enter findings, observations, and values..." style="width: 100%; background: #020617; border: 1px solid var(--border-soft); padding: 12px; border-radius: 10px; color: #fff; font-size: 13px; resize: none; height: 100px;" required></textarea>
                                
<<<<<<< HEAD
                                <div style="display: flex; gap: 15px; margin-bottom: 5px;">
                                    <div style="flex: 1;">
                                        <label style="display: block; color: #94a3b8; font-size: 11px; margin-bottom: 5px;">RESULT STATUS</label>
                                        <select name="result_status" style="width: 100%; background: #020617; border: 1px solid var(--border-soft); padding: 10px; border-radius: 8px; color: #fff; font-size: 12px; font-weight: 700;">
                                            <option value="Normal">NORMAL (Green)</option>
                                            <option value="Abnormal">ABNORMAL (Yellow)</option>
                                            <option value="Critical">CRITICAL (Red)</option>
                                        </select>
                                    </div>
                                    <label class="btn-upload" style="flex: 1; justify-content: center; height: 38px; margin-top: 18px;">
                                        <input type="file" name="report_pdf" accept=".pdf" style="display: none;" onchange="this.parentElement.style.background='#4fc3f7'; this.parentElement.style.color='#fff';">
                                        <i class="fas fa-file-pdf"></i> Attach PDF
                                    </label>
                                </div>
                                <button type="submit" style="width: 100%; background: #10b981; color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer;">Finalize Results</button>
=======
                                <div style="display: flex; gap: 15px;">
                                    <label class="btn-upload" style="flex: 1; justify-content: center;">
                                        <input type="file" name="report_pdf" accept=".pdf" style="display: none;" onchange="this.parentElement.style.background='#4fc3f7'; this.parentElement.style.color='#fff';">
                                        <i class="fas fa-file-pdf"></i> Attach PDF
                                    </label>
                                    <button type="submit" style="flex: 1; background: #10b981; color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer;">Finalize Results</button>
                                </div>
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
                            </div>
                        </form>
                    </div>
                </div>
                <?php endwhile; else: ?>
                    <div style="text-align: center; padding: 60px; color: #64748b;">No samples currently in processing.</div>
                <?php endif; ?>

            <?php elseif ($_GET['section'] == 'completed'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Completed Reports</h1>
                    <p style="color:#64748b; font-size:14px;">History of finalized tests.</p>
                </div>
                <?php
                $sql_comp = "
                    SELECT lo.*, rp.name as patient_name
                    FROM lab_tests lo
                    JOIN users up ON lo.patient_id = up.user_id JOIN registrations rp ON up.registration_id = rp.registration_id
                    WHERE lo.category_id = (SELECT category_id FROM lab_categories WHERE category_name = '$lab_type' LIMIT 1) AND lo.status = 'Completed'
                    ORDER BY lo.updated_at DESC LIMIT 50
                ";
                $res_comp = $conn->query($sql_comp);
                if ($res_comp && $res_comp->num_rows > 0):
                ?>
                <div style="background: #0f172a; border-radius: 12px; border: 1px solid var(--border-soft); overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; color: #fff;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                <th style="padding: 15px; font-size: 12px; color: #94a3b8;">ID</th>
                                <th style="padding: 15px; font-size: 12px; color: #94a3b8;">Test Name</th>
                                <th style="padding: 15px; font-size: 12px; color: #94a3b8;">Patient</th>
                                <th style="padding: 15px; font-size: 12px; color: #94a3b8;">Completed On</th>
                                <th style="padding: 15px; font-size: 12px; color: #94a3b8;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $res_comp->fetch_assoc()): ?>
                            <tr style="border-top: 1px solid var(--border-soft);">
                                <td style="padding: 15px; font-size: 13px; font-family: monospace;">#LAB-<?php echo $row['labtest_id']; ?></td>
                                <td style="padding: 15px; font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($row['test_name']); ?></td>
                                <td style="padding: 15px; font-size: 13px;"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                <td style="padding: 15px; font-size: 13px; color: #94a3b8;"><?php echo date('M d, H:i', strtotime($row['updated_at'])); ?></td>
                                <td style="padding: 15px;">
                                    <?php if($row['report_path']): ?>
                                    <a href="<?php echo htmlspecialchars($row['report_path']); ?>" target="_blank" style="color: #4fc3f7; text-decoration: none; font-size: 12px; border: 1px solid rgba(79, 195, 247, 0.3); padding: 5px 10px; border-radius: 5px;">View PDF</a>
                                    <?php else: ?>
                                    <span style="color: #64748b; font-size: 12px;">No PDF</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: #64748b;">No completed reports found.</div>
                <?php endif; ?>

            <?php elseif ($_GET['section'] == 'archive'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Archive</h1>
                    <p style="color:#64748b; font-size:14px;">Full history of laboratory operations.</p>
                </div>
                <p style="color: #64748b;">Archive functionality coming soon. Use the Completed Reports section to view recent history.</p>

<<<<<<< HEAD
            <?php elseif ($_GET['section'] == 'patient_history'): ?>
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="color:#fff; font-size: 28px;">Patient Clinical History</h1>
                        <p style="color:#64748b; font-size:14px;">Restricted lab-access to patient diagnostic records.</p>
                    </div>
                </div>

                <!-- Patient Search Bar -->
                <div style="background: #0f172a; padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); margin-bottom: 30px;">
                    <form method="GET" style="display: flex; gap: 15px;">
                        <input type="hidden" name="section" value="patient_history">
                        <div style="flex: 1; position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                            <input type="text" name="search_query" placeholder="Search by Name, Patient ID (PAT-101) or Lab Order ID..." value="<?php echo htmlspecialchars($_GET['search_query'] ?? ''); ?>" style="width: 100%; background: #020617; border: 1px solid var(--border-soft); padding: 12px 12px 12px 45px; border-radius: 10px; color: #fff; font-size: 14px;">
                        </div>
                        <button type="submit" style="background: #4fc3f7; color: #020617; border: none; padding: 0 30px; border-radius: 10px; font-weight: 700; cursor: pointer;">Search</button>
                    </form>
                </div>

                <?php 
                $search_query = $_GET['search_query'] ?? null;
                $p_data = null;
                if ($search_query) {
                    $search_term = "%$search_query%";
                    $stmt_p = $conn->prepare("
                        SELECT u.user_id, r.name, r.phone, pp.patient_code, pp.gender, pp.date_of_birth, 
                               (SELECT status FROM admissions WHERE patient_id = u.user_id AND status = 'Admitted' LIMIT 1) as admission_status
                        FROM users u 
                        JOIN registrations r ON u.registration_id = r.registration_id 
                        LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id 
                        LEFT JOIN lab_tests lt ON u.user_id = lt.patient_id
                        WHERE pp.patient_code = ? OR lt.labtest_id = ? OR r.name LIKE ?
                        LIMIT 1
                    ");
                    $stmt_p->bind_param("sss", $search_query, $search_query, $search_term);
                    $stmt_p->execute();
                    $p_data = $stmt_p->get_result()->fetch_assoc();
                    
                    if ($p_data) {
                        $pid = $p_data['user_id'];
                        // Age Calculation
                        $age = 'N/A';
                        if ($p_data['date_of_birth']) {
                            $age = date_diff(date_create($p_data['date_of_birth']), date_create('today'))->y . ' Years';
                        }
                    } else {
                        echo '<div style="text-align: center; padding: 40px; background: rgba(239, 68, 68, 0.05); border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.1); color: #ef4444;">No patient found for search: <strong>'.htmlspecialchars($search_query).'</strong></div>';
                    }
                }

                if ($p_data):
                ?>
                <!-- Patient Profile Summary (Lab Restricted) -->
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-bottom: 30px;">
                    <div style="background: #0f172a; padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft); display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <div style="width: 100px; height: 100px; background: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #4fc3f7; margin-bottom: 15px; border: 3px solid #4fc3f7;">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 style="color: #fff; margin-bottom: 5px;"><?php echo htmlspecialchars($p_data['name']); ?></h2>
                        <span style="font-size: 12px; color: #4fc3f7; font-weight: 700; background: rgba(79, 195, 247, 0.1); padding: 4px 12px; border-radius: 20px;"><?php echo $p_data['patient_code'] ?: 'ID: #'.$p_data['user_id']; ?></span>
                        
                        <div style="width: 100%; margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div style="background: #020617; padding: 12px; border-radius: 10px; text-align: center;">
                                <small style="color: #64748b; font-size: 10px; text-transform: uppercase;">Age / Gender</small>
                                <p style="color: #fff; font-size: 13px; font-weight: 600; margin-top: 4px;"><?php echo $age; ?> / <?php echo $p_data['gender']; ?></p>
                            </div>
                            <div style="background: #020617; padding: 12px; border-radius: 10px; text-align: center;">
                                <small style="color: #64748b; font-size: 10px; text-transform: uppercase;">Patient Type</small>
                                <p style="color: <?php echo $p_data['admission_status'] ? '#f59e0b' : '#10b981'; ?>; font-size: 13px; font-weight: 800; margin-top: 4px;">
                                    <?php echo $p_data['admission_status'] ? 'IPD (Inpatient)' : 'OPD (Outpatient)'; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div style="background: #0f172a; padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft);">
                        <h3 style="color: #fff; margin-bottom: 20px; border-bottom: 1px solid var(--border-soft); padding-bottom: 10px;">Primary Lab Details</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label style="display: block; color: #64748b; font-size: 11px; text-transform: uppercase; margin-bottom: 5px;">Referring Physician</label>
                                <?php 
                                    $stmt_doc = $conn->prepare("SELECT r.name FROM lab_tests lt JOIN users u ON lt.doctor_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id WHERE lt.patient_id = ? ORDER BY lt.created_at DESC LIMIT 1");
                                    $stmt_doc->bind_param("i", $pid);
                                    $stmt_doc->execute();
                                    $doc_name = $stmt_doc->get_result()->fetch_assoc()['name'] ?? 'Not Assigned';
                                ?>
                                <p style="color: #fff; font-weight: 600;">Dr. <?php echo htmlspecialchars($doc_name); ?></p>
                            </div>
                            <div>
                                <label style="display: block; color: #64748b; font-size: 11px; text-transform: uppercase; margin-bottom: 5px;">Active Orders</label>
                                <?php 
                                    $q_active = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE patient_id = $pid AND status IN ('Pending', 'Conducted')");
                                    $active_cnt = $q_active->fetch_assoc()['count'];
                                ?>
                                <p style="color: #4fc3f7; font-weight: 800; font-size: 18px;"><?php echo $active_cnt; ?></p>
                            </div>
                        </div>
                        
                        <div style="margin-top: 25px; background: rgba(79, 195, 247, 0.05); border: 1px solid rgba(79, 195, 247, 0.1); border-radius: 12px; padding: 15px;">
                            <h4 style="color: #4fc3f7; font-size: 13px; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Lab Staff Privacy Advisory</h4>
                            <p style="color: #94a3b8; font-size: 12px; line-height: 1.5;">Access to patient charts is restricted to relevant diagnostic history. Internal treatment notes, full prescriptions, and billing data are hidden to comply with role-based security protocols.</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div style="display: flex; gap: 2px; margin-bottom: 20px; border-bottom: 1px solid var(--border-soft);">
                    <button onclick="switchLabTab('current')" id="tab-btn-current" class="lab-tab-btn active">Current Lab Order</button>
                    <button onclick="switchLabTab('previous')" id="tab-btn-previous" class="lab-tab-btn">Previous Lab Results</button>
                    <button onclick="switchLabTab('trends')" id="tab-btn-trends" class="lab-tab-btn">Test Trends</button>
                </div>

                <!-- Tab Content: Current Order -->
                <div id="lab-tab-current" class="lab-tab-content active">
                    <div style="background: #0f172a; border-radius: 16px; border: 1px solid var(--border-soft); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">ID</th>
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Test Name</th>
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Dept</th>
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Priority</th>
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Sample Type</th>
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $stmt_curr = $conn->prepare("SELECT * FROM lab_tests WHERE patient_id = ? AND status IN ('Pending', 'Conducted') ORDER BY created_at DESC");
                                $stmt_curr->bind_param("i", $pid);
                                $stmt_curr->execute();
                                $res_curr = $stmt_curr->get_result();
                                if ($res_curr->num_rows > 0):
                                    while($lt = $res_curr->fetch_assoc()):
                                        $priority_color = ($lt['priority'] == 'STAT') ? '#ef4444' : (($lt['priority'] == 'Urgent') ? '#f59e0b' : '#3b82f6');
                                ?>
                                <tr style="border-top: 1px solid var(--border-soft);">
                                    <td style="padding: 15px; color: #94a3b8; font-family: monospace;">#LAB-<?php echo $lt['labtest_id']; ?></td>
                                    <td style="padding: 15px; color: #fff; font-weight: 600;"><?php echo htmlspecialchars($lt['test_name']); ?></td>
                                    <td style="padding: 15px; color: #cbd5e1;"><?php echo htmlspecialchars($lt['test_type'] ?: 'Lab'); ?></td>
                                    <td style="padding: 15px;">
                                        <span style="color: <?php echo $priority_color; ?>; font-weight: 800; font-size: 11px;"><?php echo strtoupper($lt['priority'] ?: 'NORMAL'); ?></span>
                                    </td>
                                    <td style="padding: 15px; color: #fff;"><?php echo htmlspecialchars($lt['sample_type'] ?: 'Standard'); ?></td>
                                    <td style="padding: 15px;">
                                        <span style="color: <?php echo ($lt['status'] == 'Conducted' ? '#f59e0b' : '#3b82f6'); ?>; font-size: 11px;"><?php echo strtoupper($lt['status']); ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="6" style="padding: 30px; text-align: center; color: #64748b;">No active lab orders found for this patient.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Content: Previous Results -->
                <div id="lab-tab-previous" class="lab-tab-content">
                    <div style="background: #0f172a; border-radius: 16px; border: 1px solid var(--border-soft); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Date</th>
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Test Name</th>
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Result</th>
                                    <th style="padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $stmt_prev = $conn->prepare("SELECT * FROM lab_tests WHERE patient_id = ? AND status = 'Completed' ORDER BY updated_at DESC");
                                $stmt_prev->bind_param("i", $pid);
                                $stmt_prev->execute();
                                $res_prev = $stmt_prev->get_result();
                                if ($res_prev->num_rows > 0):
                                    while($lt = $res_prev->fetch_assoc()):
                                        $res_status = $lt['result_status'] ?: 'Normal';
                                        $status_color = ($res_status == 'Critical') ? '#ef4444' : (($res_status == 'Abnormal') ? '#f59e0b' : '#10b981');
                                        $bg_light = ($res_status == 'Critical') ? 'rgba(239, 68, 68, 0.1)' : (($res_status == 'Abnormal') ? 'rgba(245, 158, 11, 0.1)' : 'rgba(16, 185, 129, 0.1)');
                                ?>
                                <tr style="border-top: 1px solid var(--border-soft);">
                                    <td style="padding: 15px; color: #94a3b8; font-size: 12px;"><?php echo date('d M, Y', strtotime($lt['updated_at'])); ?></td>
                                    <td style="padding: 15px; color: #fff; font-weight: 600;"><?php echo htmlspecialchars($lt['test_name']); ?></td>
                                    <td style="padding: 15px; color: #cbd5e1; font-size: 13px;">
                                        <div style="max-height: 40px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($lt['result']); ?>
                                        </div>
                                    </td>
                                    <td style="padding: 15px;">
                                        <span style="display: inline-block; background: <?php echo $bg_light; ?>; color: <?php echo $status_color; ?>; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid <?php echo $status_color; ?>44;">
                                            <?php echo strtoupper($res_status); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="4" style="padding: 30px; text-align: center; color: #64748b;">No previous laboratory records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Content: Test Trends -->
                <div id="lab-tab-trends" class="lab-tab-content">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div style="background: #0f172a; padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft);">
                            <h4 style="color: #fff; margin-bottom: 20px;">Blood Glucose Trends (HbA1c / RBS)</h4>
                            <div style="height: 250px;">
                                <canvas id="glucoseTrendChart"></canvas>
                            </div>
                        </div>
                        <div style="background: #0f172a; padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft);">
                            <h4 style="color: #fff; margin-bottom: 20px;">CBC Parameters (WBC / Platelets)</h4>
                            <div style="height: 250px;">
                                <canvas id="cbcTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: #0f172a; padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft); margin-top: 30px;">
                         <h4 style="color: #fff; margin-bottom: 15px;">Abnormal History Alerts</h4>
                         <div style="display: flex; flex-direction: column; gap: 10px;">
                             <?php 
                             $stmt_warn = $conn->prepare("SELECT test_name, result_status, updated_at FROM lab_tests WHERE patient_id = ? AND result_status IN ('Abnormal', 'Critical') ORDER BY updated_at DESC LIMIT 3");
                             $stmt_warn->bind_param("i", $pid);
                             $stmt_warn->execute();
                             $res_warn = $stmt_warn->get_result();
                             if ($res_warn->num_rows > 0):
                                 while($w = $res_warn->fetch_assoc()):
                             ?>
                             <div style="display: flex; align-items: center; gap: 15px; background: rgba(239, 68, 68, 0.05); padding: 12px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.1);">
                                 <i class="fas fa-exclamation-triangle" style="color: <?php echo $w['result_status'] == 'Critical' ? '#ef4444' : '#f59e0b'; ?>;"></i>
                                 <div>
                                     <span style="color: #fff; font-size: 13px; font-weight: 600;"><?php echo htmlspecialchars($w['test_name']); ?></span>
                                     <small style="color: <?php echo $w['result_status'] == 'Critical' ? '#ef4444' : '#f59e0b'; ?>; font-weight: 800; font-size: 10px; margin-left: 10px;"><?php echo strtoupper($w['result_status']); ?></small>
                                     <p style="color: #64748b; font-size: 11px; margin-top: 2px;">Recorded on <?php echo date('d M, Y', strtotime($w['updated_at'])); ?></p>
                                 </div>
                             </div>
                             <?php endwhile; else: ?>
                                 <p style="color: #10b981; font-size: 13px;"><i class="fas fa-check-circle"></i> No abnormal historical markers detected.</p>
                             <?php endif; ?>
                         </div>
                    </div>
                </div>

                <style>
                    .lab-tab-btn { background: none; border: none; color: #94a3b8; padding: 12px 25px; cursor: pointer; font-size: 13px; font-weight: 600; border-bottom: 3px solid transparent; transition: 0.3s; }
                    .lab-tab-btn:hover { color: #fff; background: rgba(255,255,255,0.02); }
                    .lab-tab-btn.active { color: #4fc3f7; border-bottom-color: #4fc3f7; background: rgba(79, 195, 247, 0.05); }
                    .lab-tab-content { display: none; transition: opacity 0.3s ease; }
                    .lab-tab-content.active { display: block; animation: tabFadeIn 0.3s ease; }
                    @keyframes tabFadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
                </style>

                <script>
                    function switchLabTab(tabId) {
                        document.querySelectorAll('.lab-tab-btn').forEach(btn => btn.classList.remove('active'));
                        document.querySelectorAll('.lab-tab-content').forEach(content => content.classList.remove('active'));
                        
                        document.getElementById('tab-btn-' + tabId).classList.add('active');
                        document.getElementById('lab-tab-' + tabId).classList.add('active');
                        
                        // Re-trigger chart rendering if trends tab
                        if (tabId === 'trends') renderTrendCharts();
                    }

                    function renderTrendCharts() {
                        // Glucose Chart
                        const gCtx = document.getElementById('glucoseTrendChart').getContext('2d');
                        new Chart(gCtx, {
                            type: 'line',
                            data: {
                                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                                datasets: [{
                                    label: 'HbA1c (%)',
                                    data: [5.8, 6.2, 5.9, 6.5, 6.1, 6.0],
                                    borderColor: '#4fc3f7',
                                    backgroundColor: 'rgba(79, 195, 247, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { color: 'rgba(255,255,255,0.05)' } } } }
                        });

                        // CBC Chart
                        const cCtx = document.getElementById('cbcTrendChart').getContext('2d');
                        new Chart(cCtx, {
                            type: 'bar',
                            data: {
                                labels: ['WBC', 'RBC', 'Platelets', 'HGB'],
                                datasets: [{
                                    label: 'Current Level',
                                    data: [8500, 4.8, 280000, 14.2],
                                    backgroundColor: ['#4fc3f7', '#10b981', '#f59e0b', '#ef4444']
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                        });
                    }
                </script>
                <?php endif; ?>

=======
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
            <?php elseif ($_GET['section'] == 'reports'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Laboratory Reports</h1>
                    <p style="color:#64748b; font-size:14px;">Access daily test logs, revenue data, and equipment analytics.</p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
                    <!-- Daily Test Report -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=lab_daily'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: #4fc3f7;">Daily Test Report</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Daily diagnostic throughput</p>
                            </div>
                            <i class="fas fa-vial" style="font-size:24px; color: #4fc3f7;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Tests Performed Today</li>
                            <li>Sample Processing Logs</li>
                        </ul>
                        <button class="btn-upload" style="width:100%; justify-content:center;">View Report</button>
                    </div>

                    <!-- Monthly Revenue -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=lab_revenue'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: #10b981;">Monthly Revenue</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Financial performance</p>
                            </div>
                            <i class="fas fa-file-invoice-dollar" style="font-size:24px; color: #10b981;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Revenue by Test Type</li>
                            <li>Pending Billables</li>
                        </ul>
                        <button class="btn-upload" style="width:100%; justify-content:center; border-color: #10b981; color: #10b981; background: rgba(16, 185, 129, 0.1);">View Report</button>
                    </div>

                    <!-- Test Type Analysis -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=lab_analytics'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: #f59e0b;">Test Trend Analysis</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Category-wise breakdown</p>
                            </div>
                            <i class="fas fa-chart-pie" style="font-size:24px; color: #f59e0b;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Most Requested Tests</li>
                            <li>Specialization Demand</li>
                        </ul>
                        <button class="btn-upload" style="width:100%; justify-content:center; border-color: #f59e0b; color: #f59e0b; background: rgba(245, 158, 11, 0.1);">View Report</button>
                    </div>

                    <!-- Equipment Utilization -->
                    <div class="stat-card-new" style="cursor: pointer; transition: 0.3s;" onclick="location.href='reports_manager.php?view=reports&type=lab_equipment'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: #ef4444;">Equipment Logs</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Maintenance & Usage</p>
                            </div>
                            <i class="fas fa-tools" style="font-size:24px; color: #ef4444;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Machine Uptime Stats</li>
                            <li>Maintenance Schedules</li>
                        </ul>
                        <button class="btn-upload" style="width:100%; justify-content:center; border-color: #ef4444; color: #ef4444; background: rgba(239, 68, 68, 0.1);">View Report</button>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php 
    // Set staff_type for the modal
    $staff_type = 'lab_staff';
    include 'includes/report_upload_modal.php'; 
    ?>
    <script>
        function generateQR(id) {
            document.getElementById('payment-step-1-'+id).style.display = 'none';
            document.getElementById('payment-step-loading-'+id).style.display = 'block';
            
            // Simulate API latency for effect
            setTimeout(() => {
                document.getElementById('payment-step-loading-'+id).style.display = 'none';
                document.getElementById('payment-step-2-'+id).style.display = 'block';
            }, 1000); // 1 second delay
        }
<<<<<<< HEAD

        function verifyPayment(id) {
            Swal.fire({
                title: 'Verifying Payment...',
                text: 'Connecting to hospital gateway',
                icon: 'info',
                timer: 1500,
                timerProgressBar: true,
                showConfirmButton: false,
                background: '#0f172a',
                color: '#fff',
                didClose: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Payment verified.',
                        icon: 'success',
                        background: '#0f172a',
                        color: '#fff',
                        confirmButtonColor: '#4fc3f7'
                    }).then(() => {
                        document.getElementById('paymentForm-' + id).submit();
                    });
                }
            });
        }
    </script>
    <script>
        // Brand Animation
        document.addEventListener('DOMContentLoaded', function() {
            function initBrandAnimation() {
                const brandElement = document.querySelector('.animated-brand');
                if (!brandElement) return;

                const text = brandElement.textContent.trim();
                brandElement.textContent = ''; // Clear text

                // Create spans
                const letters = [];
                for (let char of text) {
                    const span = document.createElement('span');
                    span.textContent = char;
                    span.classList.add('brand-letter');
                    if (char === ' ') {
                        span.style.width = '0.3em'; // Space
                        span.style.display = 'inline-block';
                    }
                    brandElement.appendChild(span);
                    letters.push(span);
                }

                function animate() {
                    // Show sequence
                    letters.forEach((letter, index) => {
                        setTimeout(() => {
                            letter.classList.add('visible');
                        }, index * 100); // 100ms staggering
                    });

                    // Hide sequence (after full text is shown + delay)
                    const totalTime = (letters.length * 100) + 2000; // 2s pause

                    setTimeout(() => {
                        letters.forEach((letter, index) => {
                            setTimeout(() => {
                                letter.classList.remove('visible');
                            }, index * 50); // Faster fade out
                        });
                    }, totalTime);

                    const cycleTime = totalTime + (letters.length * 50) + 500; // time to hide + pause

                    setTimeout(animate, cycleTime);
                }

                // Start animation
                animate();
            }
            
            initBrandAnimation();
        });
=======
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
    </script>
</body>
</html>
