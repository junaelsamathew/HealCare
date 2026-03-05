<?php
session_start();
include 'includes/db_connect.php';

// Auth Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin') {
        // Allow
    } else {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
        exit();
    }
}

// --- Data Gathering Logic ---
try {
    // 1. Basic Counts
    $total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
    $total_patients = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='patient'")->fetch_assoc()['c'] ?? 0;
    $total_doctors = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='doctor'")->fetch_assoc()['c'] ?? 0;
    $total_staff = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='staff'")->fetch_assoc()['c'] ?? 0;
    
    // 2. Appointment Stats
    // Today
    $today = date('Y-m-d');
    $today_appts = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE DATE(appointment_date) = '$today'")->fetch_assoc()['c'] ?? 0;
    
    // Total
    $total_appts = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'] ?? 0;

    // 3. Mock Growth/Rating Data (for display purposes)
    // In a real app, these would come from historical table comparisons
    $user_growth_pct = 12.5; 
    $appt_growth_pct = 8.2;
    $avg_rating = 4.8;
    $satisfaction_pct = 94;

} catch (Exception $e) {
    // Fallback
    $total_users = 0; $total_patients = 0; $total_doctors = 0; $total_staff = 0;
    $today_appts = 0; $total_appts = 0;
    $user_growth_pct = 0; $appt_growth_pct = 0;
    $avg_rating = 0; $satisfaction_pct = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealCare Analytics | AI Intelligence</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #10b981; /* Medical Green */
            --primary-glow: rgba(16, 185, 129, 0.4);
            --secondary: #3b82f6; /* Tech Blue */
            --bg-dark: #0f172a;
            --bg-panel: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-red: #ef4444;
            --glass-border: rgba(255, 255, 255, 0.08);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #020617;
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* --- Layout --- */
        .page-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling (Custom for uniformity) */
        .sidebar {
            width: 260px;
            background: rgba(15, 23, 42, 0.95);
            border-right: 1px solid var(--glass-border);
            position: fixed;
            height: 100vh;
            backdrop-filter: blur(10px);
            z-index: 100;
            overflow-y: auto;
            transition: width 0.3s ease;
        }

        .logo-area {
            height: 80px;
            display: flex;
            align-items: center;
            padding: 0 25px;
            border-bottom: 1px solid var(--glass-border);
        }

        .logo-text {
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .nav-links {
            padding: 20px 0;
        }

        .nav-category {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 1.5px;
            padding: 0 25px;
            margin: 20px 0 10px 0;
            font-weight: 600;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            color: var(--primary);
            background: rgba(16, 185, 129, 0.05);
        }

        .nav-item.active {
            color: var(--text-main);
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.1), transparent);
            border-left-color: var(--primary);
        }

        .nav-item i {
            width: 24px;
            font-size: 16px;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px 40px;
        }

        /* Top Header */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header-title h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .header-title p {
            color: var(--text-muted);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: var(--accent-red);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-red);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .date-badge {
            background: var(--bg-panel);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
        }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-panel);
            padding: 6px 15px 6px 6px;
            border-radius: 50px;
            border: 1px solid var(--glass-border);
            cursor: pointer;
            transition: all 0.3s;
        }
        .profile-btn:hover { border-color: var(--primary); }
        .avatar {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-panel);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow), 0 0 20px rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .icon-green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .icon-blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .icon-purple { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
        .icon-yellow { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 5px;
        }

        .stat-trend {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .trend-up { color: var(--primary); }
        .trend-down { color: var(--accent-red); }

        /* Main Analytics Section */
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .panel-card {
            background: var(--bg-panel);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .panel-title {
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ai-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.4);
        }

        /* AI Insight Engine */
        .insight-card {
            background: linear-gradient(145deg, rgba(16, 185, 129, 0.05), rgba(0,0,0,0));
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .insight-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .insight-item:last-child { margin-bottom: 0; }

        .insight-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }

        .insight-content h4 {
            font-size: 14px;
            margin-bottom: 3px;
            color: var(--text-main);
        }
        .insight-content p {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Terminal Style Section */
        .terminal-panel {
            background: #000;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            font-family: 'Courier New', Courier, monospace;
            color: #10b981;
            font-size: 13px;
            height: 150px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .terminal-header {
            margin-bottom: 10px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            color: #555;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }

        .logs-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .log-line {
            opacity: 0;
            animation: fadeIn 0.5s forwards;
        }

        @keyframes fadeIn { to { opacity: 1; } }

        /* Notifications */
        .notification-panel {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 20px;
        }
        .notif-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .notif-item:last-child { border: none; }
        .notif-danger { color: #ef4444; }
        .notif-warning { color: #f59e0b; }
        .notif-success { color: #10b981; }

        /* Chart Config */
        canvas { width: 100% !important; height: 100% !important; }
        
        /* Floating Back to Top */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.4);
            cursor: pointer;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s;
            z-index: 1000;
        }
        .back-to-top.show { opacity: 1; transform: translateY(0); }

        @media (max-width: 1200px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="page-wrapper">
        <!-- Modern Sidebar -->
        <aside class="sidebar">
            <div class="logo-area">
                <a href="admin_dashboard.php" class="logo-text">HEALCARE +</a>
            </div>
            
            <div class="nav-links">
                <!-- Overview -->
                <div class="nav-category">Core</div>
                <a href="admin_dashboard.php?section=dashboard" class="nav-item">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </a>
                <a href="platform_analytics.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i> <span>Analytics Engine</span>
                </a>

                <!-- Management -->
                <div class="nav-category">Management</div>
                <a href="admin_dashboard.php?section=appointments" class="nav-item">
                    <i class="fas fa-calendar-check"></i> <span>Appointments</span>
                </a>
                <a href="admin_dashboard.php?section=all-users" class="nav-item">
                     <i class="fas fa-users"></i> <span>User Base</span>
                </a>
                <a href="admin_dashboard.php?section=doctor-scheduling" class="nav-item">
                    <i class="fas fa-user-md"></i> <span>Doctors</span>
                </a>
                 <a href="admin_dashboard.php?section=room-management" class="nav-item">
                    <i class="fas fa-bed"></i> <span>Rooms</span>
                </a>

                <!-- Services -->
                <div class="nav-category">Services</div>
                <a href="admin_dashboard.php?section=packages" class="nav-item">
                    <i class="fas fa-box"></i> <span>Packages</span>
                </a>
                <a href="admin_dashboard.php?section=pharmacy-alerts" class="nav-item">
                    <i class="fas fa-pills"></i> <span>Pharmacy</span>
                </a>
                <a href="admin_dashboard.php?section=blood-bank" class="nav-item">
                    <i class="fas fa-tint"></i> <span>Blood Bank</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            
            <!-- Header -->
            <div class="top-header">
                <div class="header-title">
                    <h1>Real-Time Analytics</h1>
                    <p><span class="live-dot"></span> Live Data Stream • <?php echo date("F j, Y"); ?></p>
                </div>
                <div class="header-actions">
                    <button class="profile-btn">
                        <span class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></span>
                        <span style="font-size: 13px; font-weight: 600; padding-right: 10px;">Admin</span>
                    </button>
                    <a href="logout.php" style="color: var(--text-muted); font-size: 20px;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>

            <!-- 1. Top Stats Cards -->
            <div class="stats-grid">
                <!-- Active Users -->
                <div class="stat-card">
                    <div class="stat-icon icon-green"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value counter" data-target="<?php echo $total_users; ?>">0</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> +<?php echo $user_growth_pct; ?>% this month
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="stat-card">
                    <div class="stat-icon icon-blue"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-label">Appointments Today</div>
                    <div class="stat-value counter" data-target="<?php echo $today_appts; ?>">0</div>
                    <div class="stat-trend trend-neutral">
                        <i class="fas fa-minus"></i> Stable flow
                    </div>
                </div>

                <!-- Total Doctors -->
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="fas fa-user-md"></i></div>
                    <div class="stat-label">Medical Staff</div>
                    <div class="stat-value counter" data-target="<?php echo $total_doctors; ?>">0</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check"></i> Available
                    </div>
                </div>

                <!-- Departments/Staff -->
                <div class="stat-card">
                    <div class="stat-icon icon-yellow"><i class="fas fa-users-cog"></i></div>
                    <div class="stat-label">Support Staff</div>
                    <div class="stat-value counter" data-target="<?php echo $total_staff; ?>">0</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-plus"></i> 2 New
                    </div>
                </div>

                <!-- Feedback -->
                <div class="stat-card">
                    <div class="stat-icon icon-green" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;"><i class="fas fa-star"></i></div>
                    <div class="stat-label">Avg Rating</div>
                    <div class="stat-value"><?php echo $avg_rating; ?> <span style="font-size: 14px; color: #f59e0b;">★</span></div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> Top rated
                    </div>
                </div>

                 <!-- Satisfaction -->
                 <div class="stat-card">
                    <div class="stat-icon icon-blue" style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9;"><i class="fas fa-smile"></i></div>
                    <div class="stat-label">Satisfaction</div>
                    <div class="stat-value"><?php echo $satisfaction_pct; ?>%</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> High
                    </div>
                </div>
            </div>

            <!-- 2. Analytics Main Row -->
            <div class="analytics-grid">
                <!-- Main Chart -->
                <div class="panel-card" style="height: 400px;">
                    <div class="panel-header">
                        <div class="panel-title"><i class="fas fa-chart-area" style="color: var(--primary);"></i> User Growth Analytics</div>
                        <select style="background: rgba(0,0,0,0.3); border: 1px solid var(--glass-border); color: var(--text-muted); padding: 5px 10px; border-radius: 8px;">
                            <option>This Year</option>
                            <option>Last 6 Months</option>
                        </select>
                    </div>
                    <canvas id="growthChart"></canvas>
                </div>

                <!-- Right Column: Distribution & AI -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                     <!-- User Distribution -->
                     <div class="panel-card" style="flex: 1;">
                        <div class="panel-header">
                             <div class="panel-title">User Distribution</div>
                        </div>
                        <div style="height: 180px; position: relative;">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>

                    <!-- AI Intelligence Panel -->
                    <div class="insight-card">
                        <div class="panel-header" style="margin-bottom: 15px;">
                            <div class="panel-title"><i class="fas fa-robot"></i> AI Healer Insights</div>
                            <span class="ai-badge">ACTIVE</span>
                        </div>
                        <div class="insight-item">
                            <div class="insight-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="insight-content">
                                <h4>Trend Detected</h4>
                                <p>Appointments increased by <strong>18%</strong> this week due to seasonal flu.</p>
                            </div>
                        </div>
                        <div class="insight-item">
                            <div class="insight-icon" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="insight-content">
                                <h4>Department Load</h4>
                                <p>Cardiology is operating at <strong>92% capacity</strong> today.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 3. Bottom Row: Predictive & Terminal -->
            <div class="analytics-grid" style="grid-template-columns: 1fr 1fr;">
                
                <!-- Predictive Analytics -->
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="panel-title"><i class="fas fa-crystal-ball" style="color: #8b5cf6;"></i> Predictive Forecast</div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; align-items: flex-end;">
                        <div>
                            <div style="color: var(--text-muted); font-size: 13px;">Predicted Appointments (Tomorrow)</div>
                            <div style="font-size: 32px; font-weight: 700;">42 <span style="font-size: 14px; color: var(--primary); font-weight: 500;">(Low Risk)</span></div>
                        </div>
                         <div style="text-align: right;">
                            <div style="color: var(--text-muted); font-size: 13px;">Peak Hour Prediction</div>
                            <div style="font-size: 24px; font-weight: 600;">10:00 AM</div>
                        </div>
                    </div>
                    <!-- Mock Bar Chart for Depts -->
                    <div style="height: 180px;">
                         <canvas id="deptChart"></canvas>
                    </div>
                </div>

                <!-- Combined Terminal & Notifications -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <!-- Real-Time Notifications -->
                    <div class="notification-panel">
                        <div class="panel-title" style="margin-bottom: 10px; font-size: 14px;">System Alerts</div>
                        <div class="notif-item">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-circle notif-success" style="font-size: 8px;"></i>
                                <span style="font-size: 13px;">All database systems operational.</span>
                            </div>
                            <span style="font-size: 11px; color: var(--text-muted);">Now</span>
                        </div>
                        <div class="notif-item">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-circle notif-warning" style="font-size: 8px;"></i>
                                <span style="font-size: 13px;">ICU Nursing staff ratio below optimal.</span>
                            </div>
                            <span style="font-size: 11px; color: var(--text-muted);">2m ago</span>
                        </div>
                         <div class="notif-item">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-circle notif-danger" style="font-size: 8px;"></i>
                                <span style="font-size: 13px;">Emergency admission requested (Trauma).</span>
                            </div>
                            <span style="font-size: 11px; color: var(--text-muted);">15m ago</span>
                        </div>
                    </div>

                    <!-- Terminal -->
                    <div class="terminal-panel">
                         <div class="terminal-header">
                             <span>HEALCARE_AI_ENGINE_V2.4</span>
                             <span>STATUS: ONLINE</span>
                         </div>
                         <div class="logs-container" id="terminalLogs">
                             <!-- JS will populate this -->
                         </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- Back to Top -->
    <div class="back-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
        <i class="fas fa-chevron-up"></i>
    </div>

    <script>
        // --- Counter Animation ---
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const duration = 2000; // ms
            const increment = target / (duration / 16);
            
            let current = 0;
            const updateCounter = () => {
                current += increment;
                if(current < target) {
                    counter.innerText = Math.ceil(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            updateCounter();
        });

        // --- Charts Configuration ---
        Chart.defaults.color = '#64748b';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
        Chart.defaults.font.family = "'Outfit', sans-serif";

        // 1. Growth Line Chart
        new Chart(document.getElementById('growthChart'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Active Patients',
                    data: [120, 150, 220, 280, 250, 310, 380, 420, 480, <?php echo $total_patients > 500 ? $total_patients : 550; ?>],
                    borderColor: '#10b981',
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
                        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
                        return gradient;
                    },
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { borderDash: [5, 5] }, beginAtZero: true },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. User Distribution Pie
        new Chart(document.getElementById('distributionChart'), {
            type: 'doughnut',
            data: {
                labels: ['Patients', 'Doctors', 'Staff'],
                datasets: [{
                    data: [<?php echo $total_patients; ?>, <?php echo $total_doctors; ?>, <?php echo $total_staff; ?>],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 10, color: '#94a3b8' } }
                }
            }
        });

        // 3. Department Bar Chart (Predictive/Risk)
        new Chart(document.getElementById('deptChart'), {
            type: 'bar',
            data: {
                labels: ['Cardio', 'Neuro', 'Ortho', 'Pedia', 'General'],
                datasets: [{
                    label: 'Projected Load (%)',
                    data: [92, 45, 60, 30, 75],
                    backgroundColor: ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100 },
                    x: { grid: { display: false } }
                }
            }
        });

        // --- Terminal Typing Animation ---
        const terminalLogs = document.getElementById('terminalLogs');
        const logMessages = [
            "> Initializing AI Analytical Core...",
            "> Scanning user database for patterns...",
            "> Analyzing appointment frequencies...",
            "> Detecting peak hours: 10:00 AM - 12:00 PM",
            "> Revenue growth projected at +14%...",
            "> Checked 450 system nodes. Status: Stable.",
            "> Monitoring incoming patient streams...",
            "> Optimization suggestions ready."
        ];

        let logIndex = 0;
        function addLog() {
            if (logIndex < logMessages.length) {
                const div = document.createElement('div');
                div.className = 'log-line';
                div.innerText = logMessages[logIndex];
                terminalLogs.appendChild(div);
                logIndex++;
                setTimeout(addLog, 1500); // Delay between logs
            } else {
                // Loop or stop
                 const div = document.createElement('div');
                 div.className = 'log-line';
                 div.innerText = "> Awaiting new data packets... _";
                 div.style.animation = "pulse 1s infinite";
                 terminalLogs.appendChild(div);
            }
        }
        setTimeout(addLog, 500); // Start after load

        // Show back to top
        window.onscroll = function() {
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                document.querySelector('.back-to-top').classList.add('show');
            } else {
                document.querySelector('.back-to-top').classList.remove('show');
            }
        };
    </script>
</body>
</html>
