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

// Fetch medical records
$medical_records = [];
if ($profile_exists) {
    $pid = $profile['patient_id'];
    $records_res = $conn->query("SELECT * FROM patient_medical_records WHERE patient_id = $pid ORDER BY visit_date DESC");
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
            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $username); ?></strong></span>
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
                        <div class="appointment-item">
                            <div class="doc-info">
                                <h4>Dr. Sarah Jenny <span class="status-badge status-online">Available Now</span></h4>
                                <p>Cardiologist • Oct 25, 2025 at 10:00 AM</p>
                                <p style="font-size: 12px; margin-top: 5px;"><i class="fas fa-info-circle"></i> Notes: General cardiac checkup and BP review.</p>
                            </div>
                            <div style="text-align: right;">
                                <a href="medical_records.php" style="display: block; font-size: 13px; color: #4fc3f7; text-decoration: none; margin-bottom: 10px;">View History</a>
                                <a href="#" class="action-cancel">Cancel</a>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Reports Download -->
                    <div style="margin-top: 40px;">
                        <div class="section-head"><h3>Recent Lab Reports</h3></div>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px;">
                                <span><i class="fas fa-microscope" style="margin-right: 10px;"></i> Blood Test Report</span>
                                <a href="#" style="color: #4fc3f7; font-size: 14px;"><i class="fas fa-download"></i> PDF</a>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px;">
                                <span><i class="fas fa-x-ray" style="margin-right: 10px;"></i> X-Ray (Chest)</span>
                                <a href="#" style="color: #4fc3f7; font-size: 14px;"><i class="fas fa-download"></i> PDF</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Canteen & Notifications -->
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <a href="#" style="padding: 10px 20px; background: rgba(79, 195, 247, 0.1); border: 1px solid #4fc3f7; border-radius: 8px; color: #4fc3f7; text-decoration: none; font-size: 13px; font-weight: 600;">Submit Feedback</a>
                </div>
            </div>

        </main>
    </div>

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>