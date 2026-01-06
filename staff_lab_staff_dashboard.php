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
            <div style="margin-bottom: 30px;">
                <h1 style="color:#fff; font-size: 28px;">Lab Operations</h1>
                <p style="color:#64748b; font-size:14px;">Manage medical test requests and report generation.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card-new"><h2>12</h2><p>Pending Requests</p></div>
                <div class="stat-card-new"><h2>05</h2><p>Samples Ready</p></div>
                <div class="stat-card-new"><h2>28</h2><p>Completed (Today)</p></div>
                <div class="stat-card-new"><h2>02</h2><p>Urgent (STAT)</p></div>
            </div>

            <h3 style="color:#fff; margin-bottom: 20px;">Active Laboratory Tasks</h3>

            <div class="test-request-card">
                <div>
                    <span style="font-size: 11px; color: #4fc3f7; font-weight: 800; text-transform: uppercase;">Hematology • ID: #LAB-1025</span>
                    <h4 style="color:#fff; margin: 10px 0; font-size: 18px;">Complete Blood Count (CBC)</h4>
                    <p style="font-size: 13px; color: #94a3b8; margin-bottom: 20px;">Patient: Dileep Mathew • Requested by: Dr. Sathish</p>
                    
                    <div class="sample-status-bar">
                        <div class="status-dot active" title="Sample Collected" style="--label: 'Collected'"></div>
                        <div class="status-dot active" title="In Processing" style="--label: 'Processing'"></div>
                        <div class="status-dot" title="Result Pending"></div>
                        <div class="status-dot" title="Report Ready"></div>
                    </div>
                </div>
                <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 16px; border: 1px solid var(--border-soft);">
                    <form action="upload_report.php" method="POST" enctype="multipart/form-data">
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 13px; color: #cbd5e1;">Final Test Results</span>
                                <span style="color: #ef4444; font-size: 10px; font-weight: 800;"><i class="fas fa-bolt"></i> URGENT</span>
                            </div>
                            <textarea placeholder="Final clinical marking/observations..." style="width: 100%; background: #020617; border: 1px solid var(--border-soft); padding: 12px; border-radius: 10px; color: #fff; font-size: 13px; resize: none;"></textarea>
                            
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
        </main>
    </div>
</body>
</html>
