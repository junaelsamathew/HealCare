<?php
session_start();
file_put_contents('c:/xampp/htdocs/HealCare/entry_debug.txt', date('Y-m-d H:i:s') . " - Script ACCESSED. Method: " . $_SERVER['REQUEST_METHOD'] . "\nPOST: " . print_r($_POST, true) . "\nSESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
include 'includes/db_connect.php';

// Debug check
if(isset($_GET['test'])) die("Script is reachable.");

// Only staff can generate bills
// Only staff or patients (for specific actions) can generate bills
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['staff', 'admin', 'doctor', 'patient'])) {
    die("Unauthorized access");
}

// Debug check
if(isset($_GET['test'])) die("Script is reachable.");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['user_role'] == 'patient') {
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = intval($_POST['doctor_id']); // Can be 0 if unknown
    $amount = floatval($_POST['amount']);
    $bill_type = $_POST['bill_type']; // 'Lab Test', 'Pharmacy', etc.
    $description = $_POST['description'] ?? '';
    $ref_id = intval($_POST['reference_id'] ?? $_POST['ref_id']); // Handle both just in case
    
    // Handle Combined Bill (Pharmacy + Lab)
    if ($bill_type === 'Combined') {
        $amount = 0;
        $desc_parts = [];
        $medicine_list = [];
        $appt_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        
        if($appt_id == 0 && $doctor_id > 0) {
             $q = $conn->query("SELECT appointment_id FROM appointments WHERE patient_id = $patient_id AND doctor_id = $doctor_id ORDER BY appointment_date DESC LIMIT 1");
             if($q && $q->num_rows > 0) $appt_id = $q->fetch_assoc()['appointment_id'];
        }
        
        // 1. Calculate Pharmacy Component
        if ($ref_id > 0) { 
             $presc_q = $conn->query("SELECT medicine_details FROM prescriptions WHERE prescription_id = $ref_id");
             if ($presc_q && $presc_q->num_rows > 0) {
                 $presc_text = strtolower($presc_q->fetch_assoc()['medicine_details']);
                 $p_cost = 0;
                 $stock_q = $conn->query("SELECT medicine_name, unit_price FROM pharmacy_stock");
                 while ($stock = $stock_q->fetch_assoc()) {
                     $name = strtolower($stock['medicine_name']);
                     if (strpos($presc_text, $name) !== false) {
                         $price = floatval($stock['unit_price']);
                         $days = 5; 
                         if (preg_match('/(\d+)\s*days?/i', $presc_text, $matches)) $days = intval($matches[1]);
                         $per_day = 2;
                         if (preg_match('/(\d+)-(\d+)-(\d+)/', $presc_text, $f_matches)) $per_day = intval($f_matches[1]) + intval($f_matches[2]) + intval($f_matches[3]);
                         $p_cost += ($days * $per_day * $price);
                         $medicine_list[] = ucwords($stock['medicine_name']);
                     }
                 }
                 if($p_cost == 0) $p_cost = 150.00;
                 $amount += $p_cost;
                 $desc_parts[] = "Medicines: " . implode(", ", $medicine_list);
             }
        }

        // 2. Calculate Lab Component
        if ($appt_id > 0) {
             $lab_q = $conn->query("SELECT test_name FROM lab_tests WHERE appointment_id = $appt_id AND status != 'Cancelled'");
             if ($lab_q && $lab_q->num_rows > 0) {
                 $l_cost = 0;
                 $tests = [];
                 while($l = $lab_q->fetch_assoc()) {
                     $l_cost += 500.00; 
                     $tests[] = $l['test_name'];
                 }
                 $amount += $l_cost;
                 $desc_parts[] = "Lab Tests: " . implode(", ", $tests);
                 $conn->query("UPDATE billing SET payment_status = 'Paid', payment_mode = 'Merged', transaction_ref = 'Merged into Combined Bill' WHERE appointment_id = $appt_id AND bill_type LIKE 'Lab Test%' AND payment_status = 'Pending'");
             }
        }
        
        // 3. Calculate Consultation Fee (Include if details are pending)
        if ($appt_id > 0) {
             $cons_q = $conn->query("SELECT total_amount FROM billing WHERE appointment_id = $appt_id AND bill_type = 'Consultation' AND payment_status = 'Pending'");
             if ($cons_q && $cons_q->num_rows > 0) {
                 $c_amt = $cons_q->fetch_assoc()['total_amount'];
                 $amount += $c_amt;
                 $desc_parts[] = "Consultation (₹$c_amt)";
                 $conn->query("UPDATE billing SET payment_status = 'Paid', payment_mode = 'Merged', transaction_ref = 'Merged into Combined Bill' WHERE appointment_id = $appt_id AND bill_type = 'Consultation' AND payment_status = 'Pending'");
             }
        }
        
        $bill_type = 'Pharmacy / Complete Clinic Bill';
        $description = implode(" | ", $desc_parts);
    }
    
    // Automatic Calculation for Pharmacy (Standalone)
    if ($bill_type === 'Pharmacy' && $ref_id > 0) {
        $amount = 0;
        $medicine_list = [];
        $presc_q = $conn->query("SELECT medicine_details FROM prescriptions WHERE prescription_id = $ref_id");
        if ($presc_q && $presc_q->num_rows > 0) {
            $presc_text = strtolower($presc_q->fetch_assoc()['medicine_details']);
            $stock_q = $conn->query("SELECT medicine_name, unit_price FROM pharmacy_stock");
            while ($stock = $stock_q->fetch_assoc()) {
                $name = strtolower($stock['medicine_name']);
                if (strpos($presc_text, $name) !== false) {
                    $price = floatval($stock['unit_price']);
                    $days = 5; 
                    if (preg_match('/(\d+)\s*days?/i', $presc_text, $matches)) {
                        $days = intval($matches[1]);
                    } elseif (preg_match('/(\d+)\s*weeks?/i', $presc_text, $matches)) {
                        $days = intval($matches[1]) * 7;
                    }

                    $per_day = 2;
                    if (preg_match('/(\d+)-(\d+)-(\d+)/', $presc_text, $f_matches)) {
                        $per_day = intval($f_matches[1]) + intval($f_matches[2]) + intval($f_matches[3]);
                    } elseif (strpos($presc_text, 'od') !== false) { $per_day = 1; }
                    elseif (strpos($presc_text, 'tds') !== false) { $per_day = 3; }
                    
                    $qty = $days * $per_day;
                    $amount += ($qty * $price);
                    $medicine_list[] = ucwords($stock['medicine_name']);
                }
            }
        }
        if ($amount == 0) $amount = 150.00;
        $bill_type = 'Pharmacy / Medicines';
        $description = "Dispensed Medicines: " . implode(", ", $medicine_list);
    } elseif ($amount <= 0 && $patient_id <= 0) {
        die("Invalid Amount or Patient ID");
    }

    $payment_status = 'Pending';
    $bill_date = date('Y-m-d');
    $appt_id = null;
    
    // Try to link to a recent active appointment for context IF not already set
    if (!$appt_id && $doctor_id > 0) {
        $q = $conn->query("SELECT appointment_id FROM appointments WHERE patient_id = $patient_id AND doctor_id = $doctor_id ORDER BY appointment_date DESC LIMIT 1");
        if($q && $q->num_rows > 0){
            $appt_id = $q->fetch_assoc()['appointment_id'];
        }
    }
    
    // If still null, create a dummy or allow null?
    // Let's check if `billing` allows NULL appointment_id. 
    // If not, we might fail.
    
    // Final Validation
    // Final Validation
    if ($amount <= 0) {
        $amount = 150.00; // Ultimate fallback
    }

    $debug_log = 'c:/xampp/htdocs/HealCare/billing_debug.log';
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Attempting Insert: Patient: $patient_id, Ref: $ref_id, Type: $bill_type, Amt: $amount, Appt: " . ($appt_id ?: 'NULL') . "\n", FILE_APPEND);

    // Prepare Insert
    $stmt = $conn->prepare("INSERT INTO billing (patient_id, doctor_id, appointment_id, reference_id, bill_type, description, total_amount, payment_status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if(!$stmt) {
        file_put_contents($debug_log, "Prepare Failed: " . $conn->error . "\n", FILE_APPEND);
        die("System Error: Prepare Failed");
    }
    
    $stmt->bind_param("iiiissdss", $patient_id, $doctor_id, $appt_id, $ref_id, $bill_type, $description, $amount, $payment_status, $bill_date);
    
    if ($stmt->execute()) {
        $bill_id = $conn->insert_id;
        // Explicit Commit just in case
        $conn->commit(); 
        
        file_put_contents($debug_log, "Success! Bill ID: $bill_id\n", FILE_APPEND);
        
        // Handle Post-Action Redirects
        if($_SESSION['user_role'] == 'patient') {
             // header("Location: payment_gateway.php?bill_id=" . $bill_id);
             ?>
             <!DOCTYPE html>
             <html lang="en">
             <head>
                 <meta charset="UTF-8">
                 <meta name="viewport" content="width=device-width, initial-scale=1.0">
                 <title>Bill Generated - HealCare</title>
                 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                 <style>
                     body {
                         background: #0f172a;
                         font-family: 'Poppins', sans-serif;
                         display: flex;
                         align-items: center;
                         justify-content: center;
                         height: 100vh;
                         margin: 0;
                         color: white;
                     }
                     .success-card {
                         background: #1e293b;
                         padding: 40px;
                         border-radius: 20px;
                         text-align: center;
                         width: 100%;
                         max-width: 400px;
                         box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                         border: 1px solid rgba(255,255,255,0.05);
                     }
                     .icon-box {
                         width: 80px; height: 80px;
                         background: rgba(16, 185, 129, 0.1);
                         border-radius: 50%;
                         display: flex;
                         align-items: center;
                         justify-content: center;
                         margin: 0 auto 20px;
                         color: #10b981;
                         font-size: 32px;
                         border: 2px solid rgba(16, 185, 129, 0.2);
                     }
                     h1 { margin: 0 0 10px; font-size: 20px; font-weight: 600; }
                     p { color: #94a3b8; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
                     .bill-info {
                         background: rgba(255,255,255,0.02);
                         border-radius: 12px;
                         padding: 15px;
                         margin-bottom: 25px;
                         text-align: left;
                     }
                     .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
                     .row:last-child { margin-bottom: 0px; font-weight: 600; color: #fff; font-size: 14px; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.05); }
                     .row span:first-child { color: #94a3b8; }
                     
                     .pay-btn {
                         background: #3b82f6;
                         color: white;
                         text-decoration: none;
                         display: block;
                         padding: 15px;
                         border-radius: 12px;
                         font-weight: 600;
                         font-size: 14px;
                         transition: 0.2s;
                     }
                     .pay-btn:hover { background: #2563eb; transform: translateY(-2px); }
                     
                     .close-link { display: block; margin-top: 15px; color: #64748b; font-size: 12px; text-decoration: none; }
                     .close-link:hover { color: #94a3b8; }
                 </style>
             </head>
             <body>
                 <div class="success-card">
                     <div class="icon-box">
                         <i class="fas fa-check"></i>
                     </div>
                     <h1>Bill Generated!</h1>
                     <p>Your invoice has been successfully created. You can now proceed to payment securely.</p>
                     
                     <div class="bill-info">
                         <div class="row">
                             <span>Bill ID</span>
                             <span>#<?php echo str_pad($bill_id, 4, '0', STR_PAD_LEFT); ?></span>
                         </div>
                         <div class="row">
                             <span>Date</span>
                             <span><?php echo date('M d, Y'); ?></span>
                         </div>
                         <div class="row">
                             <span>Total Amount</span>
                             <span>₹<?php echo number_format($amount, 2); ?></span>
                         </div>
                     </div>
                     
                     <a href="payment_gateway.php?bill_id=<?php echo $bill_id; ?>" class="pay-btn">
                         Pay ₹<?php echo number_format($amount, 2); ?> Now <i class="fas fa-arrow-right" style="font-size:12px; margin-left:5px;"></i>
                     </a>
                     <a href="javascript:window.close()" class="close-link">Close Window</a>
                 </div>
             </body>
             </html>
             <?php
             exit;
        }

        if (strpos($bill_type, 'Pharmacy') !== false) {
             // Link prescription to "Awaiting Payment" status
             $conn->query("UPDATE prescriptions SET status = 'Awaiting Payment' WHERE prescription_id = $ref_id");
             $conn->commit();
             $msg = ($bill_type == 'Pharmacy / Complete Clinic Bill') ? "Consolidated bill (Medicine + Clinic Fees) generated successfully!" : "Medicine bill generated and sent to patient!";
             header("Location: staff_pharmacist_dashboard.php?section=dashboard&msg=" . urlencode($msg));
        } elseif (strpos($bill_type, 'Lab') !== false) {
             header("Location: staff_lab_staff_dashboard.php?section=processing&msg=Bill+Generated+Successfully");
        } else {
             header("Location: index.php");
        }
    } else {
        file_put_contents($debug_log, "Execute Failed: " . $stmt->error . "\n", FILE_APPEND);
        echo "Error generating bill: " . $stmt->error;
    }
}
?>
