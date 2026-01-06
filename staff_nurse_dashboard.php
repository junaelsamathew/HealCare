<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch Nurse Details
$res = $conn->query("SELECT * FROM nurses WHERE user_id = $user_id");
$nurse = $res->fetch_assoc();
$department = $nurse['department'] ?? 'General';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent-blue: #3b82f6;
            --border-soft: rgba(255, 255, 255, 0.05);
        }

        .reception-top-bar { background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .top-info-group { display: flex; gap: 40px; }
        .top-info-item { display: flex; align-items: center; gap: 12px; color: #1e293b; font-size: 13px; }
        .top-info-item i { color: #3b82f6; font-size: 24px; }
        .top-info-text strong { display: block; text-transform: uppercase; font-size: 11px; color: #64748b; }

        .secondary-nav { background: #0f172a; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .staff-label { color: #94a3b8; font-size: 14px; }
        .btn-logout-alt { background: transparent; border: 1px solid #3b82f6; color: #fff; padding: 8px 25px; border-radius: 20px; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .btn-logout-alt:hover { background: #3b82f6; }

        .dashboard-body { display: grid; grid-template-columns: 260px 1fr; height: calc(100vh - 140px); background: #020617; }
        .side-nav { background: #020617; padding: 20px 0; border-right: 1px solid var(--border-soft); }
        .nav-item { display: flex; align-items: center; padding: 15px 30px; color: #94a3b8; text-decoration: none; font-size: 14px; gap: 15px; transition: 0.3s; }
        .nav-item.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-left: 4px solid #3b82f6; }
        .nav-item:hover:not(.active) { color: #fff; background: rgba(255,255,255,0.02); }

        .main-ops { padding: 40px; overflow-y: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card-new { background: #0f172a; padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); }
        .stat-card-new h2 { font-size: 24px; color: #4fc3f7; margin-bottom: 5px; }

        .patient-list-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .patient-card {
            background: #0f172a;
            border: 1px solid var(--border-soft);
            border-radius: 16px;
            padding: 25px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            position: relative;
            overflow: hidden;
        }
        .token-badge {
            position: absolute; top: 0; right: 0;
            background: rgba(79, 195, 247, 0.1); color: #4fc3f7;
            padding: 10px 20px; border-bottom-left-radius: 16px;
            font-weight: 800; font-size: 14px; border: 1px solid rgba(79, 195, 247, 0.2);
            border-top: none; border-right: none;
        }
        .vital-inputs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .form-group-staff { display: flex; flex-direction: column; gap: 8px; }
        .form-group-staff label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; }
        .form-group-staff input, .form-group-staff textarea { 
            background: rgba(255,255,255,0.03); border: 1px solid var(--border-soft); 
            padding: 12px; border-radius: 10px; color: #fff; font-size: 14px; outline: none; transition: 0.3s;
        }
        .form-group-staff input:focus { border-color: #4fc3f7; background: rgba(79, 195, 247, 0.05); }
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
        <div style="display: flex; align-items: center; gap: 15px;"><div style="background: #4fc3f7; color:#fff; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold;">N</div><h2 style="color:#fff; font-size:20px;">Nurse Panel</h2></div>
        <div style="display: flex; align-items: center; gap: 30px;"><span class="staff-label"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span><a href="logout.php" class="btn-logout-alt" style="border-color: #4fc3f7;">Log Out</a></div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <a href="#" class="nav-item active"><i class="fas fa-hospital-user"></i> My Patients</a>
            <a href="#" class="nav-item"><i class="fas fa-heartbeat"></i> Vitals Monitor</a>
            <a href="#" class="nav-item"><i class="fas fa-notes-medical"></i> Nursing Notes</a>
            <a href="#" class="nav-item"><i class="fas fa-syringe"></i> Medication</a>
            <a href="#" class="nav-item"><i class="fas fa-clock"></i> Shift handover</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <div style="margin-bottom: 30px;">
                <h1 style="color:#fff; font-size: 28px;">Ward Management - <?php echo $department; ?></h1>
                <p style="color:#64748b; font-size:14px;">Monitor assigned patients and update vital signs.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card-new"><h2>08</h2><p>Assigned Patients</p></div>
                <div class="stat-card-new"><h2>03</h2><p>Critical Monitoring</p></div>
                <div class="stat-card-new"><h2>05</h2><p>Doses Ready</p></div>
                <div class="stat-card-new"><h2>102</h2><p>Ward Cap.</p></div>
            </div>

            <h3 style="color:#fff; margin-bottom: 25px;">Live Department Queue - Assigned Patients</h3>

            <div class="patient-list-container">
                <!-- Patient 1 -->
                <div class="patient-card">
                    <div class="token-badge">TOKEN: #TK-101</div>
                    <div style="border-right: 1px solid var(--border-soft); padding-right: 30px;">
                        <span style="font-size:11px; color:#4fc3f7; font-weight:800; text-transform:uppercase;">In Treatment</span>
                        <h4 style="color:#fff; margin: 10px 0; font-size: 18px;">Ravi Sharma</h4>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">ID: HC-P-2026-1025 • Male / 32 Yrs</p>
                        
                        <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; font-size: 13px; color: #cbd5e1;">
                            <p style="margin-bottom: 5px;"><i class="fas fa-bed" style="width:20px;"></i> Ward B / Bed 15</p>
                            <p><i class="fas fa-stethoscope" style="width:20px;"></i> Dr. Sathish (ENT)</p>
                        </div>
                    </div>
                    <div>
                        <div class="vital-inputs">
                            <div class="form-group-staff"><label>Heart Rate (BPM)</label><input type="text" value="72" placeholder="72"></div>
                            <div class="form-group-staff"><label>BP (Sys/Dia)</label><input type="text" value="120/80" placeholder="120/80"></div>
                            <div class="form-group-staff"><label>Temp (°F)</label><input type="text" value="98.6" placeholder="98.6"></div>
                            <div class="form-group-staff"><label>SPO2 (%)</label><input type="text" value="98" placeholder="98"></div>
                        </div>
                        <div class="form-group-staff" style="margin-top: 20px;">
                            <label>Nursing Care Notes</label>
                            <textarea rows="3" placeholder="Enter patient observation, pain levels, or medication response..."></textarea>
                        </div>
                        <div style="display: flex; gap: 15px; margin-top: 20px;">
                            <button style="flex: 1; padding: 12px; background: #4fc3f7; border: none; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer;">Update Vitals</button>
                            <button style="padding: 12px 20px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-soft); border-radius: 10px; color: #fff; cursor: pointer;"><i class="fas fa-history"></i> History</button>
                        </div>
                    </div>
                </div>

                <!-- Patient 2 -->
                <div class="patient-card" style="opacity: 0.7;">
                    <div class="token-badge" style="background: rgba(251, 191, 36, 0.1); color: #fbbf24; border-color: rgba(251, 191, 36, 0.2);">TOKEN: #TK-102</div>
                    <div style="border-right: 1px solid var(--border-soft); padding-right: 30px;">
                        <span style="font-size:11px; color:#fbbf24; font-weight:800; text-transform:uppercase;">Waiting in Queue</span>
                        <h4 style="color:#fff; margin: 10px 0; font-size: 18px;">Sneha Gupta</h4>
                        <p style="font-size: 13px; color: #64748b;">ID: HC-P-2026-1026 • Female / 28 Yrs</p>
                    </div>
                    <div>
                        <p style="color: #64748b; font-size: 14px; font-style: italic;">Patient is currently waiting for initial vital check. Please call the patient to the nursing station.</p>
                        <button style="margin-top: 20px; padding: 10px 25px; background: #fbbf24; border: none; border-radius: 10px; color: #000; font-weight: 700; cursor: pointer;">Call Patient</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
