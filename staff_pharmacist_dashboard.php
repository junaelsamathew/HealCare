<?php
session_start();
include 'includes/db_connect.php';
include 'includes/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// Authorization Check for Stock Management
$is_authorized_pharmacist = false;
if ($_SESSION['user_role'] == 'admin') {
    $is_authorized_pharmacist = true;
} elseif ($_SESSION['user_role'] == 'staff') {
    // Check staff_type from registrations table
    $auth_q = $conn->query("SELECT staff_type FROM registrations WHERE registration_id = (SELECT registration_id FROM users WHERE user_id = $user_id)");
    if ($auth_q && $auth_q->num_rows > 0) {
        $st = $auth_q->fetch_assoc()['staff_type'];
        // Check for Pharmacist (or similar roles if any)
        if (stripos($st, 'Pharmacist') !== false || stripos($st, 'Admin') !== false) {
             $is_authorized_pharmacist = true;
        }
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_stock') {
        if (!$is_authorized_pharmacist) {
             $msg = "Unauthorized Access: Only Authorized Pharmacists can add stock.";
             $msg_type = "error";
        } elseif (!isset($_POST['verified_check'])) {
             $msg = "Error: You must verify the stock details.";
             $msg_type = "error";
        } else {
            $name = mysqli_real_escape_string($conn, $_POST['med_name']);
            $invoice_ref = mysqli_real_escape_string($conn, $_POST['supplier_invoice']);
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
                $new_stock_id = $conn->insert_id;
                // Log Addition
                $log_details = "Inv: $invoice_ref, Batch: $batch, Expiry: $exp";
                $conn->query("INSERT INTO pharmacy_logs (stock_id, medicine_name, action_type, quantity, performed_by, details) VALUES ($new_stock_id, '$name', 'Added', $qty, $user_id, '$log_details')");

                if (strtotime($exp) < time()) {
                    $msg = "Warning: Medicine added, but it is already EXPIRED based on the date provided ($exp). It will not be available for dispensing.";
                    $msg_type = "warning";
                } else {
                    $msg = "New stock added successfully!";
                    $msg_type = "success";
                }
            } else {
            $msg = "Error adding stock: " . $conn->error;
            $msg_type = "error";
        }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'notify_admin') {
        $alert_type = $_POST['alert_type'] ?? 'shortage';
        
        if ($alert_type === 'shortage') {
            $items_sql = "SELECT medicine_name, manufacturer, quantity FROM pharmacy_stock WHERE quantity < 20";
            $subject = 'URGENT: Medicine Stock Shortage & Low Stock Alert';
            $header_text = 'Stock Shortage / Low Stock Alert';
            $intro_text = 'The following medicines are <strong>OUT OF STOCK</strong> or <strong>LOW ON STOCK</strong> (< 20 units) in the pharmacy:';
            $success_msg = "Admin notified of stock shortages/low stock successfully via Email!";
            $n_msg_prefix = "Inventory Alert: ";
        } else {
            $items_sql = "SELECT medicine_name, batch_number, expiry_date FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) ORDER BY expiry_date ASC";
            $subject = 'ATTENTION: Medicine Expiry Alert';
            $header_text = 'Medicine Expiry Warning';
            $intro_text = 'The following medicines are <strong>EXPIRING SOON</strong> (within 3 months):';
            $success_msg = "Admin notified of expiring medicines successfully via Email!";
            $n_msg_prefix = "Expiry Alert: ";
        }

        $items_res = $conn->query($items_sql);
        
        if ($items_res && $items_res->num_rows > 0) {
            $items_list = "";
            $msg_names = [];
            while($row = $items_res->fetch_assoc()) {
                $msg_names[] = $row['medicine_name'];
                if ($alert_type === 'shortage') {
                    $status_text = ($row['quantity'] == 0) ? "<span style='color:#ef4444;'>OUT OF STOCK</span>" : "<span style='color:#f59e0b;'>LOW STOCK (" . $row['quantity'] . " left)</span>";
                    $items_list .= "<li><strong>" . htmlspecialchars($row['medicine_name']) . "</strong> (" . htmlspecialchars($row['manufacturer']) . ") - Status: $status_text</li>";
                } else {
                    $items_list .= "<li><strong>" . htmlspecialchars($row['medicine_name']) . "</strong> (Batch: " . htmlspecialchars($row['batch_number']) . ") - Expires on: " . htmlspecialchars($row['expiry_date']) . "</li>";
                }
            }
            $med_names_str = implode(', ', array_slice($msg_names, 0, 3));
            if(count($msg_names) > 3) $med_names_str .= " and " . (count($msg_names) - 3) . " more";
            
            // Get Admin Emails
            $admin_emails = [];
            $adm_res = $conn->query("SELECT email FROM users WHERE role = 'admin'");
            while($row = $adm_res->fetch_assoc()) {
                $admin_emails[] = $row['email'];
            }
            if(!in_array('admin@gmail.com', $admin_emails)) $admin_emails[] = 'admin@gmail.com';
            
            $mail = new PHPMailer(true);
            try {
                configureDefaultMail($mail);
                $mail->setFrom('system@healcare.com', 'HealCare Pharmacy');
                foreach($admin_emails as $email) {
                    $mail->addAddress($email);
                }
                
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <h2 style='color: #ef4444;'>$header_text</h2>
                    <p>$intro_text</p>
                    <ul>$items_list</ul>
                    <p>Please review and take necessary action.</p>
                    <p style='color: #64748b; font-size: 12px;'>Sent by Pharmacist Dashboard</p>
                </div>";
                
                $mail->send();
                
                $chk_n = $conn->query("SHOW TABLES LIKE 'notifications'");
                if($chk_n && $chk_n->num_rows > 0) {
                     $n_msg = $n_msg_prefix . "$med_names_str (" . $items_res->num_rows . " items total) require urgent attention.";
                     $n_title = ($alert_type === 'shortage') ? "Critical Stock Shortage" : "Medicine Expiry Warning";
                     $n_cat = "Inventory";
                     $n_icon = ($alert_type === 'shortage') ? "fa-box-open" : "fa-hourglass-end";
                     $n_color = ($alert_type === 'shortage') ? "danger" : "warning";
                     $n_url = "admin_pharmacy_inventory.php?section=" . (($alert_type === 'shortage') ? "low_stock" : "expiry");
                     
                     $conn->query("INSERT INTO notifications (user_id, category, icon, color, title, message, url, priority, unread, created_at) 
                                  VALUES (0, '$n_cat', '$n_icon', '$n_color', '$n_title', '" . mysqli_real_escape_string($conn, $n_msg) . "', '$n_url', 'High', 1, NOW())");
                }

                $_SESSION['hide_stock_alert'] = true; // Dismiss until restock or session end
                $msg = "notification sent to admin";
                $msg_type = "success";
            
            } catch (Exception $e) {
                $msg = "Error sending notification: " . $mail->ErrorInfo;
                $msg_type = "error";
            }
        } else {
            $msg = "No relevant items found to report.";
            $msg_type = "info";
        }

    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_stock') {
        $id = (int)$_POST['stock_id'];
        $name = mysqli_real_escape_string($conn, $_POST['med_name'] ?? '');
        $type = mysqli_real_escape_string($conn, $_POST['med_type'] ?? '');
        $mf = mysqli_real_escape_string($conn, $_POST['manufacturer'] ?? '');
        $batch = mysqli_real_escape_string($conn, $_POST['batch_no'] ?? '');
        $exp = mysqli_real_escape_string($conn, $_POST['expiry'] ?? '');
        $qty = (int)($_POST['quantity'] ?? 0);
        $price = (float)($_POST['price'] ?? 0.00);
        $loc = mysqli_real_escape_string($conn, $_POST['location'] ?? '');

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
    } elseif (isset($_POST['action']) && $_POST['action'] === 'dispense') {
        $id = (int)$_POST['prescription_id'];
        
        // Final verification: Ensure bill is PAID before dispensing
        $check_q = $conn->query("SELECT bill_id, payment_status FROM billing WHERE reference_id = $id AND (bill_type LIKE 'Pharmacy%')");
        $can_dispense = false;
        if($check_q && $check_q->num_rows > 0) {
            $b = $check_q->fetch_assoc();
            if($b['payment_status'] === 'Paid') $can_dispense = true;
        }

        if ($can_dispense) {
            $conn->begin_transaction();
            try {
                // 1. Smart Stock Deduction Logic
                // 1. Smart Stock Deduction Logic
                $presc_res = $conn->query("SELECT medicine_details, duration FROM prescriptions WHERE prescription_id = $id");
                if ($presc_res && $presc_res->num_rows > 0) {
                    $p_data = $presc_res->fetch_assoc();
                    
                    // Split multiple medicines (comma separated)
                    // Note: This simple split assumes medicine names don't contain commas. 
                    // If they might, we'd need a more robust delimiter or regex.
                    $med_strs = explode(', ', $p_data['medicine_details']);
                    $dur_strs = explode(', ', $p_data['duration']); 

                    foreach($med_strs as $idx => $med_str) {
                         if(empty(trim($med_str))) continue;

                         // Parse Name: "Paracetamol (500mg) - 1-0-1" -> "Paracetamol"
                         $med_name = $med_str;
                         // Extract name before '('
                         if(strpos($med_str, '(') !== false) {
                             $med_name = trim(substr($med_str, 0, strpos($med_str, '(')));
                         } elseif(strpos($med_str, '-') !== false) {
                             // Fallback if no brackets but has hyphen: "Paracetamol - 1-0-1"
                             $med_name = trim(substr($med_str, 0, strpos($med_str, '-')));
                         }
                         $med_name_safe = mysqli_real_escape_string($conn, $med_name);

                         // Parse Duration (Default 5 days if missing)
                         $dur_str = $dur_strs[$idx] ?? '5 days';
                         $days = intval($dur_str);
                         if($days <= 0) $days = 5; 

                         // Parse Frequency for daily count
                         $freq_part = '';
                         if(strrpos($med_str, '-') !== false) {
                             $freq_part = trim(substr($med_str, strrpos($med_str, '-') + 1));
                         }
                         
                         $per_day = 0;
                         // Regex for "1-0-1", "1-1-1", etc
                         if (preg_match('/(\d+)-(\d+)-(\d+)/', $freq_part, $f_matches)) {
                             $per_day = intval($f_matches[1]) + intval($f_matches[2]) + intval($f_matches[3]);
                         } elseif (preg_match('/(\d+)-(\d+)/', $freq_part, $f_matches)) { 
                             $per_day = intval($f_matches[1]) + intval($f_matches[2]);
                         }
                         
                         // Text based
                         if ($per_day == 0) {
                             if (stripos($med_str, 'qid') !== false) { $per_day = 4; }
                             elseif (stripos($med_str, 'tds') !== false || stripos($med_str, 'tid') !== false) { $per_day = 3; }
                             elseif (stripos($med_str, 'bd') !== false || stripos($med_str, 'bid') !== false) { $per_day = 2; }
                             elseif (stripos($med_str, 'od') !== false) { $per_day = 1; }
                             else { $per_day = 2; } // Default assumption
                         }

                         $qty_needed = $days * $per_day;

                         // Find Stock - FIFO (First Expiring First Out)
                         // Matches medicine name at start of stock name - ONLY Active and Non-Expired
                         $stock_q = $conn->query("SELECT stock_id, quantity, medicine_name FROM pharmacy_stock WHERE medicine_name LIKE '$med_name_safe%' AND quantity > 0 AND status = 'Active' AND expiry_date > CURDATE() ORDER BY expiry_date ASC LIMIT 1");
                         
                         $s_id = 0; // Default if not found
                         $log_name = $med_name_safe;
                         $is_unlisted = true;

                         if($stock_q && $stock_q->num_rows > 0) {
                             $stock = $stock_q->fetch_assoc();
                             $s_id = $stock['stock_id'];
                             $log_name = mysqli_real_escape_string($conn, $stock['medicine_name']);
                             $is_unlisted = false;
                             
                             // Deduct from batch
                             $conn->query("UPDATE pharmacy_stock SET quantity = GREATEST(0, quantity - $qty_needed) WHERE stock_id = $s_id");

                             // AUTO NOTIFY ADMIN IF HITS ZERO
                             $check_zero = $conn->query("SELECT quantity, medicine_name FROM pharmacy_stock WHERE stock_id = $s_id");
                             if($check_zero) {
                                 $curr = $check_zero->fetch_assoc();
                                 if($curr['quantity'] < 20) {
                                      $m_name = mysqli_real_escape_string($conn, $curr['medicine_name']);
                                      $q_left = $curr['quantity'];
                                      $n_title = ($q_left == 0) ? "Critical Out of Stock Alert" : "Low Stock Alert";
                                      $n_color = ($q_left == 0) ? "danger" : "warning";
                                      $n_msg = ($q_left == 0) ? "URGENT: $m_name is now OUT OF STOCK." : "Attention: $m_name is RUNNING LOW ($q_left units left).";
                                      $n_icon = ($q_left == 0) ? "fa-box-open" : "fa-exclamation-triangle";
                                      
                                      $check_duplicate = $conn->query("SELECT id FROM notifications WHERE title = '$n_title' AND message LIKE '%$m_name%' AND unread = 1 AND DATE(created_at) = CURDATE()");
                                      if($check_duplicate->num_rows == 0) {
                                          $conn->query("INSERT INTO notifications (user_id, category, icon, color, title, message, url, priority, unread, created_at) 
                                                       VALUES (0, 'Inventory', '$n_icon', '$n_color', '$n_title', '$n_msg', 'admin_pharmacy_inventory.php?section=low_stock', 'High', 1, NOW())");
                                      }
                                  }
                             }
                         }
                         
                         // Always Log Dispense (Even if not in inventory, so pharmacist sees it in logs)
                         $log_details = ($is_unlisted ? "Unlisted Medicine - " : "") . "RX #$id";
                         $conn->query("INSERT INTO pharmacy_logs (stock_id, medicine_name, action_type, quantity, performed_by, details) VALUES ($s_id, '$log_name', 'Dispensed', $qty_needed, $user_id, '$log_details')");
                    }
                }

                // 2. Mark Prescription as Dispensed
                $conn->query("UPDATE prescriptions SET status = 'Dispensed' WHERE prescription_id = $id");
                
                // 3. Mark Bill as Dispensed (Update clinical lifecycle of the bill)
                $conn->query("UPDATE billing SET payment_status = 'Dispensed' WHERE reference_id = $id AND (bill_type LIKE 'Pharmacy%')");
                
                $conn->commit();
                $msg = "Medicines dispensed and transaction completed successfully!";
                $msg_type = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "Error finalizing dispense: " . $e->getMessage();
                $msg_type = "error";
            }
        } else {
            $msg = "Error: Medicine cannot be dispensed until payment is verified as PAID.";
            $msg_type = "error";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_medicine_status') {
        $sid = (int)$_POST['stock_id'];
        $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
        $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'Manual status update');

        $res = $conn->query("SELECT medicine_name, batch_number FROM pharmacy_stock WHERE stock_id = $sid");
        if ($res && $row = $res->fetch_assoc()) {
            $m_name = $row['medicine_name'];
            $batch = $row['batch_number'];

            if ($conn->query("UPDATE pharmacy_stock SET status = '$new_status' WHERE stock_id = $sid")) {
                // Log Action
                $conn->query("INSERT INTO pharmacy_logs (stock_id, medicine_name, action_type, quantity, performed_by, details) 
                             VALUES ($sid, '$m_name', 'Status Update', 0, $user_id, 'Status changed to $new_status. Reason: $reason')");

                // Notify Admin
                $n_title = "Medicine Status Flagged: $new_status";
                $n_color = ($new_status === 'Expired') ? 'danger' : 'warning';
                $n_icon = ($new_status === 'Expired') ? 'fa-calendar-times' : 'fa-hourglass-half';
                $n_msg = "Pharmacist flagged $m_name (Batch: $batch) as $new_status. Reason: $reason";
                
                $conn->query("INSERT INTO notifications (user_id, category, icon, color, title, message, url, priority, unread, created_at) 
                             VALUES (0, 'Inventory', '$n_icon', '$n_color', '$n_title', '" . mysqli_real_escape_string($conn, $n_msg) . "', 'admin_pharmacy_inventory.php?section=expiry', 'High', 1, NOW())");

                $msg = "Medicine status updated to $new_status and Admin has been notified.";
                $msg_type = "success";
            } else {
                $msg = "Error updating status: " . $conn->error;
                $msg_type = "error";
            }
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
            margin-bottom: 20px;
        }
        .stock-alert { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-size: 13px; display: flex; justify-content: space-between; align-items: center; }
        
        .inventory-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        .inventory-table th { text-align: left; padding: 12px; color: #64748b; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--border-soft); }
        .inventory-table td { padding: 15px 12px; color: #cbd5e1; font-size: 13px; border-bottom: 1px solid var(--border-soft); }
        .stock-low { color: #ef4444; font-weight: bold; }
        .stock-low { color: #ef4444; font-weight: bold; }

        /* Brand Animation */
        .brand-letter {
            display: inline-block;
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .brand-letter.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
        <a href="index.php" class="logo-main" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img src="images/healcare_logo.jpg" alt="HealCare" style="height: 50px;">
            <span class="animated-brand" style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">HEALCARE HOSPITAL</span>
        </a>
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WHATSAPP</span>
                    <a href="https://wa.me/919539045609" target="_blank" style="font-size: 13px; color: #25d366; font-weight: 600; text-decoration: none;"><i class="fab fa-whatsapp"></i> +91 953 904 5609</a>
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

    <div class="secondary-nav" style="position: relative;">
        <div style="display: flex; align-items: center; gap: 15px;"><div style="background: #4fc3f7; color:#fff; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold;">P</div><h2 style="color:#fff; font-size:20px;">Pharmacy Panel</h2></div>
        <div style="display: flex; align-items: center; gap: 25px;">
            <div style="position: relative; cursor: pointer; display: flex; align-items: center;" onclick="document.getElementById('staffNotifDropdown').style.display = document.getElementById('staffNotifDropdown').style.display === 'none' ? 'block' : 'none'">
                <i class="fas fa-bell" style="color: #94a3b8; font-size: 18px;"></i>
                <?php 
                $n_q = "SELECT COUNT(*) as c FROM notifications WHERE (user_id = $user_id OR user_id = 0) AND unread = 1";
                $n_count = $conn->query($n_q)->fetch_assoc()['c'];
                if($n_count > 0): 
                ?>
                <span style="position: absolute; top: -10px; right: -10px; background: #ef4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 50%; font-weight: 800;"><?php echo $n_count; ?></span>
                <?php endif; ?>
            </div>
            <div id="staffNotifDropdown" style="display:none; position: absolute; top: 100%; right: 180px; width: 320px; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                <div style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-weight: 700; color: #fff; font-size: 13px;">Management Alerts</div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php
                    $notifs = $conn->query("SELECT * FROM notifications WHERE (user_id = $user_id OR user_id = 0) ORDER BY created_at DESC LIMIT 10");
                    if($notifs && $notifs->num_rows > 0) {
                        while($n = $notifs->fetch_assoc()) {
                            $bg = ($n['unread'] == 0) ? 'transparent' : 'rgba(79, 195, 247, 0.05)';
                            echo "<div style='padding:12px 15px; background:$bg; border-bottom:1px solid rgba(255,255,255,0.02);'>";
                            echo "<p style='margin:0; font-size:12px; color:#cbd5e1; line-height:1.4;'>".htmlspecialchars($n['message'])."</p>";
                            echo "<span style='font-size:10px; color:#64748b;'>".date('M d, H:i', strtotime($n['created_at']))."</span>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div style='padding:20px; color:#64748b; font-size:12px; text-align:center;'>No alerts at this time.</div>";
                    }
                    ?>
                </div>
            </div>
            <div style="display: flex; align-items: center;"><span class="staff-label" style="color: #94a3b8; font-size: 14px; margin-right: 15px;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span><a href="logout.php" style="color: #94a3b8; text-decoration: none; border: 1px solid #4fc3f7; padding: 5px 20px; border-radius: 20px;">Log Out</a></div>
        </div>
    </div>

    <div class="dashboard-body">
        <aside class="side-nav">
            <a href="?section=dashboard" class="nav-item <?php echo (!isset($_GET['section']) || $_GET['section'] == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Prescriptions</a>
            <a href="?section=inventory" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'inventory') ? 'active' : ''; ?>"><i class="fas fa-pills"></i> Inventory / Stock</a>
            <a href="?section=history" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'history') ? 'active' : ''; ?>"><i class="fas fa-history"></i> Dispensed History</a>
            <a href="?section=reports" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'reports') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Pharmacy Reports</a>
            <a href="?section=alerts" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'alerts') ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Expiry Alerts</a>
            <a href="?section=logs" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] == 'logs') ? 'active' : ''; ?>"><i class="fas fa-list-alt"></i> Stock Logs</a>
            <a href="staff_settings.php" class="nav-item"><i class="fas fa-cog"></i> Profile Settings</a>
        </aside>

        <main class="main-ops">
            <?php if ($msg): ?>
                <div style="background: <?php echo $msg_type == 'success' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; border: 1px solid <?php echo $msg_type == 'success' ? '#10b981' : '#ef4444'; ?>; color: #fff; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_GET['section']) || $_GET['section'] == 'dashboard'): ?>
                <?php
                // Fetch ALL stock-out items first
                $stock_out_list = $conn->query("SELECT medicine_name, batch_number FROM pharmacy_stock WHERE quantity = 0 AND status = 'Active'");
                $out_count = ($stock_out_list) ? $stock_out_list->num_rows : 0;
                
                // If none out, check for low stock
                $critical_item = null;
                if ($out_count == 0) {
                    $crit_sql = "SELECT * FROM pharmacy_stock WHERE quantity < 20 AND quantity > 0 AND status = 'Active' ORDER BY quantity ASC LIMIT 1";
                    $crit_res = $conn->query($crit_sql);
                    $critical_item = ($crit_res && $crit_res->num_rows > 0) ? $crit_res->fetch_assoc() : null;
                }

                if (!isset($_SESSION['hide_stock_alert']) || $_SESSION['hide_stock_alert'] === false):
                    if ($out_count > 0): 
                    ?>
                    <div class="stock-alert" id="activeAlert" style="background: rgba(239, 68, 68, 0.1); border-color: #ef4444; color: #ef4444; margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i> 
                            <div>
                                <strong style="display: block; font-size: 15px; margin-bottom: 2px;">CRITICAL OUT-OF-STOCK ALERT</strong>
                                <span style="font-size: 13px;">The following medicines are completely empty: 
                                    <?php 
                                    $out_names = [];
                                    while($o = $stock_out_list->fetch_assoc()) $out_names[] = $o['medicine_name'] . " (#" . $o['batch_number'] . ")";
                                    echo implode(', ', $out_names);
                                    ?>
                                </span>
                            </div>
                        </div>
                        <button id="notifyBtn" style="background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;" onclick="notifyAdmin('shortage')">Notify Admin</button>
                    </div>
                    <?php elseif ($critical_item): ?>
                    <div class="stock-alert" id="activeAlert" style="background: rgba(245, 158, 11, 0.1); border-color: #f59e0b; color: #f59e0b; margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px;"></i>
                            <div>
                                <strong style="display: block; font-size: 15px; margin-bottom: 2px;">LOW STOCK WARNING</strong>
                                <span style="font-size: 13px;"><?php echo htmlspecialchars($critical_item['medicine_name']); ?> is below 20 units (<?php echo $critical_item['quantity']; ?> left).</span>
                            </div>
                        </div>
                        <button id="notifyBtn" style="background: #f59e0b; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;" onclick="notifyAdmin('shortage')">Notify Admin</button>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
                // --- Analytics Data Fetching for Dashboard ---
                // 1. Prescription Trends (Last 7 Days)
                $days_labels = [];
                $presc_counts = [];
                for ($i = 6; $i >= 0; $i--) {
                    $d = date('Y-m-d', strtotime("-$i days"));
                    $days_labels[] = date('D', strtotime($d));
                    $pq = $conn->query("SELECT COUNT(*) as c FROM prescriptions WHERE DATE(prescription_date) = '$d'");
                    $presc_counts[] = ($pq) ? $pq->fetch_assoc()['c'] : 0;
                }
                
                // 2. Pharmacy Health Score Calculation
                $stock_issues = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE quantity < 20")->fetch_assoc()['c'];
                $expiry_issues = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)")->fetch_assoc()['c'];
                $pending_presc = $conn->query("SELECT COUNT(*) as c FROM prescriptions WHERE status = 'Requested'")->fetch_assoc()['c'];
                
                // Simple scoring logic: Start at 100, deduct points for issues
                $pharmacy_score = max(0, 100 - ($stock_issues * 2) - ($expiry_issues * 3) - ($pending_presc * 5));
                $score_color = ($pharmacy_score > 80) ? '#10b981' : (($pharmacy_score > 50) ? '#f59e0b' : '#ef4444');
                $score_text = ($pharmacy_score > 80) ? 'Excellent Condition' : (($pharmacy_score > 50) ? 'Needs Attention' : 'Critical Condition');
                
                $js_days_labels = json_encode($days_labels);
                $js_presc_counts = json_encode($presc_counts);
                ?>

                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
                    <!-- Prescription Queue -->
                    <div>
                        <h3 style="color:#fff; margin-bottom: 20px;">Prescription Processing Queue</h3>
                        
                        <?php
                        // Fetch Pending and Awaiting Payment Prescriptions
                        $presc_sql = "
                            SELECT p.*, 
                                   rp.name as patient_name, 
                                   rd.name as doctor_name, 
                                   d.specialization,
                                   b.bill_id,
                                   b.payment_status as medicine_payment_status,
                                   b.total_amount as medicine_bill_amount
                            FROM prescriptions p
                            JOIN users up ON p.patient_id = up.user_id
                            JOIN registrations rp ON up.registration_id = rp.registration_id
                            JOIN users ud ON p.doctor_id = ud.user_id
                            JOIN registrations rd ON ud.registration_id = rd.registration_id
                            LEFT JOIN doctors d ON ud.user_id = d.user_id
                            LEFT JOIN billing b ON p.prescription_id = b.reference_id AND (b.bill_type LIKE 'Pharmacy%')
                            WHERE p.status IN ('Requested', 'Awaiting Payment', 'Paid')
                            ORDER BY 
                                CASE 
                                    WHEN p.status = 'Requested' THEN 1
                                    WHEN p.status = 'Awaiting Payment' THEN 2
                                    ELSE 3 
                                END ASC, p.prescription_date DESC
                        ";
                        $presc_res = $conn->query($presc_sql);
                        
                        if ($presc_res && $presc_res->num_rows > 0):
                            while ($presc = $presc_res->fetch_assoc()):
                                $has_bill = !empty($presc['bill_id']);
                                $is_paid = ($presc['medicine_payment_status'] === 'Paid');
                                
                                // Find appointment_id for context
                                $appt_q = $conn->query("SELECT appointment_id FROM medical_records WHERE prescription_id = " . $presc['prescription_id'] . " LIMIT 1");
                                $appt_id = ($appt_q && $appt_q->num_rows > 0) ? $appt_q->fetch_assoc()['appointment_id'] : 0;
                        ?>
                        <div class="medicine-card" style="border-left-color: <?php echo $is_paid ? '#10b981' : ($has_bill ? '#f59e0b' : '#4fc3f7'); ?>">
                            <div style="display: flex; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 11px; color: #4fc3f7; font-weight: 800; text-transform: uppercase;">ID: #RX-<?php echo $presc['prescription_id']; ?></span>
                                    <h4 style="color:#fff; margin: 5px 0; font-size: 18px;"><?php echo htmlspecialchars($presc['patient_name']); ?></h4>
                                    <p style="font-size: 13px; color: #94a3b8;">Requested by: Dr. <?php echo htmlspecialchars($presc['doctor_name']); ?> (<?php echo htmlspecialchars($presc['specialization'] ?? 'General'); ?>)</p>
                                </div>
                                <div style="text-align: right;">
                                    <span style="color: #4fc3f7; font-size: 11px; font-weight: bold; display: block;"><?php echo date('M d, Y', strtotime($presc['prescription_date'])); ?></span>
                                    <?php if($has_bill): ?>
                                        <span class="badge" style="background: <?php echo $is_paid ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)'; ?>; color: <?php echo $is_paid ? '#10b981' : '#f59e0b'; ?>; font-size: 10px; margin-top: 5px;">
                                            Medicine Bill: <?php echo $presc['medicine_payment_status']; ?> (₹<?php echo number_format($presc['medicine_bill_amount']); ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; margin-top: 10px;">
                                <p style="font-size: 13px; color: #cbd5e1; white-space: pre-wrap; margin: 0;"><?php echo htmlspecialchars($presc['medicine_details']); ?></p>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 10px;">
                                <div>
                                    <?php if(!$has_bill): ?>
                                        <span style="color: #4fc3f7; font-size: 12px; font-weight: 600;"><i class="fas fa-file-invoice"></i> Step 1: Generate Bill</span>
                                    <?php elseif(!$is_paid): ?>
                                        <span style="color: #f59e0b; font-size: 12px; font-weight: 600;"><i class="fas fa-clock"></i> Step 2: Waiting for Online Payment...</span>
                                    <?php else: ?>
                                        <span style="color: #10b981; font-size: 12px; font-weight: 600;"><i class="fas fa-check-circle"></i> Paid! Ready to Dispense</span>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; gap:10px;">
                                    <?php if(!$has_bill): ?>
                                        <button type="button" onclick="openBillModalCombined('<?php echo $presc['prescription_id']; ?>', '<?php echo $presc['patient_id']; ?>', '<?php echo $presc['doctor_id']; ?>', '<?php echo addslashes($presc['patient_name']); ?>', '<?php echo $appt_id; ?>')" style="background: #4fc3f7; color: #020617; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                            <i class="fas fa-receipt"></i> Generate Medicine Bill
                                        </button>
                                    <?php elseif($is_paid): ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="dispense">
                                            <input type="hidden" name="prescription_id" value="<?php echo $presc['prescription_id']; ?>">
                                            <button type="submit" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                                <i class="fas fa-pills"></i> Dispense & Complete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button disabled style="background: #334155; color: #94a3b8; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: not-allowed;">
                                            <i class="fas fa-lock"></i> Payment Pending
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn-print" onclick="window.open('print_prescription.php?id=<?php echo $presc['prescription_id']; ?>', '_blank', 'width=900,height=800')" style="background: transparent; border: 1px solid var(--border-soft); color: #fff; padding: 10px 15px; border-radius: 8px; cursor: pointer;"><i class="fas fa-print"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #64748b; background: #0f172a; border-radius: 12px; border: 1px dashed var(--border-soft);">
                                <i class="fas fa-check-circle" style="font-size: 30px; margin-bottom: 10px;"></i>
                                <p>No pending prescriptions.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Stock Alerts & Expiry -->
                    <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 16px; padding: 25px;">
                        <h4 style="color: #fff; margin-bottom: 20px; font-size: 15px;"><i class="fas fa-warehouse"></i> Inventory Snapshot</h4>
                        <table class="inventory-table">
                            <thead>
                                <tr><th>Item</th><th>Stock</th><th>Expiry</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $snap_sql = "SELECT medicine_name, quantity, expiry_date FROM pharmacy_stock ORDER BY quantity ASC LIMIT 3";
                                $snap_res = $conn->query($snap_sql);
                                if($snap_res && $snap_res->num_rows > 0) {
                                    while($s = $snap_res->fetch_assoc()) {
                                        $low_cls = ($s['quantity'] < 20) ? 'stock-low' : '';
                                        $stock_display = ($s['quantity'] == 0) ? "<span style='color:#ef4444; font-weight:800;'>OUT</span>" : str_pad($s['quantity'], 2, '0', STR_PAD_LEFT);
                                        $exp_format = date('M Y', strtotime($s['expiry_date']));
                                        echo "<tr>
                                            <td>".htmlspecialchars($s['medicine_name'])."</td>
                                            <td class='$low_cls'>$stock_display</td>
                                            <td>$exp_format</td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'>No items in stock.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <h4 style="color: #fff; margin-top: 30px; margin-bottom: 20px; font-size: 15px;"><i class="fas fa-hourglass-half"></i> Near Expiry Medicines</h4>
                        <table class="inventory-table">
                            <thead>
                                <tr><th>Medicine</th><th>Days Left</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $near_exp_sql = "SELECT medicine_name, DATEDIFF(expiry_date, CURDATE()) as days_left, status FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 2 MONTH) AND status != 'Expired' ORDER BY expiry_date ASC LIMIT 5";
                                $near_exp_res = $conn->query($near_exp_sql);
                                if($near_exp_res && $near_exp_res->num_rows > 0) {
                                    while($ne = $near_exp_res->fetch_assoc()) {
                                        $exp_clr = ($ne['days_left'] < 30) ? '#ef4444' : '#f59e0b';
                                        echo "<tr>
                                            <td style='font-size:12px;'>".htmlspecialchars($ne['medicine_name'])."</td>
                                            <td style='color: $exp_clr; font-weight:bold; font-size:12px;'>".($ne['days_left'] > 0 ? $ne['days_left'].'d' : 'EXPIRED')."</td>
                                            <td><span style='font-size:10px; border:1px solid #94a3b8; padding:1px 5px; border-radius:5px;'>".$ne['status']."</span></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' style='text-align:center; font-size:12px; color:#64748b;'>No items expiring soon.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <a href="?section=alerts" style="display:block; text-align:center; margin-top:15px; font-size:12px; color:#4fc3f7; text-decoration:none;">View All Expiry Alerts <i class="fas fa-arrow-right"></i></a>
                        <button style="width: 100%; margin-top: 25px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-soft); color: #fff; padding: 12px; border-radius: 10px; cursor: pointer; font-size: 12px;">Full Inventory Report</button>
                    </div>
                </div>

            <?php elseif ($_GET['section'] == 'reports'): ?>
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="color:#fff; font-size: 28px;">Pharmacy Reports</h1>
                        <p style="color:#64748b; font-size:14px;">Access medicine sales, stock usage, and expiry analytics.</p>
                    </div>
                     <button onclick="openReportModal()" style="background: #4fc3f7; color: #020617; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-upload"></i> Upload Report
                    </button>
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
                    <?php if($is_authorized_pharmacist): ?>
                        <button class="btn-action-main" onclick="openModal('addStockModal')" style="background: #4fc3f7; color: #020617; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;"><i class="fas fa-plus"></i> Add New Stock</button>
                    <?php endif; ?>
                </div>

                <?php
                // Fetch high-level counts
                $count_total = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock")->fetch_assoc()['c'];
                $count_low = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE quantity > 0 AND quantity < 20")->fetch_assoc()['c'];
                $count_out = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE quantity = 0")->fetch_assoc()['c'];
                $count_expiring = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND expiry_date > CURDATE() AND status != 'Inactive'")->fetch_assoc()['c'];
                $count_avail = $conn->query("SELECT COUNT(*) as c FROM pharmacy_stock WHERE quantity >= 20")->fetch_assoc()['c'];
                
                // Fetch Category Data for Charts
                $cat_labels = [];
                $cat_data = [];
                $cat_res = $conn->query("SELECT medicine_type, COUNT(*) as count FROM pharmacy_stock GROUP BY medicine_type ORDER BY count DESC LIMIT 8");
                if ($cat_res) {
                    while($row = $cat_res->fetch_assoc()) {
                        $cat_labels[] = $row['medicine_type'] ?: 'Uncategorized';
                        $cat_data[] = $row['count'];
                    }
                }
                
                // Prepare Chart Data
                $js_cat_labels = json_encode($cat_labels);
                $js_cat_data = json_encode($cat_data);
                $js_stock_status_data = json_encode([$count_avail, $count_low, $count_out, $count_expiring]);
                ?>

                <!-- Global Out of Stock Alert -->
                 <?php if ($count_out > 0 && (!isset($_SESSION['hide_stock_alert']) || $_SESSION['hide_stock_alert'] === false)): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px dashed #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <div style="background:#ef4444; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <strong style="color: #ef4444; display: block; font-size:16px;">Critical Stock Alert</strong>
                                <span style="color: #cbd5e1; font-size: 14px;">Warning: <strong><?php echo $count_out; ?></strong> medicines are completely out of stock.</span>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="notify_admin">
                            <input type="hidden" name="alert_type" value="shortage">
                            <button type="submit" style="background: #ef4444; color: white; border: none; padding: 10px 25px; border-radius: 6px; font-weight: bold; cursor: pointer; display:flex; align-items:center; gap:8px; transition: 0.3s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                <i class="fas fa-paper-plane"></i> Notify Admin Now
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- Global Expiry Alert -->
                <?php if ($count_expiring > 0 && (!isset($_SESSION['hide_stock_alert']) || $_SESSION['hide_stock_alert'] === false)): ?>
                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px dashed #f59e0b; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <div style="background:#f59e0b; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white;">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div>
                                <strong style="color: #f59e0b; display: block; font-size:16px;">Medicine Expiry Warning</strong>
                                <span style="color: #cbd5e1; font-size: 14px;">Attention: <strong><?php echo $count_expiring; ?></strong> medicines are expiring within 3 months.</span>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="notify_admin">
                            <input type="hidden" name="alert_type" value="expiry">
                            <button type="submit" style="background: #f59e0b; color: white; border: none; padding: 10px 25px; border-radius: 6px; font-weight: bold; cursor: pointer; display:flex; align-items:center; gap:8px; transition: 0.3s;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                                <i class="fas fa-paper-plane"></i> Notify Admin Now
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Inventory Summary Cards -->
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #0f172a; border: 1px solid var(--border-soft); padding: 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="color: #94a3b8; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">TOTAL MEDICINES</span>
                            <h3 style="color: #fff; font-size: 24px; margin: 5px 0 0 0;"><?php echo $count_total; ?></h3>
                        </div>
                        <div style="background: rgba(79, 195, 247, 0.1); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #4fc3f7;">
                            <i class="fas fa-pills" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div style="background: #0f172a; border: 1px solid var(--border-soft); padding: 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="color: #94a3b8; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">AVAILABLE (IN STOCK)</span>
                            <h3 style="color: #10b981; font-size: 24px; margin: 5px 0 0 0;"><?php echo $count_avail; ?></h3>
                        </div>
                        <div style="background: rgba(16, 185, 129, 0.1); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #10b981;">
                            <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.3); padding: 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="color: #f59e0b; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">LOW STOCK (REORDER)</span>
                            <h3 style="color: #f59e0b; font-size: 24px; margin: 5px 0 0 0;"><?php echo $count_low; ?></h3>
                        </div>
                        <div style="background: rgba(245, 158, 11, 0.1); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 18px;"></i>
                        </div>
                    </div>
                     <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.3); padding: 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="color: #ef4444; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">OUT OF STOCK</span>
                            <h3 style="color: #ef4444; font-size: 24px; margin: 5px 0 0 0;"><?php echo $count_out; ?></h3>
                        </div>
                        <div style="background: rgba(239, 68, 68, 0.1); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ef4444;">
                            <i class="fas fa-times-circle" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.3); padding: 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="color: #f59e0b; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">EXPIRING SOON</span>
                            <h3 style="color: #f59e0b; font-size: 24px; margin: 5px 0 0 0;"><?php echo $count_expiring; ?></h3>
                        </div>
                        <div style="background: rgba(245, 158, 11, 0.1); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                            <i class="fas fa-hourglass-half" style="font-size: 18px;"></i>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 20px;">
                        <h4 style="color: #fff; margin-bottom: 20px; font-size: 16px; border-bottom: 1px solid var(--border-soft); padding-bottom: 10px;">Medicine Categories Distribution</h4>
                        <div style="height: 250px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                    <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 20px;">
                        <h4 style="color: #fff; margin-bottom: 20px; font-size: 16px; border-bottom: 1px solid var(--border-soft); padding-bottom: 10px;">Stock Status Overview</h4>
                        <div style="height: 250px; position: relative;">
                            <canvas id="stockStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Category Chart
                    const ctxCat = document.getElementById('categoryChart').getContext('2d');
                    new Chart(ctxCat, {
                        type: 'bar',
                        data: {
                            labels: <?php echo $js_cat_labels; ?>,
                            datasets: [{
                                label: 'Items Count',
                                data: <?php echo $js_cat_data; ?>,
                                backgroundColor: 'rgba(79, 195, 247, 0.2)',
                                borderColor: '#4fc3f7',
                                borderWidth: 1,
                                borderRadius: 4,
                                barThickness: 40
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                    ticks: { color: '#94a3b8' }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: '#94a3b8' }
                                }
                            }
                        }
                    });

                    // Stock Status Chart
                    const ctxStatus = document.getElementById('stockStatusChart').getContext('2d');
                    new Chart(ctxStatus, {
                        type: 'doughnut',
                        data: {
                            labels: ['Available', 'Low Stock', 'Out of Stock', 'Expiring'],
                            datasets: [{
                                data: <?php echo $js_stock_status_data; ?>,
                                backgroundColor: [
                                    '#10b981', // Green
                                    '#f59e0b', // Amber
                                    '#ef4444', // Red
                                    '#fbbf24'  // Yellow
                                ],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: { color: '#cbd5e1', font: { size: 12 }, boxWidth: 15, padding: 15 }
                                }
                            },
                            cutout: '65%',
                            layout: {
                                padding: 10
                            }
                        }
                    });
                });
                </script>


                <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 25px;">
                    <!-- Filter Tabs -->
                    <div style="margin-bottom: 20px; display: flex; gap: 10px; border-bottom: 1px solid var(--border-soft); padding-bottom: 15px;">
                        <a href="?section=inventory&status=all" style="color: <?php echo (!isset($_GET['status']) || $_GET['status'] == 'all') ? '#4fc3f7' : '#94a3b8'; ?>; text-decoration: none; padding: 5px 15px; font-size: 14px; font-weight: 600; border-bottom: 2px solid <?php echo (!isset($_GET['status']) || $_GET['status'] == 'all') ? '#4fc3f7' : 'transparent'; ?>;">All Medicines</a>
                        <a href="?section=inventory&status=instock" style="color: <?php echo (isset($_GET['status']) && $_GET['status'] == 'instock') ? '#10b981' : '#94a3b8'; ?>; text-decoration: none; padding: 5px 15px; font-size: 14px; font-weight: 600; border-bottom: 2px solid <?php echo (isset($_GET['status']) && $_GET['status'] == 'instock') ? '#10b981' : 'transparent'; ?>;">In Stock</a>
                        <a href="?section=inventory&status=low" style="color: <?php echo (isset($_GET['status']) && $_GET['status'] == 'low') ? '#f59e0b' : '#94a3b8'; ?>; text-decoration: none; padding: 5px 15px; font-size: 14px; font-weight: 600; border-bottom: 2px solid <?php echo (isset($_GET['status']) && $_GET['status'] == 'low') ? '#f59e0b' : 'transparent'; ?>;">Low Stock</a>
                        <a href="?section=inventory&status=out" style="color: <?php echo (isset($_GET['status']) && $_GET['status'] == 'out') ? '#ef4444' : '#94a3b8'; ?>; text-decoration: none; padding: 5px 15px; font-size: 14px; font-weight: 600; border-bottom: 2px solid <?php echo (isset($_GET['status']) && $_GET['status'] == 'out') ? '#ef4444' : 'transparent'; ?>;">Out of Stock</a>
                        <a href="?section=inventory&status=expiry" style="color: <?php echo (isset($_GET['status']) && $_GET['status'] == 'expiry') ? '#f59e0b' : '#94a3b8'; ?>; text-decoration: none; padding: 5px 15px; font-size: 14px; font-weight: 600; border-bottom: 2px solid <?php echo (isset($_GET['status']) && $_GET['status'] == 'expiry') ? '#f59e0b' : 'transparent'; ?>;">Expiring Soon</a>
                        <a href="?section=inventory&status=restocked" style="color: <?php echo (isset($_GET['status']) && $_GET['status'] == 'restocked') ? '#10b981' : '#94a3b8'; ?>; text-decoration: none; padding: 5px 15px; font-size: 14px; font-weight: 600; border-bottom: 2px solid <?php echo (isset($_GET['status']) && $_GET['status'] == 'restocked') ? '#10b981' : 'transparent'; ?>;">Recently Restocked</a>
                    </div>
                    
                    <?php if (isset($_GET['status']) && ($_GET['status'] == 'out' || $_GET['status'] == 'expiry') && ($count_out > 0 || $count_expiring > 0) && (!isset($_SESSION['hide_stock_alert']) || $_SESSION['hide_stock_alert'] === false)): ?>
                        <?php 
                        $at = ($_GET['status'] == 'out') ? 'shortage' : 'expiry';
                        $ac = ($_GET['status'] == 'out') ? $count_out : $count_expiring;
                        $atxt = ($_GET['status'] == 'out') ? 'out of stock' : 'expiring soon';
                        $clr = ($_GET['status'] == 'out') ? '#ef4444' : '#f59e0b';
                        ?>
                        <div style="background: <?php echo ($at=='shortage'?'rgba(239, 68, 68, 0.1)':'rgba(245, 158, 11, 0.1)'); ?>; border: 1px dashed <?php echo $clr; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="color: <?php echo $clr; ?>; display: block; margin-bottom: 4px;">Action Required</strong>
                                <span style="color: #cbd5e1; font-size: 13px;">You have <?php echo $ac; ?> medicines <?php echo $atxt; ?>. Notify admin to take action.</span>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="notify_admin">
                                <input type="hidden" name="alert_type" value="<?php echo $at; ?>">
                                <button type="submit" style="background: <?php echo $clr; ?>; color: white; border: none; padding: 8px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                                    <i class="fas fa-bell"></i> Notify Admin
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <form method="GET" style="margin-bottom: 20px; display: flex; gap: 15px;">
                        <input type="hidden" name="section" value="inventory">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($_GET['status'] ?? 'all'); ?>">
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
                            $status_filter = $_GET['status'] ?? 'all';
                            
                            $where_clauses = [];
                            if($search_term) {
                                $where_clauses[] = "(medicine_name LIKE '%$search_term%' OR manufacturer LIKE '%$search_term%')";
                            }
                            if($cat_term) {
                                $where_clauses[] = "medicine_type = '$cat_term'";
                            }
                            
                            // Status Filters
                            if($status_filter == 'instock') {
                                $where_clauses[] = "quantity >= 20";
                            } elseif($status_filter == 'low') {
                                $where_clauses[] = "quantity > 0 AND quantity < 20";
                            } elseif($status_filter == 'out') {
                                $where_clauses[] = "quantity = 0";
                            } elseif($status_filter == 'expiry') {
                                $where_clauses[] = "expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND expiry_date > CURDATE() AND status != 'Inactive'";
                            } elseif($status_filter == 'restocked') {
                                $where_clauses[] = "last_restocked_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND quantity > 0";
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
                                        $is_physically_expired = (strtotime($item['expiry_date']) < time());
                                        
                                        if ($item['status'] == 'Blocked') {
                                            $status_color = '#f59e0b';
                                            $status_text = 'BLOCKED';
                                        } elseif ($item['status'] == 'Expired' || $is_physically_expired) {
                                            $status_color = '#ef4444';
                                            $status_text = 'EXPIRED';
                                        } elseif ($item['quantity'] == 0) {
                                            $status_color = '#ef4444';
                                            $status_text = 'STOCK OUT';
                                        } elseif ($item['quantity'] < 20) {
                                            $status_color = '#ef4444';
                                            $status_text = 'Low Stock';
                                        } else {
                                            $status_color = '#10b981';
                                            $status_text = 'Active';
                                        }

                                        $item_json = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item['medicine_name']); ?></strong><br><span style='font-size:11px; color:#64748b;'><?php echo htmlspecialchars($item['manufacturer'] ?? ''); ?></span></td>
                                            <td><?php echo htmlspecialchars($item['medicine_type']); ?></td>
                                            <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                                            <td><?php echo htmlspecialchars($item['expiry_date']); ?></td>
                                            <td>₹<?php echo htmlspecialchars($item['unit_price']); ?></td>
                                            <td style='font-weight:bold;'><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td><span style='color: <?php echo $status_color; ?>; font-size: 11px; border: 1px solid <?php echo $status_color; ?>; padding: 2px 8px; border-radius: 10px;'><?php echo $status_text; ?></span></td>
                                            <td>
                                                <div style="display:flex; gap:8px; align-items:center;">
                                                    <button type='button' data-medicine='<?php echo $item_json; ?>' onclick='openEditModal(this)' style='background:none; border:none; color:#4fc3f7; cursor:pointer;' title="Edit Details"><i class='fas fa-edit'></i></button>
                                                    
                                                    <?php if($item['status'] == 'Active' && !$is_physically_expired): ?>
                                                        <button type="button" onclick="openStatusModal('<?php echo $item['stock_id']; ?>', '<?php echo addslashes($item['medicine_name']); ?>')" style="background:none; border:none; color:#f59e0b; cursor:pointer;" title="Flag Status"><i class="fas fa-flag"></i></button>
                                                    <?php endif; ?>

                                                    <?php if($item['quantity'] == 0): ?>
                                                        <button type='button' onclick="openModal('addStockModal'); document.getElementsByName('med_name')[0].value='<?php echo addslashes($item['medicine_name']); ?>'; document.getElementsByName('med_type')[0].value='<?php echo addslashes($item['medicine_type']); ?>';" style='background:none; border:none; color:#10b981; cursor:pointer;' title='Restock'><i class='fas fa-plus-circle'></i></button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
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
                            // Fetch Dispensed Prescriptions (Source of Truth) linked with Billing Info
                            $hist_sql = "
                                SELECT p.prescription_id, 
                                       p.prescription_date,
                                       rp.name as patient_name,
                                       b.bill_id, 
                                       b.total_amount, 
                                       b.payment_status
                                FROM prescriptions p
                                JOIN users up ON p.patient_id = up.user_id
                                JOIN registrations rp ON up.registration_id = rp.registration_id
                                LEFT JOIN billing b ON p.prescription_id = b.reference_id 
                                WHERE p.status = 'Dispensed'
                                ORDER BY p.prescription_date DESC LIMIT 50
                            ";
                            $hist_res = $conn->query($hist_sql);
                            
                            if(!$hist_res) {
                                echo "<tr><td colspan='6'>Error loading history: " . $conn->error . "</td></tr>";
                            } else {
                                if($hist_res->num_rows > 0) {
                                    while($row = $hist_res->fetch_assoc()) {
                                        $inv_display = $row['bill_id'] ? "#INV-".str_pad($row['bill_id'], 4, '0', STR_PAD_LEFT) : "<span style='color:#94a3b8;'>Unbilled</span>";
                                        $amt_display = $row['total_amount'] ? "$".number_format($row['total_amount'], 2) : "-";
                                        
                                        echo "<tr>
                                            <td>" . $inv_display . "</td>
                                            <td><strong style='color:white;'>".htmlspecialchars($row['patient_name'])."</strong></td>
                                            <td>".date('M d, Y', strtotime($row['prescription_date']))."</td>
                                            <td><span style='font-size:12px; color:#cbd5e1;'>RX #". $row['prescription_id'] ." - Medicines</span></td>
                                            <td>" . $amt_display . "</td>
                                            <td><span class='status-pill' style='color:#10b981; border:1px solid #10b981; padding:2px 8px; border-radius:10px;'>Dispensed</span></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align:center; padding: 30px;'>No dispensed medicines found.</td></tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($_GET['section'] == 'alerts'): ?>
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="color:#ef4444; font-size: 28px;">Expiry Alerts</h1>
                        <p style="color:#64748b; font-size:14px;">Medicines expiring within the next 3 months.</p>
                    </div>
                    <?php
                    // Check if there are any expiring items to notify about
                    $check_exp = $conn->query("SELECT COUNT(*) as count FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)");
                    if ($check_exp && $check_exp->fetch_assoc()['count'] > 0):
                    ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="notify_admin">
                        <input type="hidden" name="alert_type" value="expiry">
                        <button type="submit" style="background: #ef4444; color: white; border: none; padding: 10px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-bell"></i> Notify Admin of Expiry
                        </button>
                    </form>
                    <?php endif; ?>
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
                                <th>Action</th>
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
                                    $is_physically_expired = ($row['days_left'] <= 0);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['medicine_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                        <td style='color: <?php echo $days_color; ?>; font-weight:bold;'><?php echo htmlspecialchars($row['expiry_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                        <td style='color: <?php echo $days_color; ?>;'><?php echo ($row['days_left'] > 0 ? $row['days_left'].' Days' : 'EXPIRED'); ?></td>
                                        <td>
                                            <?php if($row['status'] == 'Active' && !$is_physically_expired): ?>
                                                <button type="button" onclick="openStatusModal('<?php echo $row['stock_id']; ?>', '<?php echo addslashes($row['medicine_name']); ?>')" style="background:none; border:none; color:#f59e0b; cursor:pointer;" title="Flag Status"><i class="fas fa-flag"></i> Flag</button>
                                            <?php elseif($is_physically_expired && $row['status'] != 'Expired'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_medicine_status">
                                                    <input type="hidden" name="stock_id" value="<?php echo $row['stock_id']; ?>">
                                                    <input type="hidden" name="new_status" value="Expired">
                                                    <input type="hidden" name="reason" value="Physically Expired - Automated Cleanup">
                                                    <button type="submit" style="background:rgba(239, 68, 68, 0.2); border:1px solid #ef4444; color:#ef4444; padding:2px 8px; border-radius:5px; cursor:pointer; font-size:11px;">Set Expired</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color:#64748b; font-size:11px;"><?php echo $row['status']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center; padding: 30px;'>No expiry alerts at this time.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($_GET['section'] == 'logs'): ?>
                <div style="margin-bottom: 30px;">
                    <h1 style="color:#fff; font-size: 28px;">Stock Movement Logs</h1>
                    <p style="color:#64748b; font-size:14px;">Track all medicines added to and dispensed from the pharmacy.</p>
                </div>

                <div style="background: #0f172a; border: 1px solid var(--border-soft); border-radius: 12px; padding: 25px;">
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <a href="?section=logs&filter=all" style="padding: 8px 15px; background: <?php echo (!isset($_GET['filter']) || $_GET['filter']=='all') ? '#4fc3f7' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo (!isset($_GET['filter']) || $_GET['filter']=='all') ? '#020617' : '#94a3b8'; ?>; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: bold;">All Activity</a>
                        <a href="?section=logs&filter=added" style="padding: 8px 15px; background: <?php echo (isset($_GET['filter']) && $_GET['filter']=='added') ? '#10b981' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo (isset($_GET['filter']) && $_GET['filter']=='added') ? '#fff' : '#94a3b8'; ?>; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: bold;">Stock Added</a>
                        <a href="?section=logs&filter=dispensed" style="padding: 8px 15px; background: <?php echo (isset($_GET['filter']) && $_GET['filter']=='dispensed') ? '#f59e0b' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo (isset($_GET['filter']) && $_GET['filter']=='dispensed') ? '#fff' : '#94a3b8'; ?>; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: bold;">Dispensed</a>
                    </div>

                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Action</th>
                                <th>Medicine Name</th>
                                <th>Quantity</th>
                                <th>Details</th>
                                <th>Performed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $filter = $_GET['filter'] ?? 'all';
                            $where_sql = "";
                            if($filter == 'added') $where_sql = "WHERE action_type = 'Added'";
                            elseif($filter == 'dispensed') $where_sql = "WHERE action_type = 'Dispensed'";

                            $log_sql = "SELECT l.*, u.username FROM pharmacy_logs l LEFT JOIN users u ON l.performed_by = u.user_id $where_sql ORDER BY l.log_date DESC LIMIT 100";
                            $log_res = $conn->query($log_sql);

                            if($log_res && $log_res->num_rows > 0) {
                                while($row = $log_res->fetch_assoc()) {
                                    $action_color = ($row['action_type'] == 'Added') ? '#10b981' : '#f59e0b';
                                    $qty_sign = ($row['action_type'] == 'Added') ? '+' : '-';
                                    
                                    echo "<tr>
                                        <td>".date('M d, Y h:i A', strtotime($row['log_date']))."</td>
                                        <td><span style='color: $action_color; font-weight: bold; border: 1px solid $action_color; padding: 2px 8px; border-radius: 10px; font-size: 11px;'>".strtoupper($row['action_type'])."</span></td>
                                        <td><strong>".htmlspecialchars($row['medicine_name'])."</strong></td>
                                        <td style='color: $action_color; font-weight: bold;'>$qty_sign".htmlspecialchars($row['quantity'])."</td>
                                        <td style='color: #94a3b8; font-size: 12px;'>".htmlspecialchars($row['details'])."</td>
                                        <td style='font-size: 12px;'>".htmlspecialchars($row['username'] ?? 'Unknown')."</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center; padding: 30px;'>No logs found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </main>
    </div>


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
                <div style="margin-bottom:15px;">
                    <label style="color:#94a3b8; font-size:12px; display:block; mb-1;">Supplier Invoice No.</label>
                    <input type="text" name="supplier_invoice" required placeholder="Enter Invoice Number" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                </div>
                <div style="margin-bottom:15px; background:rgba(16, 185, 129, 0.1); padding:10px; border-radius:6px; border:1px dashed #10b981;">
                    <label style="color:#10b981; font-size:12px; display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="verified_check" required style="width:16px; height:16px;">
                        I verify that I have checked the physical stock, invoice, batch, expiry, quantity, and price.
                    </label>
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
                </div>
                    <div>
                        <label style="color:#94a3b8; font-size:12px; display:block;">Unit Price ($)</label>
                        <input type="number" step="0.01" name="price" id="edit_price" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                    </div>
                <div style="margin-bottom:20px;">
                    <label style="color:#94a3b8; font-size:12px; display:block;">Shelf Location</label>
                    <input type="text" name="location" id="edit_loc" placeholder="e.g. Shelf A1" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                </div>
                <button type="submit" style="width:100%; padding:12px; background:#4fc3f7; color:#020617; font-weight:bold; border:none; border-radius:8px; cursor:pointer;">Update Stock</button>
            </form>
        </div>
    </div>

    <!-- Pharmacy Bill Modal (Restored & Updated) -->
    <div id="billModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#0f172a; padding:30px; border-radius:12px; width:500px; max-width:90%; border:1px solid rgba(255,255,255,0.1);">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="color:white;">Generate Pharmacy Bill</h3>
                <i class="fas fa-times" style="color:#64748b; cursor:pointer;" onclick="document.getElementById('billModal').style.display='none'"></i>
            </div>
            <form action="generate_bill.php" method="POST">
                <input type="hidden" name="patient_id" id="bill_pid">
                <input type="hidden" name="doctor_id" id="bill_did">
                <input type="hidden" name="appointment_id" id="bill_aid">
                <input type="hidden" name="reference_id" id="bill_ref">
                <input type="hidden" name="bill_type" value="Pharmacy">
                
                <div style="margin-bottom:15px;">
                    <label style="color:#94a3b8; font-size:12px; display:block; margin-bottom:5px;">Patient Name</label>
                    <input type="text" id="bill_pname" readonly style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px; cursor:not-allowed;">
                </div>

                <div style="margin-bottom:15px; background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 8px; border: 1px dashed #10b981;">
                    <label style="color:#10b981; font-size:13px; font-weight:bold; display:block; margin-bottom:5px;"><i class="fas fa-calculator"></i> System Auto-Calculation</label>
                    <p style="color:#cbd5e1; font-size:12px; margin:0;">Total amount will be calculated automatically based on medicine prices and prescribed duration. Minimum charge of $150 applies if calculation fails.</p>
                </div>

                <div style="margin-bottom:15px; display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" name="bill_type" value="Combined" id="is_combined" style="width:20px; height:20px;">
                    <label for="is_combined" style="color:white; font-size:14px; cursor:pointer;">Consolidate with Consultation & Lab Fees</label>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="color:#94a3b8; font-size:12px; display:block; margin-bottom:5px;">Remarks (Optional)</label>
                    <textarea name="description" id="bill_desc" rows="3" placeholder="List of medicines (optional)" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px; resize:none;"></textarea>
                </div>

                <button type="submit" style="width:100%; padding:12px; background:#4fc3f7; color:#020617; font-weight:bold; border:none; border-radius:8px; cursor:pointer;">Confirm & Generate Bill</button>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#0f172a; padding:30px; border-radius:12px; width:450px; max-width:90%; border:1px solid rgba(255,255,255,0.1);">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="color:white;">Flag Medicine Status</h3>
                <i class="fas fa-times" style="color:#64748b; cursor:pointer;" onclick="document.getElementById('statusModal').style.display='none'"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_medicine_status">
                <input type="hidden" name="stock_id" id="status_stock_id">
                
                <div style="margin-bottom:15px;">
                    <label style="color:#94a3b8; font-size:12px; display:block; margin-bottom:5px;">Medicine</label>
                    <input type="text" id="status_med_name" readonly style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="color:#94a3b8; font-size:12px; display:block; margin-bottom:5px;">New Status</label>
                    <select name="new_status" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px;">
                        <option value="Near Expiry">Near Expiry</option>
                        <option value="Expired">Expired</option>
                        <option value="Blocked">Blocked (Safety Concern)</option>
                    </select>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="color:#94a3b8; font-size:12px; display:block; margin-bottom:5px;">Reason / Note for Admin</label>
                    <textarea name="reason" required placeholder="Describe why this status is being set..." rows="3" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid #334155; color:white; border-radius:6px; resize:none;"></textarea>
                </div>

                <button type="submit" style="width:100%; padding:12px; background:#f59e0b; color:#020617; font-weight:bold; border:none; border-radius:8px; cursor:pointer;">Update Status & Notify Admin</button>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(id, name) {
            document.getElementById('statusModal').style.display = 'flex';
            document.getElementById('status_stock_id').value = id;
            document.getElementById('status_med_name').value = name;
        }

        function notifyAdmin(type) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.name = 'action';
            actionInput.value = 'notify_admin';
            form.appendChild(actionInput);

            const typeInput = document.createElement('input');
            typeInput.name = 'alert_type';
            typeInput.value = type || 'shortage';
            form.appendChild(typeInput);

            document.body.appendChild(form);
            form.submit();
        }

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }


        function openBillModalCombined(rxId, pId, dId, pName, aId) {
            document.getElementById('billModal').style.display = 'flex';
            document.getElementById('bill_ref').value = rxId;
            document.getElementById('bill_pid').value = pId;
            document.getElementById('bill_did').value = dId;
            document.getElementById('bill_aid').value = aId;
            document.getElementById('bill_pname').value = pName;
            document.getElementById('bill_desc').value = "Dispensing Rx #" + rxId;
            // Workflow: Consolidated Bill (Pharmacist clears all pending clinic fees)
            document.getElementById('is_combined').checked = true;
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
    <script>
        // Brand Animation
        document.addEventListener('DOMContentLoaded', function() {
            function initBrandAnimation() {
                const brandElement = document.querySelector('.animated-brand');
                if (!brandElement) return;

                const text = brandElement.textContent.trim();
                brandElement.textContent = ''; // Clear text

                // Create spans
                const letters = [];
                for (let char of text) {
                    const span = document.createElement('span');
                    span.textContent = char;
                    span.classList.add('brand-letter');
                    if (char === ' ') {
                        span.style.width = '0.3em'; // Space
                        span.style.display = 'inline-block';
                    }
                    brandElement.appendChild(span);
                    letters.push(span);
                }

                function animate() {
                    // Show sequence
                    letters.forEach((letter, index) => {
                        setTimeout(() => {
                            letter.classList.add('visible');
                        }, index * 100); // 100ms staggering
                    });

                    // Hide sequence (after full text is shown + delay)
                    const totalTime = (letters.length * 100) + 2000; // 2s pause

                    setTimeout(() => {
                        letters.forEach((letter, index) => {
                            setTimeout(() => {
                                letter.classList.remove('visible');
                            }, index * 50); // Faster fade out
                        });
                    }, totalTime);

                    const cycleTime = totalTime + (letters.length * 50) + 500; // time to hide + pause

                    setTimeout(animate, cycleTime);
                }

                // Start animation
                animate();
            }
            
            initBrandAnimation();
        });
    </script>
    <!-- Report Upload Modal Integration -->
    <?php 
    $staff_type = 'pharmacist';
    include 'includes/report_upload_modal.php'; 
    ?>
</body>
</html>
