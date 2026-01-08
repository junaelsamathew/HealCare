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
$lab_type = $lab['lab_type'] ?? 'Pathology';
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
        <div style="display: flex; align-items: center;"><span class="staff-label" style="color: #94a3b8; font-size: 14px; margin-right: 15px;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span><a href="logout.php" style="color: #94a3b8; text-decoration: none; border: 1px solid #4fc3f7; padding: 5px 20px; border-radius: 20px;">Log Out</a></div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <a href="#" class="nav-item active"><i class="fas fa-vials"></i> Pending Tests</a>
            <a href="#" class="nav-item"><i class="fas fa-microscope"></i> In Processing</a>
            <a href="#" class="nav-item"><i class="fas fa-file-alt"></i> Completed Reports</a>
            <a href="#" class="nav-item"><i class="fas fa-archive"></i> Archive</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
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
            $q_pending = $conn->query("SELECT COUNT(*) as count FROM lab_orders WHERE lab_category = '$lab_type' AND order_status = 'Pending'");
            $pending_count = $q_pending->fetch_assoc()['count'];

            $q_completed = $conn->query("SELECT COUNT(*) as count FROM lab_orders WHERE lab_category = '$lab_type' AND order_status = 'Completed' AND DATE(created_at) = CURRENT_DATE");
            $completed_today = $q_completed->fetch_assoc()['count'];
            ?>

            <div class="stats-grid">
                <div class="stat-card-new"><h2><?php echo str_pad($pending_count, 2, '0', STR_PAD_LEFT); ?></h2><p>Pending Requests</p></div>
                <div class="stat-card-new"><h2>05</h2><p>Samples Ready</p></div>
                <div class="stat-card-new"><h2><?php echo str_pad($completed_today, 2, '0', STR_PAD_LEFT); ?></h2><p>Completed (Today)</p></div>
                <div class="stat-card-new"><h2>00</h2><p>Urgent (STAT)</p></div>
            </div>

            <h3 style="color:#fff; margin-bottom: 20px;">Active Laboratory Tasks</h3>

            <?php
            // Fetch Pending Orders
            $sql_orders = "
                SELECT lo.*, 
                       rp.name as patient_name, 
                       rd.name as doctor_name
                FROM lab_orders lo
                JOIN users up ON lo.patient_id = up.user_id
                JOIN registrations rp ON up.registration_id = rp.registration_id
                JOIN users ud ON lo.doctor_id = ud.user_id
                JOIN registrations rd ON ud.registration_id = rd.registration_id
                WHERE lo.lab_category = '$lab_type' AND lo.order_status = 'Pending'
                ORDER BY lo.created_at ASC
            ";
            $res_orders = $conn->query($sql_orders);

            if ($res_orders && $res_orders->num_rows > 0):
                while($order = $res_orders->fetch_assoc()):
            ?>
            <div class="test-request-card">
                <div>
                    <span style="font-size: 11px; color: #4fc3f7; font-weight: 800; text-transform: uppercase;">
                        <?php echo htmlspecialchars($lab_type); ?> • ID: #LAB-<?php echo $order['order_id']; ?>
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
                <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft);">
                    <form action="finalize_lab_report.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 13px; color: #cbd5e1;">Final Test Results</span>
                            </div>
                            <textarea name="result_summary" placeholder="Final clinical marking/observations..." style="width: 100%; background: #020617; border: 1px solid var(--border-soft); padding: 12px; border-radius: 10px; color: #fff; font-size: 13px; resize: none;" required></textarea>
                            
                            <div style="display: flex; gap: 15px;">
                                <label class="btn-upload" style="flex: 1; justify-content: center;">
                                    <input type="file" name="report_pdf" accept=".pdf" style="display: none;">
                                    <i class="fas fa-file-pdf"></i> Attach PDF Report
                                </label>
                                <button type="submit" style="flex: 1; background: #4fc3f7; color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer;">Finalize & Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php 
                endwhile;
            else:
            ?>
            <div style="text-align: center; padding: 100px; color: #64748b; background: #0f172a; border-radius: 20px; border: 1px dashed var(--border-soft);">
                <i class="fas fa-check-circle" style="font-size: 40px; margin-bottom: 15px; color: #10b981;"></i>
                <p>All clear! No pending requests for your lab today.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
