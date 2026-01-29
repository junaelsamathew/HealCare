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
            <a href="?section=archive" class="nav-item <?php echo $section == 'archive' ? 'active' : ''; ?>"><i class="fas fa-archive"></i> Archive</a>
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

                $q_completed = $conn->query("SELECT COUNT(*) as count FROM lab_tests WHERE test_type LIKE '$search_pattern' AND status = 'Completed' AND DATE(created_at) = CURRENT_DATE");
                $completed_today = $q_completed->fetch_assoc()['count'];
                ?>

                <!-- Quick Archive -->
                <div style="background: linear-gradient(135deg, #0f172a, #1e293b); padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="color: #fff; margin-bottom: 5px; font-size: 16px;"><i class="fas fa-file-upload" style="color: #4fc3f7;"></i> Laboratory Documentation</h3>
                        <p style="color: #64748b; font-size: 12px;">Archive manual test summaries or complex diagnostic reports.</p>
                    </div>
                    <button onclick="openReportModal()" style="background: #4fc3f7; color: #020617; text-decoration: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 12px; border: none; cursor: pointer;">
                        <i class="fas fa-upload"></i> Upload Report
                    </button>
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
                        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 20px;">
                            Patient: <?php echo htmlspecialchars($order['patient_name']); ?> • Requested by: Dr. <?php echo htmlspecialchars($order['doctor_name']); ?>
                        </p>
                        
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
                                <div style="background: white; padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 20px;">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=HealCare_Lab_Payment_<?php echo $order['labtest_id']; ?>" alt="Payment QR" style="display: block;">
                                </div>
                                <p style="color: #64748b; font-size: 12px; margin-bottom: 20px;">Ask patient to scan & pay</p>
                                <form action="mark_lab_paid.php" method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $order['labtest_id']; ?>">
                                    <button type="submit" style="background: #10b981; color: #fff; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; transition:0.3s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i> Payment Done
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
                                
                                <div style="display: flex; gap: 15px;">
                                    <label class="btn-upload" style="flex: 1; justify-content: center;">
                                        <input type="file" name="report_pdf" accept=".pdf" style="display: none;" onchange="this.parentElement.style.background='#4fc3f7'; this.parentElement.style.color='#fff';">
                                        <i class="fas fa-file-pdf"></i> Attach PDF
                                    </label>
                                    <button type="submit" style="flex: 1; background: #10b981; color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer;">Finalize Results</button>
                                </div>
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
    </script>
</body>
</html>
