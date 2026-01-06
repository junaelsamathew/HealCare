<?php
session_start();
include 'includes/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'request_otp') {
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        
        // Check if email exists in users OR registrations (Case insensitive & Trimming DB side)
        $check = $conn->query("SELECT * FROM users WHERE TRIM(email) = '$email'");
        $check_reg = $conn->query("SELECT * FROM registrations WHERE TRIM(email) = '$email'");
        
        if ($check->num_rows == 0 && $check_reg->num_rows == 0) {
            echo "<script>alert('Email not found! Received: [$email]'); window.location.href='login.php'</script>";
            exit();
        }

        $otp = rand(100000, 999999);
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_expiry'] = time() + 300; // 5 mins

        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'junaelsamathew2028@mca.ajce.in'; // Updated based on user context
            $mail->Password   = 'yiuwcrykatkfzdwv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            
            // Fix for local XAMPP SSL issues
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            //Recipients
            $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare Hospital');
            $mail->addAddress($email);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Verification Code - HealCare';
            
            // Styled Email Body
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 500px; color: #333; line-height: 1.6;">
                <h2 style="color: #2b50c0; font-size: 22px; margin-bottom: 20px;">HealCare Hospital</h2>
                
                <p>Hello,</p>
                
                <p>We received a request to reset your password. Use the following verification code to proceed:</p>
                
                <div style="background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 20px; text-align: left; width: fit-content; border-radius: 5px; margin: 20px 0;">
                    <span style="font-size: 32px; font-weight: bold; color: #2b50c0; letter-spacing: 1px;">' . $otp . '</span>
                </div>
                
                <p>If you didn\'t request this, please ignore this email.</p>
                
                <br>
                <div style="border-top: 1px solid #eee; padding-top: 10px;">
                    <p style="color: #999; font-size: 13px; margin: 0;">Sent by HealCare Hospital System</p>
                </div>
            </div>';

            $mail->send();
            echo "OTP_SENT"; // JS on login.php looks for this string
        } catch (Exception $e) {
            echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='login.php'</script>";
        }
        exit();

    } elseif ($action == 'verify_and_reset') {
        $otp = $_POST['otp'];
        $pass = $_POST['new_password'];
        $email = $_SESSION['reset_email'];

        if ($otp != $_SESSION['reset_otp']) {
            echo "<script>alert('Invalid OTP!'); window.location.href='login.php'</script>";
            exit();
        }

        if (time() > $_SESSION['otp_expiry']) {
            echo "<script>alert('OTP Expired!'); window.location.href='login.php'</script>";
            exit();
        }

        $new_hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Update Users Table
        $conn->query("UPDATE users SET password='$new_hash' WHERE email='$email'");
        
        // Update Registrations Table (to keep them in sync if needed)
        $conn->query("UPDATE registrations SET password='$new_hash' WHERE email='$email'");

        // Clear Session
        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['otp_expiry']);

        echo "<script>alert('Password reset successful! Please login.'); window.location.href='login.php'</script>";
        exit();

    } elseif ($action == 'signup') {
        // Patient Signup (Auto-Approve)
        $name = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password_raw = $_POST['password'];
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $role = 'patient';

        $check_user = $conn->query("SELECT * FROM users WHERE email='$email'");
        if ($check_user->num_rows > 0) {
            echo "<script>alert('This email is already registered.'); window.location.href='login.php'</script>";
            exit();
        }

        $conn->begin_transaction();
        try {
            $sql_reg = "INSERT INTO registrations (name, email, phone, password, user_type, status) 
                        VALUES ('$name', '$email', '$phone', '$password', '$role', 'Approved')";
            $conn->query($sql_reg);
            $registration_id = $conn->insert_id;
            
            // Generate Unique Patient ID
            $patient_code = "HC-P-" . date("Y") . "-" . rand(1000, 9999);
            $username = $patient_code; 

            $sql_user = "INSERT INTO users (registration_id, username, email, password, role, status) 
                         VALUES ('$registration_id', '$username', '$email', '$password', '$role', 'Active')";
            $conn->query($sql_user);
            $user_id = $conn->insert_id;

            // Create initial patient profile
            $conn->query("INSERT INTO patient_profiles (user_id, patient_code, name, phone) VALUES ($user_id, '$patient_code', '$name', '$phone')");

            $conn->commit();
            echo "<script>alert('Patient account created successfully! Your Patient ID is: $patient_code'); window.location.href='login.php'</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='signup.php'</script>";
        }

    } elseif ($action == 'apply') {
        // Create upload directories
        $resume_dir = 'uploads/resumes/';
        $photo_dir = 'uploads/photos/';
        if (!is_dir($resume_dir)) mkdir($resume_dir, 0777, true);
        if (!is_dir($photo_dir)) mkdir($photo_dir, 0777, true);

        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $staff_type = isset($_POST['staff_type']) ? mysqli_real_escape_string($conn, $_POST['staff_type']) : '';
        $name = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $h_qual = mysqli_real_escape_string($conn, $_POST['highest_qualification']);
        $t_exp = mysqli_real_escape_string($conn, $_POST['total_experience']);
        $shift = mysqli_real_escape_string($conn, $_POST['shift_preference']);
        $doj = mysqli_real_escape_string($conn, $_POST['date_of_joining']);

        // Role Specific
        $qual = isset($_POST['qualification']) ? mysqli_real_escape_string($conn, $_POST['qualification']) : '';
        $spec = isset($_POST['specialization']) ? mysqli_real_escape_string($conn, $_POST['specialization']) : '';
        $license = isset($_POST['license_number']) ? mysqli_real_escape_string($conn, $_POST['license_number']) : '';
        $dept = isset($_POST['dept_preference']) ? mysqli_real_escape_string($conn, $_POST['dept_preference']) : '';
        $designation = isset($_POST['designation']) ? mysqli_real_escape_string($conn, $_POST['designation']) : '';
        $langs = isset($_POST['languages_known']) ? mysqli_real_escape_string($conn, $_POST['languages_known']) : '';
        $fd_exp = isset($_POST['front_desk_exp']) ? mysqli_real_escape_string($conn, $_POST['front_desk_exp']) : '';
        $c_role = isset($_POST['canteen_job_role']) ? mysqli_real_escape_string($conn, $_POST['canteen_job_role']) : '';
        $rel_exp = isset($_POST['relevant_experience']) ? mysqli_real_escape_string($conn, $_POST['relevant_experience']) : '';
        $qual_details = isset($_POST['qualification_details']) ? mysqli_real_escape_string($conn, $_POST['qualification_details']) : '';

        // Generate Unique Application ID
        $app_id = "HC-APP-" . date("Y") . "-" . rand(1000, 9999);

        // Handle File Uploads
        $resume_path = '';
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $filename = time() . '_resume_' . basename($_FILES['resume']['name']);
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $resume_dir . $filename)) {
                $resume_path = $resume_dir . $filename;
            }
        }

        $photo_path = '';
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $filename = time() . '_photo_' . basename($_FILES['profile_photo']['name']);
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $photo_dir . $filename)) {
                $photo_path = $photo_dir . $filename;
            }
        }

        $check = $conn->query("SELECT * FROM registrations WHERE email='$email'");
        if ($check->num_rows > 0) {
            echo "<script>alert('Application already exists for this email.'); window.location.href='login.php'</script>";
            exit();
        }

        $sql = "INSERT INTO registrations 
                (app_id, name, email, phone, user_type, staff_type, status, address, profile_photo, highest_qualification, total_experience, resume_path, specialization, designation, license_number, dept_preference, languages_known, front_desk_exp, canteen_job_role, shift_preference, date_of_joining, relevant_experience, qualification_details) 
                VALUES 
                ('$app_id', '$name', '$email', '$phone', '$role', '$staff_type', 'Pending', '$address', '$photo_path', '$h_qual', '$t_exp', '$resume_path', '$spec', '$designation', '$license', '$dept', '$langs', '$fd_exp', '$c_role', '$shift', '$doj', '$rel_exp', '$qual_details')";
        
        if ($conn->query($sql)) {
            header("Location: pending_approval.php?email=" . urlencode($email) . "&app_id=" . urlencode($app_id));
        } else {
            echo "<script>alert('Error submitting application: " . $conn->error . "'); window.location.href='apply.php'</script>";
        }



    } elseif ($action == 'login') {
        $identity = mysqli_real_escape_string($conn, $_POST['identity']);
        $password = $_POST['password'];

        // 1. Admin Fixed Credentials
        if ($identity === 'admin@gmail.com' && $password === 'admin@Healcare') {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['username'] = 'admin';
            $_SESSION['full_name'] = 'Administrator';
            header("Location: admin_dashboard.php");
            exit();
        }

        // 2. Check Users Table (Approved)
        $res = $conn->query("SELECT * FROM users WHERE (username='$identity' OR email='$identity')");
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['user_id'];
                
                // Fetch Real Name from Registrations
                $reg_id = $user['registration_id'];
                $reg_q = $conn->query("SELECT name FROM registrations WHERE registration_id = $reg_id");
                if ($reg_q->num_rows > 0) {
                    $reg_data = $reg_q->fetch_assoc();
                    $_SESSION['full_name'] = $reg_data['name'];
                } else {
                    $_SESSION['full_name'] = $user['username']; // Fallback
                }
                
                // Check for redirect URL
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect_url = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $redirect_url);
                    exit();
                }

                // Check if password change is forced (Disabled as per request)
                /* if (isset($user['force_password_change']) && $user['force_password_change'] == 1) {
                    $_SESSION['force_change'] = true;
                    header("Location: change_password.php");
                    exit();
                } */

                // Redirect to specialized staff dashboards if role is staff
                if ($user['role'] == 'staff') {
                   // Get staff type from registrations
                   $reg_res = $conn->query("SELECT staff_type FROM registrations WHERE registration_id = " . ($user['registration_id'] ?? 0));
                   $reg = $reg_res->fetch_assoc();
                   $stype = $reg['staff_type'] ?? 'general';
                   header("Location: staff_dashboard.php?type=" . $stype);
                } else {
                   header("Location: " . $user['role'] . "_dashboard.php");
                }
                exit();
            }
        }

        // 3. Check Registrations Table (Pending/Rejected)
        // If they exist here but not in users table, they are still in the application pipeline
        $res_reg = $conn->query("SELECT * FROM registrations WHERE email='$identity'");
        if ($res_reg->num_rows > 0) {
            $reg = $res_reg->fetch_assoc();
            // Check if they are already in users table (if so, normal login would have caught it)
            $check_user = $conn->query("SELECT * FROM users WHERE email='$identity'");
            if ($check_user->num_rows == 0) {
                // Not a full user yet, must be Pending or Rejected
                header("Location: pending_approval.php?email=" . urlencode($reg['email']) . "&app_id=" . urlencode($reg['app_id']));
                exit();
            }
        }

        echo "<script>alert('Invalid credentials!'); window.location.href='login.php'</script>";

    } elseif ($action == 'google_login' || $action == 'google_signup') {
        // For Google, we treat them as patients by default unless handled otherwise
        // For this task, I'll keep it simple: Google users are patients
        $email = mysqli_real_escape_string($conn, strtolower($_POST['email']));
        $name = mysqli_real_escape_string($conn, $_POST['fullname']);
        
        $res = $conn->query("SELECT * FROM users WHERE email='$email'");
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $_SESSION['logged_in'] = true;
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['user_id'];
            
            // Fetch Name
            $reg_id = $user['registration_id'];
            $reg_q = $conn->query("SELECT name FROM registrations WHERE registration_id = $reg_id");
            if ($reg_q->num_rows > 0) {
                $reg_data = $reg_q->fetch_assoc();
                $_SESSION['full_name'] = $reg_data['name'];
            } else {
                $_SESSION['full_name'] = $name; // Fallback to Google Name
            }

            echo "<script>window.location.href='" . $user['role'] . "_dashboard.php';</script>";
        } else {
            // Auto-register as patient
            $conn->begin_transaction();
            try {
                $dummy_pass = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
                $rid = 0;
                
                // Check if registration already exists to prevent duplicate email error
                $check_reg = $conn->query("SELECT registration_id, name FROM registrations WHERE email='$email'");
                if ($check_reg->num_rows > 0) {
                    $reg_row = $check_reg->fetch_assoc();
                    $rid = $reg_row['registration_id'];
                    // Optional: Update name if needed, or just proceed
                } else {
                    $conn->query("INSERT INTO registrations (name, email, user_type, status, password) VALUES ('$name', '$email', 'patient', 'Approved', '$dummy_pass')");
                    $rid = $conn->insert_id;
                }

                // Generate a unique username to avoid conflicts
                $base_uname = strtolower(explode('@', $email)[0]);
                $uname = $base_uname;
                $counter = 1;
                while($conn->query("SELECT user_id FROM users WHERE username='$uname'")->num_rows > 0) {
                    $uname = $base_uname . $counter;
                    $counter++;
                }

                $conn->query("INSERT INTO users (registration_id, username, email, password, role, status) VALUES ($rid, '$uname', '$email', '$dummy_pass', 'patient', 'Active')");
                
                $new_user_id = $conn->insert_id;
                $conn->commit();
                
                $_SESSION['user_role'] = 'patient';
                $_SESSION['username'] = $uname;
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['full_name'] = $name;

                echo "<script>window.location.href='patient_dashboard.php';</script>";
            } catch (Exception $e) {
                $conn->rollback();
                $err_msg = json_encode("Error: " . $e->getMessage());
                echo "<script>alert($err_msg); window.location.href='login.php'</script>";
            }
        }
        exit();
    } elseif ($action == 'update_forced_password') {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['force_change'])) {
            header("Location: login.php");
            exit();
        }

        $new_pass = $_POST['new_password'];
        $conf_pass = $_POST['confirm_password'];

        if ($new_pass !== $conf_pass) {
            echo "<script>alert('Passwords do not match!'); window.location.href='change_password.php'</script>";
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['user_role'] ?? 'patient';
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

        // Update password and clear flag
        $conn->query("UPDATE users SET password='$hashed_pass', force_password_change=0 WHERE user_id=$user_id");
        
        // Remove force_change session
        unset($_SESSION['force_change']);

        // Redirect based on role
        if ($role == 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($role == 'staff') {
            header("Location: staff_dashboard.php");
        } else {
            header("Location: " . $role . "_dashboard.php");
        }
        exit();
    }
}
?>
