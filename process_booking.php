<?php
ob_start();
session_start();
include 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Capture Form Data
    $doctor_id = $_POST['doctor_id'];
    $department = $_POST['dept'];
    $date = $_POST['date'];
    $time_slot = $_POST['time_slot'];
    $token = $_POST['token'];
    
    $reg_status = $_POST['reg_status'];
    $patient_id = null;
    $patient_name = "";

    $conn->begin_transaction();

    try {
        if (isset($_SESSION['user_id'])) {
            // Logged in User - Use Session
            $patient_id = $_SESSION['user_id'];
            $patient_name = $_SESSION['full_name'];
            
            // Verify if profile exists, if not create basic one?
            // Assuming existed.
        } elseif ($reg_status == 'yes') {
            // Existing Patient (Lookup)
            $op_number = $_POST['op_number'] ?? '';
            $mobile = $_POST['reg_mobile'];
            
            // Search Logic
            if (!empty($op_number)) {
                $stmt = $conn->prepare("SELECT user_id, name FROM patient_profiles WHERE patient_code = ?");
                $stmt->bind_param("s", $op_number);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $patient_id = $row['user_id'];
                    $patient_name = $row['name'];
                }
            }
            
            // Fallback to Phone if not found or OP number empty
            if (empty($patient_id) && !empty($mobile)) {
                // Check Registrations table for phone
                $stmt = $conn->prepare("SELECT u.user_id, r.name FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE r.phone like ?");
                // Allow fuzzy match? No, exact.
                $stmt->bind_param("s", $mobile);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $patient_id = $row['user_id'];
                    $patient_name = $row['name'];
                }
            }

            if (empty($patient_id)) {
                 throw new Exception("Patient not found. Please check your OP Number or Registered Mobile Number.");
            }
            
        } else {
            // New Patient - Create User & Profile
            $fname = $_POST['first_name'];
            $lname = $_POST['last_name'];
            $full_name = $fname . " " . $lname;
            $address = $_POST['address'];
            $gender = $_POST['gender'];
            $age = $_POST['age'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            
            // 1. Create Registration
            $password = password_hash("12345678", PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO registrations (name, email, phone, password, user_type, status) VALUES (?, ?, ?, ?, 'patient', 'Approved')");
            $stmt->bind_param("ssss", $full_name, $email, $phone, $password);
            $stmt->execute();
            $reg_id = $conn->insert_id;
            
            // 2. Create User
            $username = "P" . rand(10000, 99999);
            $stmt = $conn->prepare("INSERT INTO users (registration_id, username, email, password, role, status) VALUES (?, ?, ?, ?, 'patient', 'Active')");
            $stmt->bind_param("isss", $reg_id, $username, $email, $password);
            $stmt->execute();
            $patient_id = $conn->insert_id;
            
            // 3. Create Profile
            $patient_code = "HC-P-" . date("Y") . "-" . rand(1000, 9999);
            $stmt = $conn->prepare("INSERT INTO patient_profiles (user_id, patient_code, name, phone, address, gender) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $patient_id, $patient_code, $full_name, $phone, $address, $gender);
            $stmt->execute();
            
            $patient_name = $full_name;
        }

        // 2. Insert Appointment
        // Fetch doctor's fee to ensure consistency
        $fee_res = $conn->query("SELECT consultation_fee FROM doctors WHERE user_id = $doctor_id");
        $doc_fee = 200.00; // Default fallback
        if($fee_res && $fee_res->num_rows > 0) {
            $row = $fee_res->fetch_assoc();
            $doc_fee = $row['consultation_fee'];
        }

        // appointment_time is TIME type. $time_slot is like "09:00 AM". MySQL handles conversion usually.
        // Convert to 24h format for safety
        $appt_time = date("H:i", strtotime($time_slot));
        
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, department, appointment_date, appointment_time, appointment_type, status, queue_number, consultation_fee) VALUES (?, ?, ?, ?, ?, 'Walk-in', 'Pending', ?, ?)");
        $stmt->bind_param("iisssid", $patient_id, $doctor_id, $department, $date, $appt_time, $token, $doc_fee);
        $stmt->execute();
        
        $appt_id = $conn->insert_id;
        $booking_id = "BK-" . $appt_id;

        // 3. Create Bill for Consultation
        $bill_type = 'Consultation';
        $payment_status = 'Pending';
        $bill_date = date('Y-m-d');
        $stmt_bill = $conn->prepare("INSERT INTO billing (patient_id, appointment_id, bill_type, total_amount, doctor_id, payment_status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_bill->bind_param("iisdiss", $patient_id, $appt_id, $bill_type, $doc_fee, $doctor_id, $payment_status, $bill_date);
        $stmt_bill->execute();
        $bill_id = $conn->insert_id;

        $conn->commit();
        
        // Redirect to Success with Bill ID
        header("Location: booking_success.php?booking_id=$booking_id&token=$token&doctor=$doctor_id&date=$date&time=$time_slot&patient=" . urlencode($patient_name) . "&fee=$doc_fee&bill_id=$bill_id");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
        // In production, log error and show friendly message
    }
}
?>
