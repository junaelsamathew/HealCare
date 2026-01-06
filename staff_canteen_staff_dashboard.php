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
    <title>Canteen Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        :root {
            --bg-deep: #020617;
            --bg-card: #0f172a;
            --accent-orange: #4fc3f7;
            --border-soft: rgba(255, 255, 255, 0.05);
        }

        .reception-top-bar { background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .secondary-nav { background: #0f172a; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .dashboard-body { display: grid; grid-template-columns: 260px 1fr; height: calc(100vh - 140px); background: #020617; }
        .side-nav { background: #020617; padding: 20px 0; border-right: 1px solid var(--border-soft); }
        .nav-item { display: flex; align-items: center; padding: 15px 30px; color: #94a3b8; text-decoration: none; font-size: 14px; gap: 15px; transition: 0.3s; }
        .nav-item.active { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; border-left: 4px solid #4fc3f7; }
        .main-ops { padding: 40px; overflow-y: auto; }

        .order-card {
            background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 25px;
            margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; border-left: 4px solid #4fc3f7;
        }
        .status-badge-ct { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .st-prep { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; }
        .st-ready { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; }

        .menu-item-card {
            background: rgba(255,255,255,0.02); border: 1px solid var(--border-soft); padding: 15px; border-radius: 10px;
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;
        }
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
        <div style="display: flex; align-items: center; gap: 15px;"><div style="background: #4fc3f7; color:#fff; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold;">C</div><h2 style="color:#fff; font-size:20px;">Canteen Panel</h2></div>
        <div style="display: flex; align-items: center;"><span class="staff-label" style="color: #94a3b8; font-size: 14px; margin-right: 15px;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span><a href="logout.php" style="color: #94a3b8; text-decoration: none; border: 1px solid #4fc3f7; padding: 5px 20px; border-radius: 20px;">Log Out</a></div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <a href="#" class="nav-item active"><i class="fas fa-list-ul"></i> Active Orders</a>
            <a href="#" class="nav-item"><i class="fas fa-utensils"></i> Menu Management</a>
            <a href="#" class="nav-item"><i class="fas fa-history"></i> Order History</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <!-- Live Orders -->
                <div>
                    <h3 style="color:#fff; margin-bottom: 20px;">Active Orders & Time-Slots</h3>
                    <div class="order-card">
                        <div>
                            <span class="status-badge-ct st-prep">Preparing</span>
                            <h4 style="color:#fff; margin: 15px 0 5px 0; font-size: 18px;">Patient Combo A</h4>
                            <p style="font-size: 13px; color: #94a3b8; margin-bottom: 15px;">Ward B / Bed 15 • Liquid Diet</p>
                            <div style="font-size: 12px; color: #cbd5e1;"><i class="fas fa-clock"></i> Requested Slot: <strong>08:30 AM - 09:00 AM</strong></div>
                        </div>
                        <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; display: flex; flex-direction: column; gap: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 13px; color: #cbd5e1;">Progress Monitor</span>
                                <span style="color: #4fc3f7; font-size: 11px; font-weight: 800;">75% Cooked</span>
                            </div>
                            <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden;">
                                <div style="width: 75%; height: 100%; background: #4fc3f7;"></div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 5px;">
                                <button style="flex: 1; padding: 10px; background: #4fc3f7; border: none; border-radius: 8px; color: white; font-weight: 700; cursor: pointer;">Mark Ready</button>
                                <button style="flex: 1; padding: 10px; background: #3b82f6; border: none; border-radius: 8px; color: white; font-weight: 700; cursor: pointer;">Out for delivery</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Menu Management Snippet -->
                <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 16px; padding: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h4 style="color: #fff; font-size: 15px;"><i class="fas fa-book-open"></i> Daily Menu</h4>
                        <button style="font-size: 10px; color: #4fc3f7; background: none; border: 1px solid #4fc3f7; padding: 4px 10px; border-radius: 20px; cursor: pointer;">Edit Menu</button>
                    </div>
                    <div class="menu-item-card">
                        <span style="color: #cbd5e1; font-size: 13px;">Oatmeal Porridge</span>
                        <span style="color: #4fc3f7; font-size: 11px; font-weight: bold;">AVAILABLE</span>
                    </div>
                    <div class="menu-item-card">
                        <span style="color: #cbd5e1; font-size: 13px;">Veg Clear Soup</span>
                        <span style="color: #4fc3f7; font-size: 11px; font-weight: bold;">AVAILABLE</span>
                    </div>
                    <div class="menu-item-card" style="opacity: 0.5;">
                        <span style="color: #cbd5e1; font-size: 13px;">Paneer Butter Masala</span>
                        <span style="color: #ef4444; font-size: 11px; font-weight: bold;">SOLD OUT</span>
                    </div>
                    <button style="width: 100%; margin-top: 20px; background: #4fc3f7; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer;">Update Availability</button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
