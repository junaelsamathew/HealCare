<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent-green: #4fc3f7; /* Changed to Blue */
            --border-soft: rgba(255, 255, 255, 0.05);
        }

        .reception-top-bar { background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .secondary-nav { background: #0f172a; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .dashboard-body { display: grid; grid-template-columns: 260px 1fr; height: calc(100vh - 140px); background: #020617; }
        .side-nav { background: #020617; padding: 20px 0; border-right: 1px solid var(--border-soft); }
        .nav-item { display: flex; align-items: center; padding: 15px 30px; color: #94a3b8; text-decoration: none; font-size: 14px; gap: 15px; transition: 0.3s; }
        .nav-item.active { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; border-left: 4px solid #4fc3f7; }
        .main-ops { padding: 40px; overflow-y: auto; }
        
        .medicine-card {
            background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 25px;
            display: flex; flex-direction: column; gap: 15px; border-left: 4px solid #4fc3f7;
        }
        .stock-alert { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-size: 13px; display: flex; justify-content: space-between; align-items: center; }
        
        .inventory-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        .inventory-table th { text-align: left; padding: 12px; color: #64748b; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--border-soft); }
        .inventory-table td { padding: 15px 12px; color: #cbd5e1; font-size: 13px; border-bottom: 1px solid var(--border-soft); }
        .stock-low { color: #ef4444; font-weight: bold; }
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
        <div style="display: flex; align-items: center; gap: 15px;"><div style="background: #4fc3f7; color:#fff; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold;">P</div><h2 style="color:#fff; font-size:20px;">Pharmacy Panel</h2></div>
        <div style="display: flex; align-items: center;"><span class="staff-label" style="color: #94a3b8; font-size: 14px; margin-right: 15px;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span><a href="logout.php" style="color: #94a3b8; text-decoration: none; border: 1px solid #4fc3f7; padding: 5px 20px; border-radius: 20px;">Log Out</a></div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <a href="#" class="nav-item active"><i class="fas fa-clipboard-list"></i> Prescriptions</a>
            <a href="#" class="nav-item"><i class="fas fa-pills"></i> Inventory / Stock</a>
            <a href="#" class="nav-item"><i class="fas fa-history"></i> Dispensed History</a>
            <a href="reports_manager.php" class="nav-item"><i class="fas fa-chart-line"></i> Pharmacy Reports</a>
            <a href="#" class="nav-item"><i class="fas fa-bell"></i> Expiry Alerts</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <!-- Quick Archive -->
            <div style="background: linear-gradient(135deg, #0f172a, #1e293b); padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="color: #fff; margin-bottom: 5px; font-size: 16px;"><i class="fas fa-file-upload" style="color: #4fc3f7;"></i> Pharmacy Sales Data</h3>
                    <p style="color: #64748b; font-size: 12px;">Archive monthly inventory financial reports or narcotics logs.</p>
                </div>
                <a href="reports_manager.php?view=repository" style="background: #4fc3f7; color: #020617; text-decoration: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 12px;">Archive Report</a>
            </div>
            <div class="stock-alert">
                <span><i class="fas fa-exclamation-circle"></i> <strong>Critical Stock Alert:</strong> Paracetamol 500mg (Batch #990) is below threshold.</span>
                <button style="background: #ef4444; color: white; border: none; padding: 8px 15px; border-radius: 8px; font-size: 11px; font-weight: bold; cursor: pointer;" onclick="alert('Notification sent to Admin & Procurement')">Notify Admin</button>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
                <!-- Prescription Queue -->
                <div>
                    <h3 style="color:#fff; margin-bottom: 20px;">Prescription Processing Queue</h3>
                    <div class="medicine-card">
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <span style="font-size: 11px; color: #4fc3f7; font-weight: 800; text-transform: uppercase;">ID: #RX-2026-0042</span>
                                <h4 style="color:#fff; margin: 5px 0; font-size: 18px;">Ravi Sharma</h4>
                                <p style="font-size: 13px; color: #94a3b8;">Requested by: Dr. Sathish (ENT)</p>
                            </div>
                            <span style="color: #4fc3f7; font-size: 11px; font-weight: bold;">TIME: 10:45 AM</span>
                        </div>
                        <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 13px; color: #cbd5e1;">
                                <thead>
                                    <tr style="text-align: left; border-bottom: 1px solid var(--border-soft);">
                                        <th style="padding-bottom: 10px;">Medicine Name</th>
                                        <th style="padding-bottom: 10px;">Dosage</th>
                                        <th style="padding-bottom: 10px;">Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td style="padding: 10px 0;">Paracetamol 500mg</td><td>1-0-1</td><td>10</td></tr>
                                    <tr><td style="padding: 10px 0;">Amoxicillin 250mg</td><td>1-1-1</td><td>15</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <span style="color: #94a3b8; font-size: 13px;">Estimated Bill: <strong>₹450.00</strong></span>
                            <div style="display: flex; gap: 10px;">
                                <button style="background: transparent; border: 1px solid var(--border-soft); color: #fff; padding: 10px 20px; border-radius: 10px; cursor: pointer;">Print Rx</button>
                                <button style="background: #4fc3f7; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer;">Dispense & Bill</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Alerts & Expiry -->
                <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 16px; padding: 25px;">
                    <h4 style="color: #fff; margin-bottom: 20px; font-size: 15px;"><i class="fas fa-warehouse"></i> Inventory Snapshot</h4>
                    <table class="inventory-table">
                        <thead>
                            <tr><th>Item</th><th>Stock</th><th>Expiry</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Paracetamol</td><td class="stock-low">12</td><td>Dec 2026</td></tr>
                            <tr><td>Cough Syrup</td><td>145</td><td>Jun 2027</td></tr>
                            <tr><td>Insulin Vials</td><td class="stock-low">05</td><td>Oct 2026</td></tr>
                        </tbody>
                    </table>
                    <button style="width: 100%; margin-top: 25px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-soft); color: #fff; padding: 12px; border-radius: 10px; cursor: pointer; font-size: 12px;">Full Inventory Report</button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
