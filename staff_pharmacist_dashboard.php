<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_stock') {
        $name = mysqli_real_escape_string($conn, $_POST['med_name']);
        $type = mysqli_real_escape_string($conn, $_POST['med_type']);
        $mf = mysqli_real_escape_string($conn, $_POST['manufacturer']);
        $batch = mysqli_real_escape_string($conn, $_POST['batch_no']);
        $exp = mysqli_real_escape_string($conn, $_POST['expiry']);
        $qty = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $loc = mysqli_real_escape_string($conn, $_POST['location']);

        $sql = "INSERT INTO pharmacy_stock (medicine_name, medicine_type, manufacturer, batch_number, expiry_date, quantity, unit_price, location, last_restocked_date) 
                VALUES ('$name', '$type', '$mf', '$batch', '$exp', $qty, $price, '$loc', CURDATE())";
        
        if ($conn->query($sql)) {
            $msg = "New stock added successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error adding stock: " . $conn->error;
            $msg_type = "error";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_stock') {
        $id = (int)$_POST['stock_id'];
        $name = mysqli_real_escape_string($conn, $_POST['med_name']);
        $type = mysqli_real_escape_string($conn, $_POST['med_type']);
        $mf = mysqli_real_escape_string($conn, $_POST['manufacturer']);
        $batch = mysqli_real_escape_string($conn, $_POST['batch_no']);
        $exp = mysqli_real_escape_string($conn, $_POST['expiry']);
        $qty = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $loc = mysqli_real_escape_string($conn, $_POST['location']);

        $sql = "UPDATE pharmacy_stock SET 
                medicine_name='$name', 
                medicine_type='$type', 
                manufacturer='$mf', 
                batch_number='$batch', 
                expiry_date='$exp', 
                quantity=$qty, 
                unit_price=$price, 
                location='$loc' 
                WHERE stock_id=$id";
        
        if ($conn->query($sql)) {
            $msg = "Stock updated successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error updating stock: " . $conn->error;
            $msg_type = "error";
        }
    }
}
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
            <a href="?section=dashboard" class="nav-item <?php echo (!isset($_GET['section']) || $_GET['section'] == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Prescriptions</a>
            <a href="?section=inventory" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'inventory') ? 'active' : ''; ?>"><i class="fas fa-pills"></i> Inventory / Stock</a>
            <a href="?section=history" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'history') ? 'active' : ''; ?>"><i class="fas fa-history"></i> Dispensed History</a>
            <a href="?section=reports" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'reports') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Pharmacy Reports</a>
            <a href="?section=alerts" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'alerts') ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Expiry Alerts</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <?php if ($msg): ?>
                <div style="background: <?php echo $msg_type == 'success' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; border: 1px solid <?php echo $msg_type == 'success' ? '#10b981' : '#ef4444'; ?>; color: #fff; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_GET['section']) || $_GET['section'] == 'dashboard'): ?>
                <!-- Quick Archive -->
                <div style="background: linear-gradient(135deg, #0f172a, #1e293b); padding: 25px; border-radius: 12px; border: 1px solid var(--border-soft); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="color: #fff; margin-bottom: 5px; font-size: 16px;"><i class="fas fa-file-upload" style="color: #4fc3f7;"></i> Pharmacy Sales Data</h3>
                        <p style="color: #64748b; font-size: 12px;">Upload monthly inventory financial reports or narcotics logs.</p>
                    </div>
                    <button onclick="openReportModal()" style="background: #4fc3f7; color: #020617; text-decoration: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 12px; border: none; cursor: pointer;">
                        <i class="fas fa-upload"></i> Upload Report
                    </button>
                </div>
                <?php
                // Fetch critical stock for the alert banner
                $crit_sql = "SELECT * FROM pharmacy_stock WHERE quantity < 20 ORDER BY quantity ASC LIMIT 1";
                $crit_res = $conn->query($crit_sql);
                $critical_item = ($crit_res && $crit_res->num_rows > 0) ? $crit_res->fetch_assoc() : null;
                
                if ($critical_item): 
                ?>
                <div class="stock-alert" id="activeAlert">
                    <span><i class="fas fa-exclamation-circle"></i> <strong>Critical Stock Alert:</strong> <?php echo htmlspecialchars($critical_item['medicine_name']); ?> (Batch #<?php echo htmlspecialchars($critical_item['batch_number']); ?>) is below threshold.</span>
                    <button id="notifyBtn" style="background: #ef4444; color: white; border: none; padding: 8px 15px; border-radius: 8px; font-size: 11px; font-weight: bold; cursor: pointer; transition: 0.3s;" onclick="notifyAdmin()">Notify Admin</button>
                </div>
                <?php endif; ?>

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

            <?php elseif ($_GET['section'] == 'reports'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Pharmacy Reports</h1>
                    <p style="color:#64748b; font-size:14px;">Access medicine sales, stock usage, and expiry analytics.</p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                    <!-- Medicine Sales Report -->
                    <div class="medicine-card" style="cursor: pointer; transition: 0.3s; border-left-color: #4fc3f7;" onclick="location.href='reports_manager.php?view=reports&type=pharmacist_sales'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: #fff;">Medicine Sales</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Daily & monthly sales revenue</p>
                            </div>
                            <i class="fas fa-receipt" style="font-size:24px; color: #4fc3f7;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Total Daily Revenue</li>
                            <li>Prescription Sales Count</li>
                        </ul>
                        <button style="width:100%; padding: 10px; background: transparent; border: 1px solid #4fc3f7; color: #4fc3f7; border-radius: 8px;">View Report</button>
                    </div>

                    <!-- Stock Usage Report -->
                    <div class="medicine-card" style="cursor: pointer; transition: 0.3s; border-left-color: #f59e0b;" onclick="location.href='reports_manager.php?view=reports&type=pharmacist_stock'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: #fff;">Stock Usage</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Inventory movement & remaining</p>
                            </div>
                            <i class="fas fa-cubes" style="font-size:24px; color: #f59e0b;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Daily Usage Stats</li>
                            <li>Current Stock Levels</li>
                        </ul>
                        <button style="width:100%; padding: 10px; background: transparent; border: 1px solid #f59e0b; color: #f59e0b; border-radius: 8px;">View Report</button>
                    </div>

                    <!-- Expiry Alert Report -->
                    <div class="medicine-card" style="cursor: pointer; transition: 0.3s; border-left-color: #ef4444;" onclick="location.href='reports_manager.php?view=reports&type=pharmacist_expiry'">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:18px; color: #fff;">Expiry Alerts</h3>
                                <p style="color:#64748b; font-size:13px; margin-top:5px;">Medicines nearing expiration</p>
                            </div>
                            <i class="fas fa-hourglass-end" style="font-size:24px; color: #ef4444;"></i>
                        </div>
                        <ul style="color:#cbd5e1; font-size:13px; margin-bottom:20px; padding-left:20px;">
                            <li>Expired Batches</li>
                            <li>Near-Expiry Warning List</li>
                        </ul>
                        <button style="width:100%; padding: 10px; background: transparent; border: 1px solid #ef4444; color: #ef4444; border-radius: 8px;">View Report</button>
                    </div>
                </div>
                </div>
            
            <?php elseif ($_GET['section'] == 'inventory'): ?>
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="color:#fff; font-size: 28px;">Inventory Management</h1>
                        <p style="color:#64748b; font-size:14px;">Track medicine stock, batches, and expiry dates.</p>
                    </div>
                    <button class="btn-action-main" onclick="openModal('addStockModal')" style="background: #4fc3f7; color: #020617; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;"><i class="fas fa-plus"></i> Add New Stock</button>
                </div>

                <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 25px;">
                    <!-- Search Form -->
                    <form method="GET" style="margin-bottom: 20px; display: flex; gap: 15px;">
                        <input type="hidden" name="section" value="inventory">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search medicine..." style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-soft); padding: 10px 15px; border-radius: 8px; color: white; width: 300px;">
                        <select name="category" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-soft); padding: 10px 15px; border-radius: 8px; color: white;">
                             <option value="">All Categories</option>
                             <?php 
                                $cats = ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream', 'Drops', 'Inhaler'];
                                foreach($cats as $c) {
                                    $sel = (isset($_GET['category']) && $_GET['category'] == $c) ? 'selected' : '';
                                    echo "<option value='$c' $sel>$c</option>";
                                }
                             ?>
                        </select>
                        <button type="submit" style="background: #3b82f6; border: none; color: white; padding: 0 20px; border-radius: 8px; cursor: pointer;">Search</button>
                        <?php if(isset($_GET['search'])): ?>
                            <a href="?section=inventory" style="display:flex; align-items:center; color: #ef4444; text-decoration:none; font-size: 13px;">Clear</a>
                        <?php endif; ?>
                    </form>

                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Category</th>
                                <th>Batch No</th>
                                <th>Expiry Date</th>
                                <th>Unit Price</th>
                                <th>Stock Left</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $search_term = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
                            $cat_term = mysqli_real_escape_string($conn, $_GET['category'] ?? '');
                            
                            $where_clauses = [];
                            if($search_term) {
                                $where_clauses[] = "(medicine_name LIKE '%$search_term%' OR manufacturer LIKE '%$search_term%')";
                            }
                            if($cat_term) {
                                $where_clauses[] = "medicine_type = '$cat_term'";
                            }
                            
                            $where_sql = "";
                            if(count($where_clauses) > 0) {
                                $where_sql = "WHERE " . implode(' AND ', $where_clauses);
                            }

                            $stock_sql = "SELECT * FROM pharmacy_stock $where_sql ORDER BY medicine_name ASC";
                            $stock_res = $conn->query($stock_sql);
                            
                            if(!$stock_res){
                                echo "<tr><td colspan='8'>Error loading stock.</td></tr>";
                            } else {
                                if ($stock_res->num_rows > 0) {
                                    while($item = $stock_res->fetch_assoc()) {
                                        $status_color = ($item['quantity'] < 20) ? '#ef4444' : '#10b981';
                                        $status_text = ($item['quantity'] < 20) ? 'Low Stock' : 'In Stock';
                                        $item_json = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                                        echo "<tr>
                                            <td><strong>".htmlspecialchars($item['medicine_name'])."</strong><br><span style='font-size:11px; color:#64748b;'>".htmlspecialchars($item['manufacturer'] ?? '')."</span></td>
                                            <td>".htmlspecialchars($item['medicine_type'])."</td>
                                            <td>".htmlspecialchars($item['batch_number'])."</td>
                                            <td>".htmlspecialchars($item['expiry_date'])."</td>
                                            <td>$".htmlspecialchars($item['unit_price'])."</td>
                                            <td style='font-weight:bold;'>".htmlspecialchars($item['quantity'])."</td>
                                            <td><span style='color: $status_color; font-size: 11px; border: 1px solid $status_color; padding: 2px 8px; border-radius: 10px;'>$status_text</span></td>
                                            <td><button type='button' data-medicine='$item_json' onclick='openEditModal(this)' style='background:none; border:none; color:#4fc3f7; cursor:pointer;'><i class='fas fa-edit'></i></button></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' style='text-align:center; padding: 20px;'>No stock items found matching your criteria.</td></tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($_GET['section'] == 'history'): ?>
                 <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Dispensed History</h1>
                    <p style="color:#64748b; font-size:14px;">Log of all medicines dispensed and billed.</p>
                </div>
                <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 25px;">
                     <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Patient Name</th>
                                <th>Date</th>
                                <th>Items / Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch Billing records where type is Pharmacy
                            $hist_sql = "
                                SELECT b.*, r.name as patient_name 
                                FROM billing b
                                JOIN users u ON b.patient_id = u.user_id
                                JOIN registrations r ON u.registration_id = r.registration_id
                                WHERE b.bill_type LIKE '%Pharmacy%' OR b.bill_type LIKE '%Medicine%'
                                ORDER BY b.bill_date DESC LIMIT 50
                            ";
                            $hist_res = $conn->query($hist_sql);
                            if(!$hist_res) {
                                // Fallback
                            } else {
                                if($hist_res->num_rows > 0) {
                                    while($row = $hist_res->fetch_assoc()) {
                                        echo "<tr>
                                            <td>#INV-".str_pad($row['bill_id'], 4, '0', STR_PAD_LEFT)."</td>
                                            <td><strong style='color:white;'>".htmlspecialchars($row['patient_name'])."</strong></td>
                                            <td>".date('M d, Y', strtotime($row['bill_date']))."</td>
                                            <td>Pharmacy / Medicines</td>
                                            <td>$".number_format($row['total_amount'], 2)."</td>
                                            <td><span class='status-pill' style='color:#10b981; border:1px solid #10b981; padding:2px 8px; border-radius:10px;'>Dispensed</span></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align:center; padding: 30px;'>No history records found.</td></tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($_GET['section'] == 'alerts'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#ef4444; font-size: 28px;">Expiry Alerts</h1>
                    <p style="color:#64748b; font-size:14px;">Medicines expiring within the next 3 months.</p>
                </div>
                <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid #ef4444; border-radius: 12px; padding: 25px;">
                     <table class="inventory-table">
                        <thead>
                            <tr>
                                <th style="color: #ef4444;">Medicine Name</th>
                                <th>Batch No</th>
                                <th>Expiry Date</th>
                                <th>Stock Remaining</th>
                                <th>Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch stock expiring in next 90 days
                            $alert_sql = "SELECT *, DATEDIFF(expiry_date, CURDATE()) as days_left FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) ORDER BY expiry_date ASC";
                            $alert_res = $conn->query($alert_sql);
                            
                            if($alert_res && $alert_res->num_rows > 0) {
                                while($row = $alert_res->fetch_assoc()) {
                                    $days_color = ($row['days_left'] < 30) ? '#ef4444' : '#f59e0b';
                                    echo "<tr>
                                        <td><strong>".htmlspecialchars($row['medicine_name'])."</strong></td>
                                        <td>".htmlspecialchars($row['batch_number'])."</td>
                                        <td style='color: $days_color; font-weight:bold;'>".htmlspecialchars($row['expiry_date'])."</td>
                                        <td>".htmlspecialchars($row['quantity'])."</td>
                                        <td style='color: $days_color;'>".($row['days_left'] > 0 ? $row['days_left'].' Days' : 'EXPIRED')."</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center; padding: 30px;'>No expiry alerts at this time.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </main>
    </div>

    <?php 
    // Set staff_type for the modal
    $staff_type = 'pharmacist';
    include 'includes/report_upload_modal.php'; 
    ?>

    <!-- Add Stock Modal -->
    <div id="addStockModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#0f172a; padding:30px; border-radius:12px; width:500px; max-width:90%; border:1px solid rgba(255,255,255,0.1);">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="color:white;">Add New Medicine Stock</h3>
                <i class="fas fa-times" style="color:#64748b; cursor:pointer;" onclick="document.getElementById('addStockModal').style.display='none'"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_stock">
                <div style="margin-bottom:15px;">
                    <label style="color:#94a3b8; font-size:12px; display:block; mb-1;">Medicine Name</label>
                    <input type="text" name="med_name" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Type</label>
                        <select name="med_type" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                            <option>Tablet</option>
                            <option>Capsule</option>
                            <option>Syrup</option>
                            <option>Injection</option>
                            <option>Cream</option>
                            <option>Inhaler</option>
                        </select>
                    </div>
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Manufacturer</label>
                         <input type="text" name="manufacturer" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Batch No</label>
                        <input type="text" name="batch_no" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Expiry Date</label>
                        <input type="date" name="expiry" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                </div>
                 <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Quantity</label>
                        <input type="number" name="quantity" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Unit Price ($)</label>
                        <input type="number" step="0.01" name="price" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="color:#94a3b8; font-size:12px; display:block;">Shelf Location</label>
                    <input type="text" name="location" placeholder="e.g. Shelf A1" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                </div>
                <button type="submit" style="width:100%; padding:12px; background:#4fc3f7; color:#020617; font-weight:bold; border:none; border-radius:8px; cursor:pointer;">Add Stock</button>
            </form>
        </div>
    <!-- Edit Stock Modal -->
    <div id="editStockModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#0f172a; padding:30px; border-radius:12px; width:500px; max-width:90%; border:1px solid rgba(255,255,255,0.1);">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="color:white;">Edit Medicine Stock</h3>
                <i class="fas fa-times" style="color:#64748b; cursor:pointer;" onclick="document.getElementById('editStockModal').style.display='none'"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_stock">
                <input type="hidden" name="stock_id" id="edit_id">
                <div style="margin-bottom:15px;">
                    <label style="color:#94a3b8; font-size:12px; display:block; mb-1;">Medicine Name</label>
                    <input type="text" name="med_name" id="edit_name" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Type</label>
                        <select name="med_type" id="edit_type" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                            <option>Tablet</option>
                            <option>Capsule</option>
                            <option>Syrup</option>
                            <option>Injection</option>
                            <option>Cream</option>
                            <option>Inhaler</option>
                        </select>
                    </div>
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Manufacturer</label>
                         <input type="text" name="manufacturer" id="edit_mf" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Batch No</label>
                        <input type="text" name="batch_no" id="edit_batch" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Expiry Date</label>
                        <input type="date" name="expiry" id="edit_exp" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                </div>
                 <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Quantity</label>
                        <input type="number" name="quantity" id="edit_qty" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Unit Price ($)</label>
                        <input type="number" step="0.01" name="price" id="edit_price" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="color:#94a3b8; font-size:12px; display:block;">Shelf Location</label>
                    <input type="text" name="location" id="edit_loc" placeholder="e.g. Shelf A1" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                </div>
                <button type="submit" style="width:100%; padding:12px; background:#4fc3f7; color:#020617; font-weight:bold; border:none; border-radius:8px; cursor:pointer;">Update Stock</button>
            </form>
        </div>
    </div>

    <script>
        function notifyAdmin() {
            const btn = document.getElementById('notifyBtn');
            const alertBox = document.getElementById('activeAlert');
            
            // UI Feedback
            btn.innerHTML = '<i class="fas fa-check"></i> Notified';
            btn.style.background = '#10b981';
            btn.disabled = true;
            
            // Visual confirmation
            setTimeout(() => {
                alertBox.style.borderColor = '#10b981';
                alertBox.style.background = 'rgba(16, 185, 129, 0.1)';
            }, 500);
        }

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }


        function openEditModal(btn) {
            try {
                const jsonStr = btn.getAttribute('data-medicine');
                console.log("Raw JSON:", jsonStr);
                
                if (!jsonStr) {
                    console.error("No data-medicine attribute found.");
                    alert("Error: No data found for this item.");
                    return;
                }

                const data = JSON.parse(jsonStr);
                console.log("Parsed Data:", data);
                
                const modal = document.getElementById('editStockModal');
                if(!modal) {
                    console.error("Modal #editStockModal not found!");
                    return;
                }

                modal.style.display = 'flex';
                
                // Helper to safely set value
                const setVal = (id, val) => {
                    const el = document.getElementById(id);
                    if(el) el.value = val !== null ? val : '';
                    else console.warn(`Element #${id} not found`);
                };

                setVal('edit_id', data.stock_id);
                setVal('edit_name', data.medicine_name);
                setVal('edit_type', data.medicine_type);
                setVal('edit_mf', data.manufacturer);
                setVal('edit_batch', data.batch_number);
                setVal('edit_exp', data.expiry_date);
                setVal('edit_qty', data.quantity);
                setVal('edit_price', data.unit_price);
                setVal('edit_loc', data.location);

            } catch (e) {
                console.error("Error opening edit modal:", e);
                alert("An error occurred while opening the edit form. Check console for details.");
            }
        }
        // General close modal if clicking outside
         window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
