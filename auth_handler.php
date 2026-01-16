<?php
ob_start(); // Start output buffering to prevent header issues
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
        // Patient Signup - New Flow (OTP First)
        $name = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password_raw = $_POST['password'];
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $role = 'patient';

        // Check if email already exists in users or registrations
        $check_user = $conn->query("SELECT email FROM users WHERE email='$email'");
        $check_reg = $conn->query("SELECT email FROM registrations WHERE email='$email'");
        
        if ($check_user->num_rows > 0 || $check_reg->num_rows > 0) {
            echo "<script>alert('This email is already registered. Please login or use a different email.'); window.location.href='login.php'</script>";
            exit();
        }

        // Generate 4-Digit Verification OTP
        $otp_code = rand(1000, 9999);

        // Store Signup Data in Session
        $_SESSION['pending_signup'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => $role,
            'otp' => $otp_code,
            'expiry' => time() + 600 // 10 minutes
        ];

        // Send Verification Email with Code
        $mail = new PHPMailer(true);
        
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
            $mail->Password   = 'yiuwcrykatkfzdwv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            //Recipients
            $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare Hospital');
            $mail->addAddress($email, $name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - HealCare';
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 500px; padding: 30px; border: 1px solid #e2e8f0; border-radius: 12px; color: #1e293b;">
                <h2 style="color: #2b50c0; text-align: center;">Welcome to HealCare!</h2>
                <p>Hi ' . $name . ',</p>
                <p>Thank you for choosing HealCare. Please use the verification code below to complete your registration:</p>
                
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 20px; text-align: center; margin: 25px 0; border-radius: 8px;">
                    <span style="font-size: 32px; font-weight: 800; color: #3b82f6; letter-spacing: 12px;">' . $otp_code . '</span>
                </div>
                
                <p style="font-size: 0.9em; color: #64748b; text-align: center;">This code will expire in 10 minutes.</p>
                
                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 25px 0;">
                <p style="font-size: 0.8em; color: #94a3b8;">If you did not initiate this registration, please ignore this email.</p>
            </div>';

            $mail->send();

            echo "<script>
                alert('A 4-digit verification code has been sent to your email. Please verify to complete your account creation.');
                window.location.href = 'verify_code.php?email=" . urlencode($email) . "';
            </script>";
            exit();
        } catch (Exception $e) {
            echo "<script>alert('Error sending email: " . $mail->ErrorInfo . "'); window.location.href='signup.php'</script>";
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

        // Server-side Email Validation
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
             echo "<script>alert('Invalid Email Address! Please use a valid email.'); window.location.href='apply.php'</script>";
             exit();
        }

        // Domain Validation (MX Record Check)
        $domain = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($domain, "MX")) {
             echo "<script>alert('Invalid Email Domain! No mail server found for @$domain.'); window.location.href='apply.php'</script>";
             exit();
        }
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

        // Direct Insert - Skip Email Verification as per user request
        // Insert 'Pending' Application into Database
        $sql = "INSERT INTO registrations (
            user_type, staff_type, app_id, name, email, phone, 
            address, highest_qualification, total_experience, 
            shift_preference, date_of_joining, 
            specialization, designation, dept_preference, license_number, 
            languages_known, front_desk_exp, 
            canteen_job_role, qualification_details, 
            resume_path, profile_photo, 
            status, registered_date
        ) VALUES (
            '$role', '$staff_type', '$app_id', '$name', '$email', '$phone', 
            '$address', '$h_qual', '$t_exp', 
            '$shift', '$doj', 
            '$spec', '$designation', '$dept', '$license', 
            '$langs', '$fd_exp', 
            '$c_role', '$qual_details', 
            '$resume_path', '$photo_path', 
            'Pending', NOW()
        )";

        if ($conn->query($sql) === TRUE) {
             echo "<script>
                alert('Application Submitted Successfully! Your application ID is $app_id. Please wait for admin approval.');
                window.location.href = 'index.php'; // Or home page
             </script>";
        } else {
             echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit();



    } elseif ($action == 'login') {
        $identity = mysqli_real_escape_string($conn, $_POST['identity']);
        $password = $_POST['password'];

        // 1. Admin Fixed Credentials
        if ($identity === 'admin@gmail.com' && $password === 'admin@Healcare') {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['username'] = 'admin';
            $_SESSION['user_id'] = 0; // Fixed Admin ID
            $_SESSION['full_name'] = 'Administrator';
            header("Location: admin_dashboard.php");
            exit();
        }

        // 2. Check Users Table (Approved)
        $res = $conn->query("SELECT * FROM users WHERE (username='$identity' OR email='$identity')");
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($user['status'] == 'Unverified') {
                    echo "<script>alert('Please verify your account with the code sent to your email.'); window.location.href='verify_code.php?email=" . urlencode($user['email']) . "'</script>";
                    exit();
                }

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

            $_SESSION['logged_in'] = true;
            header("Location: " . $user['role'] . "_dashboard.php");
            exit();
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
                
                // Create patient profile
                $patient_code = "HC-P-" . date("Y") . "-" . rand(1000, 9999);
                $conn->query("INSERT INTO patient_profiles (user_id, patient_code, name, phone) VALUES ($new_user_id, '$patient_code', '$name', '')");
                
                $conn->commit();
                
                $_SESSION['logged_in'] = true;
                $_SESSION['user_role'] = 'patient';
                $_SESSION['username'] = $uname;
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['full_name'] = $name;

                header("Location: patient_dashboard.php");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='login.php';</script>";
                exit();
            }
        }
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
    } elseif ($action == 'resend_signup_otp') {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Check if there is a pending signup in session
        if (isset($_SESSION['pending_signup']) && $_SESSION['pending_signup']['email'] == $email) {
            $new_otp = rand(1000, 9999);
            $_SESSION['pending_signup']['otp'] = $new_otp;
            $_SESSION['pending_signup']['expiry'] = time() + 600;
            $name = $_SESSION['pending_signup']['name'];

            // Send Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
                $mail->Password   = 'yiuwcrykatkfzdwv';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->SMTPOptions = array('ssl' => array('verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true));

                $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare Hospital');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'New Verification Code - HealCare';
                $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 500px; padding: 30px; border: 1px solid #e2e8f0; border-radius: 12px; color: #1e293b;">
                    <h2 style="color: #2b50c0; text-align: center;">New Verification Code</h2>
                    <p>Hi ' . $name . ',</p>
                    <p>You requested a new verification code. Please use the code below to complete your registration:</p>
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 20px; text-align: center; margin: 25px 0; border-radius: 8px;">
                        <span style="font-size: 32px; font-weight: 800; color: #3b82f6; letter-spacing: 12px;">' . $new_otp . '</span>
                    </div>
                </div>';
                $mail->send();
                echo "OTP_SENT";
            } catch (Exception $e) {
                echo "ERROR: " . $mail->ErrorInfo;
            }
            exit();
        }

        // Fallback: Check Users Table (for old flow users)
        $res = $conn->query("SELECT * FROM users WHERE email='$email' AND status='Unverified'");
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $name = $user['username']; // Fallback
            
            // Fetch Real Name from Registrations
            $reg_id = $user['registration_id'];
            $reg_q = $conn->query("SELECT name FROM registrations WHERE registration_id = $reg_id");
            if ($reg_q->num_rows > 0) {
                $name = $reg_q->fetch_assoc()['name'];
            }

            $new_otp = rand(1000, 9999);
            $conn->query("UPDATE users SET verification_token='$new_otp' WHERE user_id=" . $user['user_id']);

            // Send Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
                $mail->Password   = 'yiuwcrykatkfzdwv';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->SMTPOptions = array('ssl' => array('verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true));

                $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare Hospital');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Account - HealCare';
                $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 500px; padding: 30px; border: 1px solid #e2e8f0; border-radius: 12px; color: #1e293b;">
                    <h2 style="color: #2b50c0; text-align: center;">New Verification Code</h2>
                    <p>Hi ' . $name . ',</p>
                    <p>You requested a new verification code. Please use the code below to activate your account:</p>
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 20px; text-align: center; margin: 25px 0; border-radius: 8px;">
                        <span style="font-size: 32px; font-weight: 800; color: #3b82f6; letter-spacing: 12px;">' . $new_otp . '</span>
                    </div>
                </div>';
                $mail->send();
                echo "OTP_SENT";
            } catch (Exception $e) {
                echo "ERROR: " . $mail->ErrorInfo;
            }
        } else {
            echo "EMAIL_NOT_FOUND";
        }
        exit();

    } elseif ($action == 'verify_signup_otp') {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $otp = mysqli_real_escape_string($conn, $_POST['otp']);

        // 1. Check for Pending Signup in Session
        if (isset($_SESSION['pending_signup']) && $_SESSION['pending_signup']['email'] == $email) {
            $data = $_SESSION['pending_signup'];
            
            if ($data['otp'] == $otp) {
                if (time() > $data['expiry']) {
                    echo "<script>alert('OTP Expired! Please try signing up again.'); window.location.href='signup.php'</script>";
                    exit();
                }

                // SUCCESS: Create account now
                $conn->begin_transaction();
                try {
                    $name = $data['name'];
                    $pass = $data['password'];
                    $phone = $data['phone'];
                    $role = $data['role'];

                    // 1. Create Registration Entry
                    $sql_reg = "INSERT INTO registrations (name, email, phone, password, user_type, status) 
                                VALUES ('$name', '$email', '$phone', '$pass', '$role', 'Approved')";
                    if (!$conn->query($sql_reg)) {
                        throw new Exception("Registration failed: " . $conn->error);
                    }
                    $registration_id = $conn->insert_id;
                    
                    // 2. Generate Unique Patient ID
                    $patient_code = "HC-P-" . date("Y") . "-" . rand(1000, 9999);
                    $username = $patient_code; 

                    // 3. Create User Entry with 'Active' status
                    $sql_user = "INSERT INTO users (registration_id, username, email, password, role, status) 
                                 VALUES ('$registration_id', '$username', '$email', '$pass', '$role', 'Active')";
                    $conn->query($sql_user);
                    $user_id = $conn->insert_id;

                    // 4. Create initial patient profile
                    $conn->query("INSERT INTO patient_profiles (user_id, patient_code, name, phone) VALUES ($user_id, '$patient_code', '$name', '$phone')");

                    $conn->commit();

                    // 5. Clear Pending Session
                    unset($_SESSION['pending_signup']);

                    $alert_msg = "Account verified and created successfully!\\nYour Unique Patient ID is: $username\\nPlease login to continue.";
                    $redirect_target = "login.php";
                    
                    if(isset($_SESSION['redirect_after_login'])) {
                         $redirect_target .= "?redirect=" . urlencode($_SESSION['redirect_after_login']);
                    }

                    echo "<script>
                        alert('$alert_msg'); 
                        window.location.href='$redirect_target';
                    </script>";
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "<script>alert('Database Error: " . addslashes($e->getMessage()) . "'); window.location.href='signup.php'</script>";
                    exit();
                }
            }
        }

        // 2. Fallback: Check Users Table (Old Flow)
        $res = $conn->query("SELECT * FROM users WHERE email='$email' AND verification_token='$otp' AND status='Unverified'");
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $user_id = $user['user_id'];
            
            $conn->query("UPDATE users SET status='Active', verification_token=NULL WHERE user_id=$user_id");
            
            // Auto Login for old flow too
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // Get Name
            $reg_id = $user['registration_id'];
            $reg_q = $conn->query("SELECT name FROM registrations WHERE registration_id = $reg_id");
            if ($reg_q->num_rows > 0) {
                $_SESSION['full_name'] = $reg_q->fetch_assoc()['name'];
            }

            echo "<script>alert('Account verified successfully! Redirecting to your dashboard...'); window.location.href='patient_dashboard.php'</script>";
        } else {
            echo "<script>alert('Invalid verification code. Please check your email and try again.'); window.location.href='verify_code.php?email=" . urlencode($email) . "'</script>";
        }
        exit();
    }
}
?>
