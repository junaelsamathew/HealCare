<?php
session_start();
include 'includes/db_connect.php';
include 'includes/email_config.php';

// Auth Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';
$current_user_id = (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) ? $_SESSION['user_id'] : 1;

// --- POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create_po') {
        $supplier_id = (int)$_POST['supplier_id'];
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO purchase_orders (supplier_id, order_date, status, created_by, notes) VALUES ($supplier_id, CURDATE(), 'Pending', $current_user_id, '$notes')");
            $po_id = $conn->insert_id;
            
            $items = $_POST['items'];
            $qtys = $_POST['quantities'];
            $prices = $_POST['prices'];
            $total = 0;
            
            foreach($items as $i => $name) {
                if (empty(trim($name))) continue;
                $qty = (int)$qtys[$i];
                $price = (float)$prices[$i];
                $subtotal = $qty * $price;
                $total += $subtotal;
                
                $m_name = mysqli_real_escape_string($conn, $name);
                $conn->query("INSERT INTO po_items (po_id, medicine_name, quantity, unit_price) VALUES ($po_id, '$m_name', $qty, $price)");
            }
            
            $conn->query("UPDATE purchase_orders SET total_amount = $total WHERE po_id = $po_id");
            $conn->commit();
            $success_msg = "Purchase Order #$po_id created successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error creating PO: " . $e->getMessage();
        }
    } elseif ($action === 'approve_po') {
        $po_id = (int)$_POST['po_id'];
        if ($conn->query("UPDATE purchase_orders SET status = 'Approved' WHERE po_id = $po_id")) {
            $success_msg = "Purchase Order #$po_id approved.";
        }
    } elseif ($action === 'receive_po') {
        $po_id = (int)$_POST['po_id'];
        $conn->begin_transaction();
        try {
            // Update PO status
            $conn->query("UPDATE purchase_orders SET status = 'Received' WHERE po_id = $po_id");
            
            // Add items to pharmacy_stock
            $items_res = $conn->query("SELECT * FROM po_items WHERE po_id = $po_id");
            while($item = $items_res->fetch_assoc()) {
                $name = mysqli_real_escape_string($conn, $item['medicine_name']);
                $qty = $item['quantity'];
                $price = $item['unit_price'];
                $batch = 'PO-REF-' . $po_id;
                
                // For simplicity, we add/update stock. 
                // Real system would ask for expiry/batch details per item here.
                // We'll insert as new batch.
                $conn->query("INSERT INTO pharmacy_stock (medicine_name, quantity, unit_price, batch_number, expiry_date, status, last_restocked_date) 
                             VALUES ('$name', $qty, $price, '$batch', DATE_ADD(CURDATE(), INTERVAL 2 YEAR), 'Active', CURDATE())");
                
                // Log it
                $stock_id = $conn->insert_id;
                $conn->query("INSERT INTO pharmacy_logs (stock_id, medicine_name, action_type, quantity, performed_by, details) 
                             VALUES ($stock_id, '$name', 'Added', $qty, $current_user_id, 'Received from PO #$po_id')");
                
                // Notify Pharmacists
                $conn->query("INSERT INTO notifications (user_id, category, icon, color, title, message, url, priority, unread, created_at) 
                             SELECT user_id, 'Inventory', 'fa-plus-circle', 'success', 'Stock Restocked', 'Inventory Update: $name has been restocked ($qty units) from PO #$po_id.', 'staff_pharmacist_dashboard.php?section=inventory', 'Normal', 1, NOW() 
                             FROM users WHERE role = 'staff'");
            }
            $conn->commit();
            $success_msg = "PO Received and Inventory Updated.";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error receiving PO: " . $e->getMessage();
        }
    } elseif ($action === 'block_stock') {
        $s_id = (int)$_POST['stock_id'];
        $conn->query("UPDATE pharmacy_stock SET status = 'Blocked' WHERE stock_id = $s_id");
        $res = $conn->query("SELECT medicine_name FROM pharmacy_stock WHERE stock_id = $s_id");
        $m_name = $res->fetch_assoc()['medicine_name'];
        // Notify Pharmacists
        $conn->query("INSERT INTO notifications (user_id, message, unread, created_at) 
                     SELECT user_id, 'URGENT: Management has BLOCKED dispensing of $m_name.', 1, NOW() 
                     FROM users WHERE role = 'staff'");
        $success_msg = "Medicine batch has been BLOCKED. Pharmacists have been notified.";
    } elseif ($action === 'unblock_stock') {
        $s_id = (int)$_POST['stock_id'];
        $conn->query("UPDATE pharmacy_stock SET status = 'Active' WHERE stock_id = $s_id");
        $success_msg = "Medicine batch has been UNBLOCKED.";
    } elseif ($action === 'dispose_stock') {
        $s_id = (int)$_POST['stock_id'];
        $qty = (int)$_POST['quantity'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        
        $stock_res = $conn->query("SELECT medicine_name, quantity FROM pharmacy_stock WHERE stock_id = $s_id");
        $stock = $stock_res->fetch_assoc();
        
        if ($qty > $stock['quantity']) $qty = $stock['quantity'];

        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO medicine_disposals (stock_id, medicine_name, quantity, reason, disposal_date, disposed_by) 
                         VALUES ($s_id, '" . mysqli_real_escape_string($conn, $stock['medicine_name']) . "', $qty, '$reason', CURDATE(), $current_user_id)");
            
            $conn->query("UPDATE pharmacy_stock SET quantity = quantity - $qty WHERE stock_id = $s_id");
            
            // If quantity is now 0, mark as Inactive so it disappears from monitoring
            $check_qty = $conn->query("SELECT quantity FROM pharmacy_stock WHERE stock_id = $s_id")->fetch_assoc();
            if ($check_qty['quantity'] <= 0) {
                $conn->query("UPDATE pharmacy_stock SET status = 'Inactive' WHERE stock_id = $s_id");
            }
            
            // Notify Pharmacists
            $conn->query("INSERT INTO notifications (user_id, message, unread, created_at) 
                         SELECT user_id, 'Inventory Update: " . ($stock['medicine_name']) . " batch has been fully disposed/removed.', 1, NOW() 
                         FROM users WHERE role = 'staff'");
            
            $conn->commit();
            $success_msg = "Disposal recorded successfully and staff notified.";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error during disposal: " . $e->getMessage();
        }
    } elseif ($action === 'manual_restock') {
        $name = mysqli_real_escape_string($conn, $_POST['med_name']);
        $qty = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $batch = mysqli_real_escape_string($conn, $_POST['batch_no']);
        $exp = mysqli_real_escape_string($conn, $_POST['expiry']);
        
        $sql = "INSERT INTO pharmacy_stock (medicine_name, quantity, unit_price, batch_number, expiry_date, last_restocked_date, status) 
                VALUES ('$name', $qty, $price, '$batch', '$exp', CURDATE(), 'Active')";
        if ($conn->query($sql)) {
            $new_id = $conn->insert_id;
            $conn->query("INSERT INTO pharmacy_logs (stock_id, medicine_name, action_type, quantity, performed_by, details) 
                         VALUES ($new_id, '$name', 'Added', $qty, $current_user_id, 'Manual admin restock')");
            $success_msg = "Medicine restocked successfully!";

            // Mark any related notifications as read
            $conn->query("UPDATE notifications SET unread = 0 WHERE title LIKE '%Stock Shortage%' AND message LIKE '%$name%'");
            
            // Notify Pharmacists
            $conn->query("INSERT INTO notifications (user_id, category, icon, color, title, message, url, priority, unread, created_at) 
                         SELECT user_id, 'Inventory', 'fa-plus-circle', 'success', 'Stock Restocked', 'Inventory Update: $name has been restocked ($qty units).', 'staff_pharmacist_dashboard.php?section=inventory', 'Normal', 1, NOW() 
                         FROM users WHERE role = 'staff'");
        } else {
            $error_msg = "Error restocking: " . $conn->error;
        }
    }
}

// --- FETCH DATA ---
$section = $_GET['section'] ?? 'dashboard';

// Dashboard Metrics
$low_stock_count = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE quantity < 20 AND status = 'Active'")->fetch_assoc()['c'];
$expiring_count = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND expiry_date > CURDATE() AND status NOT IN ('Expired', 'Inactive')")->fetch_assoc()['c'];
$expired_count = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE expiry_date <= CURDATE() AND status NOT IN ('Expired', 'Inactive')")->fetch_assoc()['c'];
$pending_po_count = $conn->query("SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Pending'")->fetch_assoc()['c'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medicine Inventory Alerts - HealCare Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --bg: #f8fafc;
            --card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-main); margin: 0; display: flex; min-height: 100vh; }
        
        .sidebar { width: 280px; background: #0f172a; color: white; padding: 30px 0; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 0 30px 30px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link { padding: 15px 30px; color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 15px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.05); color: white; border-left: 4px solid var(--primary); }
        
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .top-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: var(--card); padding: 25px; border-radius: 12px; border: 1px solid var(--border); display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-val { font-size: 24px; font-weight: 700; display: block; }
        .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }

        .data-card { background: var(--card); border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card-header { padding: 20px 25px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .card-title { margin: 0; font-size: 16px; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 25px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); }
        td { padding: 15px 25px; font-size: 14px; border-bottom: 1px solid var(--border); }
        
        .btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-danger { background: #fee2e2; color: #ef4444; }
        .badge-warning { background: #ffedd5; color: #f59e0b; }
        .badge-success { background: #d1fae5; color: #10b981; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-body { background: white; width: 600px; border-radius: 12px; padding: 30px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; box-sizing: border-box; }
        
        #po-items-table input { width: 100%; padding: 5px; border: 1px solid var(--border); box-sizing: border-box; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <h3 style="margin:0; color:white;">HealCare Admin</h3>
            <span style="font-size:12px; color:#64748b;">Inventory Control</span>
        </div>
        <nav style="margin-top:20px;">
            <a href="?section=dashboard" class="nav-link <?php echo $section == 'dashboard'?'active':''; ?>"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="?section=low_stock" class="nav-link <?php echo $section == 'low_stock'?'active':''; ?>"><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</a>
            <a href="?section=expiry" class="nav-link <?php echo $section == 'expiry'?'active':''; ?>"><i class="fas fa-hourglass-end"></i> Expiry Alerts</a>
            <a href="?section=pos" class="nav-link <?php echo $section == 'pos'?'active':''; ?>"><i class="fas fa-shopping-cart"></i> Purchase Orders</a>
            <a href="?section=suppliers" class="nav-link <?php echo $section == 'suppliers'?'active':''; ?>"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="?section=disposals" class="nav-link <?php echo $section == 'disposals'?'active':''; ?>"><i class="fas fa-trash-alt"></i> Disposals</a>
            <div style="flex:1;"></div>
            <a href="admin_dashboard.php" class="nav-link" style="margin-top:auto;"><i class="fas fa-arrow-left"></i> Main Admin Panel</a>
        </nav>
    </aside>

    <main class="main-content">
        <?php if ($success_msg): ?>
            <div style="background:var(--success); color:white; padding:15px; border-radius:10px; margin-bottom:20px;"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div style="background:var(--danger); color:white; padding:15px; border-radius:10px; margin-bottom:20px;"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if ($section === 'dashboard'): ?>
            <h2 style="margin-bottom:30px;">Inventory Overview</h2>

            <?php
            $stock_out_res = $conn->query("SELECT medicine_name FROM pharmacy_stock WHERE quantity = 0 AND status = 'Active'");
            if($stock_out_res && $stock_out_res->num_rows > 0):
                $out_names = [];
                while($orn = $stock_out_res->fetch_assoc()) $out_names[] = $orn['medicine_name'];
            ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px;"></i>
                <div>
                    <strong>CRITICAL: MEDICINES STOCKING OUT!</strong><br>
                    <span style="font-size: 13px;">The following medicines are completely out of stock: <?php echo implode(', ', $out_names); ?>. Immediate restock required.</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="top-stats">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2; color:#ef4444;"><i class="fas fa-box-open"></i></div>
                    <div>
                        <span class="stat-val"><?php echo $low_stock_count; ?></span>
                        <span class="stat-label">Low Stock Items</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#ffedd5; color:#f59e0b;"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <span class="stat-val"><?php echo $expiring_count; ?></span>
                        <span class="stat-label">Expiring Soon</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(239, 68, 68, 0.1); color:#ef4444;"><i class="fas fa-times-circle"></i></div>
                    <div>
                        <span class="stat-val"><?php echo $expired_count; ?></span>
                        <span class="stat-label">Expired Batches</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#dbeafe; color:#2563eb;"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div>
                        <span class="stat-val"><?php echo $pending_po_count; ?></span>
                        <span class="stat-label">Pending POs</span>
                    </div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                <div class="data-card">
                    <div class="card-header"><h3 class="card-title">Recent Consumption Trends</h3></div>
                    <div style="padding:25px;">
                        <table style="width:100%;">
                            <thead><tr><th>Medicine</th><th>Quantity</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php
                                $trends = $conn->query("SELECT medicine_name, SUM(quantity) as q, DATE(log_date) as d FROM pharmacy_logs WHERE action_type='Dispensed' GROUP BY medicine_name, DATE(log_date) ORDER BY log_date DESC LIMIT 5");
                                while($t = $trends->fetch_assoc()):
                                ?>
                                <tr><td><?php echo $t['medicine_name']; ?></td><td><?php echo $t['q']; ?></td><td><?php echo $t['d']; ?></td></tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="data-card">
                    <div class="card-header"><h3 class="card-title">Critical Low Stock</h3><a href="?section=low_stock" class="btn btn-outline">View All</a></div>
                    <div style="padding:25px;">
                        <table style="width:100%;">
                            <thead><tr><th>Medicine</th><th>Left</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php
                                $crit = $conn->query("SELECT medicine_name, stock_id, quantity FROM pharmacy_stock WHERE quantity < 20 AND status NOT IN ('Inactive', 'Blocked') LIMIT 5");
                                while($c = $crit->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $c['medicine_name']; ?></td>
                                    <td>
                                        <?php if($c['quantity'] == 0): ?>
                                            <span class="badge badge-danger" style="background:#ef4444; color:white;">STOCK OUT</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><?php echo $c['quantity']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                     <td>
                                        <div style="display:flex; gap:5px;">
                                            <button onclick="openPOModal('<?php echo addslashes($c['medicine_name']); ?>')" class="btn btn-primary btn-sm" title="Create PO"><i class="fas fa-file-invoice"></i></button>
                                            <button onclick="openRestockModal('<?php echo addslashes($c['medicine_name']); ?>')" class="btn btn-success btn-sm" title="Quick Restock"><i class="fas fa-plus"></i> Restock</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($section === 'low_stock'): ?>
            <?php
            $stock_out_res = $conn->query("SELECT medicine_name FROM pharmacy_stock WHERE quantity = 0 AND status = 'Active'");
            if($stock_out_res && $stock_out_res->num_rows > 0):
                $out_names = [];
                while($orn = $stock_out_res->fetch_assoc()) $out_names[] = $orn['medicine_name'];
            ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px;"></i>
                <div>
                    <strong>CRITICAL: MEDICINES STOCKING OUT!</strong><br>
                    <span style="font-size: 13px;">The following medicines are completely out of stock: <?php echo implode(', ', $out_names); ?>. Immediate procurement required.</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="card-header" style="background:white; border-radius:12px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:center;">
                <h2 style="margin:0;">Low Stock Inventory Alerts</h2>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-success" onclick="openRestockModal()"><i class="fas fa-plus"></i> Quick Restock</button>
                    <button class="btn btn-primary" onclick="openPOModal()"><i class="fas fa-file-medical"></i> Bulk Procurement</button>
                </div>
            </div>
            <div class="data-card">
                <table>
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Batch #</th>
                            <th>Current Stock</th>
                            <th>Last Restocked</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $low = $conn->query("SELECT * FROM pharmacy_stock WHERE quantity < 20 AND status != 'Inactive' ORDER BY quantity ASC");
                        while($l = $low->fetch_assoc()):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($l['medicine_name']); ?></strong></td>
                            <td><?php echo $l['batch_number']; ?></td>
                            <td>
                                <?php if($l['quantity'] == 0): ?>
                                    <span class="badge badge-danger" style="background: #ef4444; color: white;"><i class="fas fa-exclamation-circle"></i> STOCK OUT</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?php echo $l['quantity']; ?> units</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $l['last_restocked_date']; ?></td>
                            <td><span class="badge badge-success"><?php echo $l['status']; ?></span></td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <button class="btn btn-success" style="padding:5px 10px; font-size:11px;" onclick="openRestockModal('<?php echo addslashes($l['medicine_name']); ?>')">Restock</button>
                                    <button class="btn btn-primary" style="padding:5px 10px; font-size:11px;" onclick="openPOModal('<?php echo addslashes($l['medicine_name']); ?>')">PO</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'expiry'): ?>
            <h2 style="margin-bottom:30px;">Medicine Expiry Monitoring</h2>
            <div class="data-card">
                <table>
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th>Stock</th>
                            <th>Days Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $exp = $conn->query("SELECT *, DATEDIFF(expiry_date, CURDATE()) as diff FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH) AND status != 'Inactive' ORDER BY expiry_date ASC");
                        while($e = $exp->fetch_assoc()):
                            $is_expired = $e['diff'] <= 0;
                            $cls = $is_expired ? 'badge-danger' : ($e['diff'] < 90 ? 'badge-warning' : 'badge-success');
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($e['medicine_name']); ?></strong></td>
                            <td><?php echo $e['batch_number']; ?></td>
                            <td><?php echo $e['expiry_date']; ?></td>
                            <td><?php echo $e['quantity']; ?></td>
                            <td><span class="badge <?php echo $cls; ?>"><?php echo $is_expired ? 'EXPIRED' : $e['diff'] . ' Days'; ?></span></td>
                            <td>
                                <?php
                                $s_cls = 'badge-success';
                                if($e['status'] === 'Blocked' || $e['status'] === 'Expired') $s_cls = 'badge-danger';
                                elseif($e['status'] === 'Near Expiry') $s_cls = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $s_cls; ?>"><?php echo $e['status']; ?></span>
                            </td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <?php if($e['status'] !== 'Blocked'): ?>
                                    <form method="POST"><input type="hidden" name="action" value="block_stock"><input type="hidden" name="stock_id" value="<?php echo $e['stock_id']; ?>"><button type="submit" class="btn btn-warning" title="Block Issue"><i class="fas fa-ban"></i> Block</button></form>
                                    <?php else: ?>
                                    <form method="POST"><input type="hidden" name="action" value="unblock_stock"><input type="hidden" name="stock_id" value="<?php echo $e['stock_id']; ?>"><button type="submit" class="btn btn-outline" title="Unblock"><i class="fas fa-check"></i> Unblock</button></form>
                                    <?php endif; ?>
                                    <button class="btn btn-danger" onclick="openDisposeModal(<?php echo $e['stock_id']; ?>, '<?php echo addslashes($e['medicine_name']); ?>', <?php echo $e['quantity']; ?>, '<?php echo $is_expired?'Expired':'Damaged'; ?>')"><i class="fas fa-trash"></i> Dispose</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'disposals'): ?>
             <h2 style="margin-bottom:30px;">Medicine Disposal Logs</h2>
            <div class="data-card">
                <table>
                    <thead><tr><th>Date</th><th>Medicine</th><th>Qty</th><th>Reason</th><th>Performed By</th></tr></thead>
                    <tbody>
                        <?php
                        $dis = $conn->query("SELECT d.*, u.username FROM medicine_disposals d LEFT JOIN users u ON d.disposed_by = u.user_id ORDER BY d.disposal_date DESC");
                        while($d = $dis->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $d['disposal_date']; ?></td>
                            <td><strong><?php echo $d['medicine_name']; ?></strong></td>
                            <td><?php echo $d['quantity']; ?></td>
                            <td><span class="badge badge-warning"><?php echo $d['reason']; ?></span></td>
                            <td><?php echo $d['username']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'suppliers'): ?>
            <h2 style="margin-bottom:30px;">Preferred Medicine Suppliers</h2>
            <div class="data-card">
                <table>
                    <thead><tr><th>Supplier Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Address</th></tr></thead>
                    <tbody>
                        <?php
                        $sup = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name ASC");
                        while($s = $sup->fetch_assoc()):
                        ?>
                        <tr>
                            <td><strong><?php echo $s['supplier_name']; ?></strong></td>
                            <td><?php echo $s['contact_person']; ?></td>
                            <td><?php echo $s['phone']; ?></td>
                            <td><?php echo $s['email']; ?></td>
                            <td><?php echo $s['address']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($section === 'pos'): ?>
            <div class="card-header" style="background:white; border-radius:12px; margin-bottom:30px;">
                <h2 style="margin:0;">Manage Purchase Orders</h2>
                <button class="btn btn-primary" onclick="openPOModal()"><i class="fas fa-plus"></i> New PO</button>
            </div>
            <div class="data-card">
                <table>
                    <thead>
                        <tr>
                            <th>PO #</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pos = $conn->query("SELECT po.*, s.supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id ORDER BY po.po_id DESC");
                        while($p = $pos->fetch_assoc()):
                        ?>
                        <tr>
                            <td>#PO-<?php echo $p['po_id']; ?></td>
                            <td><?php echo $p['supplier_name']; ?></td>
                            <td><?php echo $p['order_date']; ?></td>
                            <td>₹<?php echo number_format($p['total_amount'] ?? 0); ?></td>
                            <td>
                                <span class="badge <?php 
                                    echo $p['status'] == 'Received' ? 'badge-success' : ($p['status'] == 'Approved' ? 'badge-warning' : 'badge-danger'); 
                                ?>"><?php echo $p['status']; ?></span>
                            </td>
                            <td>
                                <?php if($p['status'] === 'Pending'): ?>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="approve_po"><input type="hidden" name="po_id" value="<?php echo $p['po_id']; ?>"><button type="submit" class="btn btn-warning">Approve</button></form>
                                <?php elseif($p['status'] === 'Approved'): ?>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="receive_po"><input type="hidden" name="po_id" value="<?php echo $p['po_id']; ?>"><button type="submit" class="btn btn-success">Receive Stock</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <!-- PO MODAL -->
    <div id="poModal" class="modal">
        <div class="modal-body" style="width:700px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3>Create Purchase Order</h3>
                <button onclick="document.getElementById('poModal').style.display='none'" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_po">
                <div class="form-group">
                    <label class="form-label">Select Supplier</label>
                    <select name="supplier_id" class="form-control" required>
                        <?php
                        $suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers");
                        while($s = $suppliers->fetch_assoc()) echo "<option value='".$s['supplier_id']."'>".$s['supplier_name']."</option>";
                        ?>
                    </select>
                </div>
                
                <table id="po-items-table" style="margin-bottom:20px;">
                    <thead><tr><th>Medicine Name</th><th width="100">Qty</th><th width="100">Price</th><th></th></tr></thead>
                    <tbody id="po-items-body">
                        <tr>
                            <td><input type="text" name="items[]" id="initial_po_item" class="form-control" placeholder="Medicine name"></td>
                            <td><input type="number" name="quantities[]" class="form-control" value="100"></td>
                            <td><input type="number" step="0.01" name="prices[]" class="form-control" value="0.00"></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-outline" onclick="addPORow()" style="margin-bottom:20px;"><i class="fas fa-plus"></i> Add Item</button>

                <div class="form-group">
                    <label class="form-label">Internal Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%; padding:15px;">Submit Purchase Order for Approval</button>
            </form>
        </div>
    </div>

    <!-- DISPOSE MODAL -->
    <div id="disposeModal" class="modal">
        <div class="modal-body">
            <h3>Record Medicine Disposal</h3>
            <form method="POST">
                <input type="hidden" name="action" value="dispose_stock">
                <input type="hidden" name="stock_id" id="dispose_stock_id">
                
                <div class="form-group">
                    <label class="form-label">Medicine</label>
                    <input type="text" id="dispose_med_name" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity to Dispose</label>
                    <input type="number" name="quantity" id="dispose_qty" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <select name="reason" id="dispose_reason" class="form-control">
                        <option value="Expired">Expired</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Returned to Supplier">Returned to Supplier</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('disposeModal').style.display='none'" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Disposal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MANUAL RESTOCK MODAL -->
    <div id="restockModal" class="modal">
        <div class="modal-body">
            <h3>Manual Medicine Restock</h3>
            <form method="POST">
                <input type="hidden" name="action" value="manual_restock">
                
                <div class="form-group">
                    <label class="form-label">Medicine Name</label>
                    <input type="text" name="med_name" id="restock_med_name" class="form-control" required placeholder="Enter medicine name">
                </div>
                <div class="form-row" style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" required placeholder="eg. 100">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Unit Price (₹)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00">
                    </div>
                </div>
                <div class="form-row" style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Batch Number</label>
                        <input type="text" name="batch_no" class="form-control" required placeholder="BT-2024-X">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                    <button type="button" onclick="document.getElementById('restockModal').style.display='none'" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-success">Add to Stock</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPOModal(name = '') {
            document.getElementById('poModal').style.display = 'flex';
            if (name) document.getElementById('initial_po_item').value = name;
        }
        function openRestockModal(name = '') {
            document.getElementById('restockModal').style.display = 'flex';
            if (name) document.getElementById('restock_med_name').value = name;
        }
        function addPORow() {
            const row = `<tr>
                <td><input type="text" name="items[]" class="form-control"></td>
                <td><input type="number" name="quantities[]" class="form-control" value="100"></td>
                <td><input type="number" step="0.01" name="prices[]" class="form-control" value="0.00"></td>
                <td><button type="button" onclick="this.parentElement.parentElement.remove()" style="background:none; border:none; color:red; cursor:pointer;"><i class="fas fa-minus-circle"></i></button></td>
            </tr>`;
            document.getElementById('po-items-body').insertAdjacentHTML('beforeend', row);
        }
        function openDisposeModal(id, name, qty, reason) {
            document.getElementById('disposeModal').style.display = 'flex';
            document.getElementById('dispose_stock_id').value = id;
            document.getElementById('dispose_med_name').value = name;
            document.getElementById('dispose_qty').value = qty;
            document.getElementById('dispose_reason').value = reason;
        }
        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) e.target.style.display = 'none';
        }
    </script>
</body>
</html>
