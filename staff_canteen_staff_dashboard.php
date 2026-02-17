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
        $stock = (int)$_POST['stock_quantity'];
        
        // Auto-set availability based on stock
        $avail = ($stock > 0) ? 'Available' : 'Out of Stock';

        $mid = $_POST['menu_id'] ?? null;

        if ($mid) {
            $stmt = $conn->prepare("UPDATE canteen_menu SET item_name=?, item_category=?, diet_type=?, price=?, description=?, stock_quantity=?, availability=? WHERE menu_id=?");
            $stmt->bind_param("sssdssii", $name, $cat, $diet, $price, $desc, $stock, $avail, $mid);
        } else {
            $stmt = $conn->prepare("INSERT INTO canteen_menu (item_name, item_category, diet_type, price, description, stock_quantity, availability) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdsis", $name, $cat, $diet, $price, $desc, $stock, $avail);
        }
        if ($stmt->execute()) {
            $success_msg = $mid ? "Menu item updated!" : "New menu item added!";
        }
    }

    // 3. Restock Item (Authorized)
    if (isset($_POST['restock_item'])) {
        $mid = $_POST['menu_id'];
        $qty_added = (int)$_POST['add_quantity'];
        $pin = $_POST['auth_pin'];

        // Simple authorized PIN check (In real app, fetch from localized config or user role)
        if ($pin === '1234') { 
            // Update stock and auto-set availability
            $stmt = $conn->prepare("UPDATE canteen_menu SET stock_quantity = stock_quantity + ?, availability = 'Available' WHERE menu_id = ?");
            $stmt->bind_param("ii", $qty_added, $mid);
            if ($stmt->execute()) {
                $success_msg = "Stock added successfully!";
            }
        } else {
            $error_msg = "Unauthorized! Incorrect Admin/Manager PIN.";
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
            <a href="?section=reports" class="nav-link <?php echo $section == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Reports & Analytics
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
                    elseif($section == 'reports') echo "Reports & Analytics Module";
                    elseif($section == 'profile') echo "Profile Settings Module";
                ?>
            </div>
            <div class="user-info">
                <span style="font-size: 14px; color: var(--text-dim);">HealCare Canteen Staff</span>
                <div class="user-avatar"><i class="fas fa-user"></i></div>
            </div>
        </header>

        <section class="content-body">
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
                    <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid var(--border);">
                        <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                            <input type="hidden" name="section" value="menu_management">
                            
                            <div style="flex: 1; min-width: 200px;">
                                <input type="text" name="search" placeholder="Search food name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="form-input">
                            </div>

                            <div style="min-width: 150px;">
                                <select name="category" class="form-input">
                                    <option value="">All Categories</option>
                                    <option value="Morning / Breakfast" <?php if(($_GET['category']??'') == 'Morning / Breakfast') echo 'selected'; ?>>Breakfast</option>
                                    <option value="Lunch" <?php if(($_GET['category']??'') == 'Lunch') echo 'selected'; ?>>Lunch</option>
                                    <option value="Evening Snacks" <?php if(($_GET['category']??'') == 'Evening Snacks') echo 'selected'; ?>>Snacks</option>
                                    <option value="Dinner" <?php if(($_GET['category']??'') == 'Dinner') echo 'selected'; ?>>Dinner</option>
                                    <option value="Night Food" <?php if(($_GET['category']??'') == 'Night Food') echo 'selected'; ?>>Night Food</option>
                                    <option value="Other Food Items" <?php if(($_GET['category']??'') == 'Other Food Items') echo 'selected'; ?>>Others</option>
                                </select>
                            </div>

                            <div style="min-width: 150px;">
                                <select name="diet" class="form-input">
                                    <option value="">All Diets</option>
                                    <option value="Normal" <?php if(($_GET['diet']??'') == 'Normal') echo 'selected'; ?>>Normal</option>
                                    <option value="Diabetic" <?php if(($_GET['diet']??'') == 'Diabetic') echo 'selected'; ?>>Diabetic</option>
                                    <option value="Low-Salt" <?php if(($_GET['diet']??'') == 'Low-Salt') echo 'selected'; ?>>Low-Salt</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-outline" style="color: var(--accent); border-color: var(--accent);"><i class="fas fa-search"></i> Filter</button>
                            
                            <?php if(isset($_GET['search']) || isset($_GET['category']) || isset($_GET['diet'])): ?>
                                <a href="?section=menu_management" class="btn btn-outline" style="color: #ef4444; border-color: #ef4444;"><i class="fas fa-times"></i> Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                        <?php
                        $stats = $conn->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN stock_quantity > 0 THEN 1 ELSE 0 END) as in_stock,
                            SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) as out_stock
                            FROM canteen_menu")->fetch_assoc();
                        ?>
                        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 10px 20px; border-radius: 10px; color: #3b82f6; font-size: 13px; font-weight: 600;">
                            Total Items: <?php echo $stats['total'] ?? 0; ?>
                        </div>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 10px 20px; border-radius: 10px; color: #10b981; font-size: 13px; font-weight: 600;">
                            In Stock: <?php echo $stats['in_stock'] ?? 0; ?>
                        </div>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 10px 20px; border-radius: 10px; color: #ef4444; font-size: 13px; font-weight: 600;">
                            Out of Stock: <?php echo $stats['out_stock'] ?? 0; ?>
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Food Name</th>
                                <th>Category</th>
                                <th>Diet Type</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $where_clauses = [];
                            if (!empty($_GET['search'])) {
                                $search = $conn->real_escape_string($_GET['search']);
                                $where_clauses[] = "item_name LIKE '%$search%'";
                            }
                            if (!empty($_GET['category'])) {
                                $cat = $conn->real_escape_string($_GET['category']);
                                $where_clauses[] = "item_category = '$cat'";
                            }
                            if (!empty($_GET['diet'])) {
                                $diet = $conn->real_escape_string($_GET['diet']);
                                $where_clauses[] = "diet_type = '$diet'";
                            }

                            $sql = "SELECT * FROM canteen_menu";
                            if (!empty($where_clauses)) {
                                $sql .= " WHERE " . implode(" AND ", $where_clauses);
                            }
                            $sql .= " ORDER BY item_category, item_name";
                            
                            $menu = $conn->query($sql);
                            if ($menu && $menu->num_rows > 0):
                                while ($m = $menu->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($m['item_name']); ?></strong></td>
                                    <td><?php echo $m['item_category']; ?></td>
                                    <td><?php echo $m['diet_type'] ?: 'Any'; ?></td>
                                    <td>₹<?php echo number_format($m['price'], 0); ?></td>
                                    <td><?php echo $m['stock_quantity']; ?></td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo $m['stock_quantity'] > 0 ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>; color: <?php echo $m['stock_quantity'] > 0 ? '#10b981' : '#ef4444'; ?>;">
                                            <?php echo $m['stock_quantity'] > 0 ? 'Available' : 'Out of Stock'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <button onclick='editItem(<?php echo json_encode($m); ?>)' class="btn btn-action edit"><i class="fas fa-edit"></i> Edit</button>
                                            <button class="btn btn-action" style="background: var(--primary); color: white;" onclick='openRestockModal(<?php echo json_encode($m); ?>)'>
                                                <i class="fas fa-plus-circle"></i> Stock
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Delete this item?');">
                                                <input type="hidden" name="menu_id" value="<?php echo $m['menu_id']; ?>">
                                                <button name="delete_menu_item" class="btn btn-outline" style="padding: 5px 10px; color: #ef4444; border-color: rgba(239,68,68,0.2);"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-dim);">
                                        <i class="fas fa-utensils" style="font-size: 30px; margin-bottom: 10px; opacity: 0.5;"></i><br>
                                        No menu items found matching your filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($section == 'reports'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">Reports & Analytics</h2>
                    <button onclick="openReportModal()" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Report
                    </button>
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
                    <!-- 1. Daily Sales Report -->
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="margin:0; font-size:18px; color:var(--accent);">Daily Sales Report</h3>
                                <p style="color:var(--text-dim); font-size:13px; margin-top:5px;">Revenue tracking & payment modes</p>
                            </div>
                            <i class="fas fa-calendar-day" style="font-size:24px; color:rgba(79, 195, 247, 0.5);"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Total Orders & Sales Amount</li>
                            <li>Cash vs UPI Breakdown</li>
                        </ul>
                        <a href="reports_manager.php?view=reports&type=canteen_daily_sales" class="btn btn-outline" style="width:100%; justify-content:center;">View Report</a>
                    </div>

                    <!-- 2. Item-Wise Sales -->
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="margin:0; font-size:18px; color:#f59e0b;">Item-Wise Sales</h3>
                                <p style="color:var(--text-dim); font-size:13px; margin-top:5px;">Popular items & total quantity</p>
                            </div>
                            <i class="fas fa-hamburger" style="font-size:24px; color:rgba(245, 158, 11, 0.5);"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Quantity Sold per Item</li>
                            <li>Total Amount per Item</li>
                        </ul>
                        <a href="reports_manager.php?view=reports&type=canteen_item_sales" class="btn btn-outline" style="width:100%; justify-content:center;">View Report</a>
                    </div>

                     <!-- 3. Payment Collection -->
                     <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="margin:0; font-size:18px; color:#10b981;">Payment Collection</h3>
                                <p style="color:var(--text-dim); font-size:13px; margin-top:5px;">Cash & UPI reconciliation</p>
                            </div>
                            <i class="fas fa-wallet" style="font-size:24px; color:rgba(16, 185, 129, 0.5);"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Cash Amount Collected</li>
                            <li>Total Daily Collection</li>
                        </ul>
                        <a href="reports_manager.php?view=reports&type=canteen_payments" class="btn btn-outline" style="width:100%; justify-content:center;">View Report</a>
                    </div>

                    <!-- 4. Stock Usage -->
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="margin:0; font-size:18px; color:#ef4444;">Stock Usage</h3>
                                <p style="color:var(--text-dim); font-size:13px; margin-top:5px;">Inventory & Wastage control</p>
                            </div>
                            <i class="fas fa-boxes" style="font-size:24px; color:rgba(239, 68, 68, 0.5);"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Used Quantity vs Remaining</li>
                            <li>Inventory Status</li>
                        </ul>
                        <a href="reports_manager.php?view=reports&type=canteen_stock" class="btn btn-outline" style="width:100%; justify-content:center;">View Report</a>
                    </div>
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
    <!-- Restock Modal -->
    <div id="restockModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="restockTitle">Add Stock</h3>
                <button type="button" class="close-modal" onclick="closeRestockModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="menu_id" id="r_menu_id">
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <input type="text" id="r_item_name" class="form-input" readonly style="background: rgba(255,255,255,0.05); color: #aaa;">
                </div>
                <div class="form-group">
                    <label class="form-label">Add Quantity (Daily Prep)</label>
                    <input type="number" name="add_quantity" class="form-input" min="1" required placeholder="Enter amount to add">
                </div>
                <div class="form-group">
                    <label class="form-label">Manager PIN (Auth Required)</label>
                    <input type="password" name="auth_pin" class="form-input" required placeholder="Enter PIN">
                </div>
                <button type="submit" name="restock_item" class="btn-submit" style="width: 100%;">Confirm Restock</button>
            </form>
        </div>
    </div>

    <!-- Edit/Add Modal -->
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
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="m_stock" class="form-input" value="0" min="0" required>
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
            document.getElementById('m_stock').value = "0";
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
            document.getElementById('m_stock').value = item.stock_quantity;
            document.getElementById('m_desc').value = item.description;
            document.getElementById('menuModal').style.display = 'flex';
        }

        function openRestockModal(item) {
            document.getElementById('r_menu_id').value = item.menu_id;
            document.getElementById('r_item_name').value = item.item_name;
            document.getElementById('restockModal').style.display = 'flex';
        }

        function closeRestockModal() {
            document.getElementById('restockModal').style.display = 'none';
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

    <!-- Report Upload Modal Integration -->
    <?php 
    $staff_type = 'canteen_staff';
    include 'includes/report_upload_modal.php'; 
    ?>
</body>
</html>
