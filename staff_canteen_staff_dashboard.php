<?php
session_start();
include 'includes/db_connect.php';

// Check for canteen staff role
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle POST actions
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Status Update
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE canteen_orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            $success_msg = "Order #$order_id status updated to $new_status!";
        }
    }

    // 2. Menu Item Management
    if (isset($_POST['save_menu_item'])) {
        $name = $_POST['food_name'];
        $cat = $_POST['meal_category'];
        $diet = $_POST['diet_type'];
        $price = $_POST['price'];
        $desc = $_POST['description'];
        $avail = $_POST['availability'];
        $mid = $_POST['menu_id'] ?? null;

        if ($mid) {
            $stmt = $conn->prepare("UPDATE canteen_menu SET item_name=?, item_category=?, diet_type=?, price=?, description=?, availability=? WHERE menu_id=?");
            $stmt->bind_param("sssdssi", $name, $cat, $diet, $price, $desc, $avail, $mid);
        } else {
            $stmt = $conn->prepare("INSERT INTO canteen_menu (item_name, item_category, diet_type, price, description, availability) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdss", $name, $cat, $diet, $price, $desc, $avail);
        }
        if ($stmt->execute()) {
            $success_msg = $mid ? "Menu item updated!" : "New menu item added!";
        }
    }

    if (isset($_POST['delete_menu_item'])) {
        $mid = $_POST['menu_id'];
        $stmt = $conn->prepare("DELETE FROM canteen_menu WHERE menu_id = ?");
        $stmt->bind_param("i", $mid);
        if ($stmt->execute()) {
            $success_msg = "Menu item deleted!";
        }
    }

    // 3. Profile Update
    if (isset($_POST['update_profile'])) {
        $new_name = $_POST['full_name'];
        $new_phone = $_POST['phone'];
        $stmt = $conn->prepare("UPDATE registrations r JOIN users u ON r.registration_id = u.registration_id SET r.name = ?, r.phone = ? WHERE u.user_id = ?");
        $stmt->bind_param("ssi", $new_name, $new_phone, $user_id);
        if ($stmt->execute()) {
            $success_msg = "Profile updated successfully!";
        }
    }

    // 4. Password Update
    if (isset($_POST['update_password'])) {
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE registrations r JOIN users u ON r.registration_id = u.registration_id SET r.password = ? WHERE u.user_id = ?");
        $stmt->bind_param("si", $new_pass, $user_id);
        if ($stmt->execute()) {
            $success_msg = "Password changed successfully!";
        }
    }
}

$section = $_GET['section'] ?? 'active_orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Panel - HealCare Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #020617;
            --bg-sidebar: #020617;
            --bg-card: #0f172a;
            --accent: #4fc3f7;
            --text-main: #fff;
            --text-dim: #94a3b8;
            --border: rgba(255, 255, 255, 0.05);
            --sidebar-width: 280px;
        }

        body { font-family: 'Poppins', sans-serif; background: var(--bg-deep); color: var(--text-main); margin: 0; display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: var(--bg-sidebar); border-right: 1px solid var(--border); position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1001; }
        .sidebar-header { padding: 30px; display: flex; align-items: center; gap: 15px; }
        .brand-icon { background: var(--accent); color: #fff; width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; }
        .brand-text { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }

        .nav-links { flex: 1; padding: 20px 0; }
        .nav-link { 
            display: flex; align-items: center; padding: 16px 30px; 
            color: var(--text-dim); text-decoration: none; font-size: 15px; 
            font-weight: 500; transition: 0.3s; gap: 15px; border-left: 4px solid transparent; 
        }
        .nav-link i { width: 22px; font-size: 18px; text-align: center; }
        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.02); }
        .nav-link.active { 
            background: rgba(79, 195, 247, 0.08); 
            color: var(--accent); 
            border-left: 4px solid var(--accent); 
        }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }
        .top-navbar { height: 80px; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000; }
        .page-title { font-size: 20px; font-weight: 700; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar { width: 35px; height: 35px; background: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent); }

        .content-body { padding: 40px; }

        /* Tables & Cards */
        .card { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border); padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th { text-align: left; padding: 15px; color: var(--text-dim); border-bottom: 2px solid var(--border); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .data-table td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-placed { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-preparing { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-delivered { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }

        .btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; transition: 0.3s; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--accent); color: #000; }
        .btn-success { background: #10b981; color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: #fff; }
        .btn:hover { filter: brightness(1.1); transform: translateY(-2px); }

        /* Form Controls */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-size: 13px; color: var(--text-dim); }
        .form-input { width: 100%; background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 12px; border-radius: 10px; color: #fff; outline: none; }
        .form-input:focus { border-color: var(--accent); }

        /* Notifications */
        .banner { padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; font-weight: 600; }
        .banner-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }

        /* Modal placeholder */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 2000; }
        .modal { background: #0f172a; background-image: radial-gradient(at top left, #1e293b, #0f172a); width: 500px; padding: 40px; border-radius: 20px; border: 1px solid var(--border); position: relative; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand-icon">C</div>
            <div class="brand-text">Canteen Panel</div>
        </div>
        <nav class="nav-links">
            <a href="?section=active_orders" class="nav-link <?php echo $section == 'active_orders' ? 'active' : ''; ?>">
                <i class="fas fa-list-ul"></i> Active Orders
            </a>
            <a href="?section=menu_management" class="nav-link <?php echo $section == 'menu_management' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> Menu Management
            </a>
            <a href="?section=order_history" class="nav-link <?php echo $section == 'order_history' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Order History
            </a>
            <a href="reports_manager.php" class="nav-link">
                <i class="fas fa-chart-line"></i> Sales Analytics
            </a>
            <a href="?section=profile" class="nav-link <?php echo $section == 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Profile Settings
            </a>
        </nav>
        <div style="padding: 20px;">
            <a href="logout.php" class="nav-link" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-navbar">
            <div class="page-title">
                <?php 
                    if($section == 'active_orders') echo "Active Orders Module";
                    elseif($section == 'menu_management') echo "Menu Management Module";
                    elseif($section == 'order_history') echo "Order History Module";
                    elseif($section == 'profile') echo "Profile Settings Module";
                ?>
            </div>
            <div class="user-info">
                <span style="font-size: 14px; color: var(--text-dim);">HealCare Canteen Staff</span>
                <div class="user-avatar"><i class="fas fa-user"></i></div>
            </div>
        </header>

        <section class="content-body">
            <!-- Quick Archive -->
            <div style="background: linear-gradient(135deg, #0f172a, #1e293b); padding: 25px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="color: #fff; margin-bottom: 5px; font-size: 16px;"><i class="fas fa-file-upload" style="color: #4fc3f7;"></i> Canteen Sales Analytics</h3>
                    <p style="color: #94a3b8; font-size: 12px;">Archive manual billing summaries or monthly food waste reports.</p>
                </div>
                <a href="reports_manager.php?view=repository" style="background: #4fc3f7; color: #020617; text-decoration: none; padding: 12px 25px; border-radius: 12px; font-weight: 700; font-size: 12px;">Archive Report</a>
            </div>
            <?php if ($success_msg): ?>
                <div class="banner banner-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if ($section == 'active_orders'): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin: 0;">Live Orders Tracker</h2>
                        <button onclick="window.location.reload()" class="btn btn-outline"><i class="fas fa-sync"></i> Refresh Updates</button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Patient Info</th>
                                <th>Meal & Items</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $active_orders = $conn->query("
                                SELECT co.*, cm.item_name, cm.item_category, cm.diet_type as item_diet,
                                       COALESCE(pp.name, r.name) as pname, COALESCE(pp.patient_code, 'N/A') as pcode
                                FROM canteen_orders co
                                JOIN canteen_menu cm ON co.menu_id = cm.menu_id
                                JOIN users u ON co.patient_id = u.user_id
                                LEFT JOIN registrations r ON u.registration_id = r.registration_id
                                LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
                                WHERE co.order_status IN ('Placed', 'Preparing')
                                ORDER BY co.created_at DESC
                            ");
                            if ($active_orders && $active_orders->num_rows > 0):
                                while ($o = $active_orders->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><strong style="color: var(--accent);">#<?php echo $o['order_id']; ?></strong></td>
                                    <td>
                                        <div style="font-weight: 700;"><?php echo htmlspecialchars($o['pname']); ?></div>
                                        <div style="font-size: 11px; color: var(--text-dim);">ID: <?php echo $o['pcode']; ?> | Location: <?php echo $o['delivery_location'] ?: 'OPD'; ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo $o['item_name']; ?> <small style="color: var(--accent);">(<?php echo $o['item_category']; ?>)</small></div>
                                        <div style="font-size: 11px; color: #4fc3f7;">Diet: <?php echo $o['item_diet'] ?: 'Normal'; ?></div>
                                    </td>
                                    <td><?php echo date('h:i A', strtotime($o['order_time'])); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($o['order_status']); ?>"><?php echo $o['order_status']; ?></span></td>
                                    <td>
                                        <form method="POST" style="display: flex; gap: 5px;">
                                            <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                            <?php if ($o['order_status'] == 'Placed'): ?>
                                                <button type="submit" name="new_status" value="Preparing" class="btn btn-primary btn-sm">Accept & Prep</button>
                                            <?php elseif ($o['order_status'] == 'Preparing'): ?>
                                                <button type="submit" name="new_status" value="Delivered" class="btn btn-success btn-sm">Deliver</button>
                                            <?php endif; ?>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-dim);">No active orders at the moment.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($section == 'menu_management'): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2 style="margin: 0;">Food Menu Management</h2>
                        <button onclick="openModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Food Item</button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Food Name</th>
                                <th>Category</th>
                                <th>Diet Type</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $menu = $conn->query("SELECT * FROM canteen_menu ORDER BY item_category, item_name");
                            while ($m = $menu->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($m['item_name']); ?></strong></td>
                                    <td><?php echo $m['item_category']; ?></td>
                                    <td><?php echo $m['diet_type'] ?: 'Any'; ?></td>
                                    <td>₹<?php echo number_format($m['price'], 0); ?></td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo $m['availability'] == 'Available' ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>; color: <?php echo $m['availability'] == 'Available' ? '#10b981' : '#ef4444'; ?>;">
                                            <?php echo $m['availability']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <button onclick='editItem(<?php echo json_encode($m); ?>)' class="btn btn-outline" style="padding: 5px 10px;"><i class="fas fa-edit"></i></button>
                                            <form method="POST" onsubmit="return confirm('Delete this item?');">
                                                <input type="hidden" name="menu_id" value="<?php echo $m['menu_id']; ?>">
                                                <button name="delete_menu_item" class="btn btn-outline" style="padding: 5px 10px; color: #ef4444; border-color: rgba(239,68,68,0.2);"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($section == 'order_history'): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2 style="margin: 0;">Past Orders & History</h2>
                        <form method="GET" style="display: flex; gap: 15px;">
                            <input type="hidden" name="section" value="order_history">
                            <input type="date" name="filter_date" value="<?php echo $_GET['filter_date'] ?? ''; ?>" class="form-input" style="width: 150px; padding: 8px;" onchange="this.form.submit()">
                            <select name="filter_cat" class="form-input" style="width: 170px; padding: 8px;" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <option <?php echo (($_GET['filter_cat'] ?? '') == 'Morning / Breakfast') ? 'selected' : ''; ?>>Morning / Breakfast</option>
                                <option <?php echo (($_GET['filter_cat'] ?? '') == 'Lunch') ? 'selected' : ''; ?>>Lunch</option>
                                <option <?php echo (($_GET['filter_cat'] ?? '') == 'Evening Snacks') ? 'selected' : ''; ?>>Evening Snacks</option>
                                <option <?php echo (($_GET['filter_cat'] ?? '') == 'Dinner') ? 'selected' : ''; ?>>Dinner</option>
                                <option <?php echo (($_GET['filter_cat'] ?? '') == 'Night Food') ? 'selected' : ''; ?>>Night Food</option>
                            </select>
                        </form>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Delivered Time</th>
                                <th>Patient</th>
                                <th>Ordered Item</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $filter_date = $_GET['filter_date'] ?? '';
                            $filter_cat = $_GET['filter_cat'] ?? '';

                            $history_query = "
                                SELECT co.*, cm.item_name, COALESCE(pp.name, r.name) as pname
                                FROM canteen_orders co
                                JOIN canteen_menu cm ON co.menu_id = cm.menu_id
                                JOIN users u ON co.patient_id = u.user_id
                                LEFT JOIN registrations r ON u.registration_id = r.registration_id
                                LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
                                WHERE co.order_status = 'Delivered'
                            ";

                            if ($filter_date) {
                                $history_query .= " AND co.order_date = '$filter_date'";
                            }
                            if ($filter_cat) {
                                $history_query .= " AND cm.item_category = '$filter_cat'";
                            }

                            $history_query .= " ORDER BY co.updated_at DESC";
                            $history = $conn->query($history_query);
                            
                            if ($history && $history->num_rows > 0):
                                while ($h = $history->fetch_assoc()):
                            ?>
                                <tr>
                                    <td>#<?php echo $h['order_id']; ?></td>
                                    <td><?php echo date('M d, h:i A', strtotime($h['updated_at'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($h['pname']); ?></strong></td>
                                    <td><?php echo $h['item_name']; ?></td>
                                    <td>₹<?php echo number_format($h['total_amount'], 0); ?></td>
                                    <td><span class="status-badge status-delivered">DELIVERED</span></td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-dim);">No past orders found matching the filters.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($section == 'profile'): ?>
                <div style="max-width: 600px;">
                    <div class="card">
                        <h3>Update My Information</h3>
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-input" placeholder="Update your phone">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>

                    <div class="card">
                        <h3>Security & Password</h3>
                        <form method="POST">
                            <input type="hidden" name="update_password" value="1">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-input" placeholder="Min 6 characters" required>
                            </div>
                            <button type="submit" class="btn btn-outline" style="border-color: var(--accent); color: var(--accent);">Change Password</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Menu Modal -->
    <div id="menuModal" class="modal-overlay">
        <div class="modal">
            <h3 id="modalTitle">Add Food Item</h3>
            <form method="POST">
                <input type="hidden" name="save_menu_item" value="1">
                <input type="hidden" name="menu_id" id="m_id">
                <div class="form-group">
                    <label class="form-label">Food Name</label>
                    <input type="text" name="food_name" id="m_name" class="form-input" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Meal Category</label>
                        <select name="meal_category" id="m_cat" class="form-input">
                            <option>Morning / Breakfast</option>
                            <option>Lunch</option>
                            <option>Evening Snacks</option>
                            <option>Dinner</option>
                            <option>Night Food</option>
                            <option>Other Food Items</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Diet Recommend</label>
                        <select name="diet_type" id="m_diet" class="form-input">
                            <option value="Normal">Normal</option>
                            <option value="Diabetic">Diabetic</option>
                            <option value="Low-Salt">Low-Salt</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" step="0.01" name="price" id="m_price" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Availability</label>
                        <select name="availability" id="m_avail" class="form-input">
                            <option>Available</option>
                            <option>Out of Stock</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="m_desc" class="form-input" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Menu Item</button>
                    <button type="button" onclick="closeModal()" class="btn btn-outline" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalTitle').innerText = "Add New Food Item";
            document.getElementById('m_id').value = "";
            document.getElementById('m_name').value = "";
            document.getElementById('m_price').value = "";
            document.getElementById('m_desc').value = "";
            document.getElementById('menuModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('menuModal').style.display = 'none';
        }
        function editItem(item) {
            document.getElementById('modalTitle').innerText = "Edit Food Item";
            document.getElementById('m_id').value = item.menu_id;
            document.getElementById('m_name').value = item.item_name;
            document.getElementById('m_cat').value = item.item_category;
            document.getElementById('m_diet').value = item.diet_type;
            document.getElementById('m_price').value = item.price;
            document.getElementById('m_avail').value = item.availability;
            document.getElementById('m_desc').value = item.description;
            document.getElementById('menuModal').style.display = 'flex';
        }

        // Auto reload Active Orders every minute & Notification
        <?php if($section == 'active_orders'): ?>
        let currentOrderCount = <?php echo $active_orders ? $active_orders->num_rows : 0; ?>;
        
        setInterval(() => {
            fetch('api_check_orders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.count > currentOrderCount) {
                        // Play notification sound
                        let audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                        audio.play().catch(e => console.log("Audio play blocked"));
                        
                        // Optional: Browser alert or toast
                        alert("🔔 New Order Received!");
                        location.reload();
                    } else {
                        location.reload();
                    }
                });
        }, 60000); 
        <?php endif; ?>
    </script>
</body>
</html>
