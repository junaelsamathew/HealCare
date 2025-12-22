<?php
session_start();
include 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'signup') {
        $name = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);

        // --- Server-Side Strict Validation ---
        // Ensure no leading spaces or symbols for Name, Email, Phone
        if (!preg_match('/^[a-zA-Z0-9]/', $name) || !preg_match('/^[a-zA-Z0-9]/', $email) || !preg_match('/^[a-zA-Z0-9]/', $phone)) {
            echo "<script>alert('Validation Error: Fields must start with a letter or number (no spaces or symbols).'); window.location.href='signup.php'</script>";
            exit();
        }

        // Strict Phone Length Check (Server Side)
        if (strlen($phone) !== 10 || !ctype_digit($phone)) {
             echo "<script>alert('Validation Error: Phone number must be exactly 10 digits.'); window.location.href='signup.php'</script>";
             exit();
        }

        // Ensure Password also starts with alphanumeric (check original POST, not escaped)
        $password_raw = $_POST['password'];
        if (!preg_match('/^[a-zA-Z0-9]/', $password_raw)) {
             echo "<script>alert('Validation Error: Password must start with a letter or number.'); window.location.href='signup.php'</script>";
             exit();
        }
        if (strlen($password_raw) < 8) {
             echo "<script>alert('Validation Error: Password must be at least 8 characters long.'); window.location.href='signup.php'</script>";
             exit();
        }

        // Handle missing username field by generating one from email
        if (isset($_POST['username']) && !empty($_POST['username'])) {
            $username = mysqli_real_escape_string($conn, $_POST['username']);
        } else {
            // Generate unique username: email_prefix + random_digits
            $username = mysqli_real_escape_string($conn, strtolower(explode('@', $_POST['email'])[0]) . rand(1000, 9999));
        }
        $password_raw = $_POST['password'];
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $user_type = $_POST['role'];
        
        // Check if user already exists in the main 'users' table
        $check_user = $conn->query("SELECT * FROM users WHERE email='$email'");
        if ($check_user->num_rows > 0) {
            $existing_user = $check_user->fetch_assoc();
            $existing_role = $existing_user['role']; 
            echo "<script>alert('This email is already registered as " . $existing_role . "'); window.location.href='login.php'</script>";
            exit();
        }

        // Check for orphaned registration (exists in registrations but not users)
        $check_reg = $conn->query("SELECT * FROM registrations WHERE email='$email'");
        if ($check_reg->num_rows > 0) {
            // It exists in registrations. Since we already checked 'users' and it wasn't there, this is a failed previous attempt.
            // We will delete it and allow the new signup to proceed.
            $conn->query("DELETE FROM registrations WHERE email='$email'");
        }

        // Use Transaction for Atomicity
        $conn->begin_transaction();

        try {
            // 1. Insert into registrations
            $sql_reg = "INSERT INTO registrations (name, email, phone, password, user_type, status) 
                        VALUES ('$name', '$email', '$phone', '$password', '$user_type', 'Approved')";
            
            if (!$conn->query($sql_reg)) {
                throw new Exception("Error registering: " . $conn->error);
            }
            
            $registration_id = $conn->insert_id;
            
            // 2. Insert into users (Base Entity)
            $sql_user = "INSERT INTO users (registration_id, username, email, password, role, status) 
                         VALUES ('$registration_id', '$username', '$email', '$password', '$user_type', 'Active')";
            
            if (!$conn->query($sql_user)) {
                throw new Exception("Error creating profile: " . $conn->error);
            }

            // Commit transaction
            $conn->commit();
            echo "<script>alert('Account created successfully! You can now login.'); window.location.href='login.php'</script>";

        } catch (Exception $e) {
            // Rollback on any error
            $conn->rollback();
            echo "<script>alert('" . $e->getMessage() . "'); window.location.href='signup.php'</script>";
        }

    } elseif ($action == 'login') {
        $identity = mysqli_real_escape_string($conn, $_POST['identity']);
        $password = $_POST['password'];

        // Search in users table by username OR email
        $res = $conn->query("SELECT * FROM users WHERE (username='$identity' OR email='$identity')");
        
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            if ($user['status'] !== 'Active') {
                echo "<script>alert('Your account is " . $user['status'] . "'); window.location.href='login.php'</script>";
                exit();
            }

            if (password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['user_id'];
                
                // Update last login
                $conn->query("UPDATE users SET last_login = NOW() WHERE user_id = " . $user['user_id']);

                // Ensure role is lowercase for filename
                $role = strtolower($user['role']);
                header("Location: " . $role . "_dashboard.html");
                exit();
            } else {
                echo "<script>alert('Wrong password!'); window.location.href='login.php'</script>";
            }
        } else {
            echo "<script>alert('User not found!'); window.location.href='login.php'</script>";
        }

    } elseif ($action == 'google_signup') {
        $email = mysqli_real_escape_string($conn, strtolower($_POST['email']));
        $name = mysqli_real_escape_string($conn, $_POST['fullname']);
        $role_raw = mysqli_real_escape_string($conn, $_POST['role']);
        $role = strtolower($role_raw); // Force lowercase for consistency
        
        // 1. Check if user already exists
        $check = $conn->query("SELECT * FROM users WHERE LOWER(email)=LOWER('$email')");
        if ($check->num_rows > 0) {
            echo "<script>alert('Account is already registered. Please login to continue.'); window.location.href='login.php';</script>";
            exit();
        }

        // 2. Create new account
        $username = strtolower(explode('@', $email)[0]);
        // Handle random password for Google users as they won't use it
        $dummy_password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);

        // Transaction start
        $conn->begin_transaction();

        try {
            // Insert into registrations
            $stmt1 = $conn->prepare("INSERT INTO registrations (name, email, user_type, status, password) VALUES (?, ?, ?, 'Approved', ?)");
            $stmt1->bind_param("ssss", $name, $email, $role, $dummy_password);
            $stmt1->execute();
            $reg_id = $conn->insert_id;

            // Insert into users
            $stmt2 = $conn->prepare("INSERT INTO users (registration_id, username, email, role, status, password) VALUES (?, ?, ?, ?, 'Active', ?)");
            $stmt2->bind_param("issss", $reg_id, $username, $email, $role, $dummy_password);
            $stmt2->execute();

            $conn->commit();
            
            // 3. User created successfully - Show message and redirect to Login
            echo "<script>alert('Account created successfully, please login in'); window.location.href='login.php';</script>";
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error creating account: " . $e->getMessage() . "'); window.location.href='login.php';</script>";
        }

    } elseif ($action == 'google_login') {
        $email = mysqli_real_escape_string($conn, strtolower($_POST['email']));
        
        // 1. Check if user exists
        $res = $conn->query("SELECT * FROM users WHERE LOWER(email)=LOWER('$email')");
        
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            // 2. Check status
            if ($user['status'] !== 'Active') {
                echo "<script>alert('Your account is " . $user['status'] . "'); window.location.href='login.php'</script>";
                exit();
            }

            // 3. Log them in
            $_SESSION['logged_in'] = true;
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['user_id'];
            
            $conn->query("UPDATE users SET last_login = NOW() WHERE user_id = " . $user['user_id']);
            
            // 4. Redirect via Script
            $role = strtolower($user['role']);
            echo "<script>window.location.href='" . $role . "_dashboard.html';</script>";
            exit();
        } else {
            // User does not exist
            echo "<script>alert('Account not found! Please sign up first.'); window.location.href='login.php';</script>";
            exit();
        }
    } elseif ($action == 'request_otp') {
        $email = mysqli_real_escape_string($conn, strtolower($_POST['email']));
        
        // 1. Check if user exists
        $check = $conn->query("SELECT * FROM users WHERE LOWER(email)=LOWER('$email')");
        
        if ($check->num_rows > 0) {
            // 2. Generate a random 6-digit OTP
            $otp = rand(100000, 999999);
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_email'] = $email;
            
            // 3. Send the actual email
            if (sendOTPEmail($email, $otp)) {
                echo "OTP_SENT"; 
            } else {
                echo "<script>alert('Failed to send email. please check your internet or mail settings.');</script>";
            }
            exit();
        } else {
            echo "<script>alert('Email not found in our records!'); window.location.href='login.php'</script>";
            exit();
        }

    } elseif ($action == 'verify_and_reset') {
        $email = mysqli_real_escape_string($conn, strtolower($_POST['email']));
        $otp_entered = trim($_POST['otp']);
        $new_password_raw = $_POST['new_password'];
        $confirm_password_raw = $_POST['confirm_password'];

        // 1. Basic validation
        if ($new_password_raw !== $confirm_password_raw) {
            echo "<script>alert('Passwords do not match!'); window.location.href='login.php'</script>";
            exit();
        }

        if (!isset($_SESSION['reset_otp']) || $_SESSION['reset_otp'] != $otp_entered || $_SESSION['reset_email'] != $email) {
            echo "<script>alert('Invalid or expired verification code!'); window.location.href='login.php'</script>";
            exit();
        }

        // 2. Success - Update password
        $new_password = password_hash($new_password_raw, PASSWORD_DEFAULT);
        
        // Update in both tables
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE users SET password='$new_password' WHERE LOWER(email)=LOWER('$email')");
            $conn->query("UPDATE registrations SET password='$new_password' WHERE LOWER(email)=LOWER('$email')");
            $conn->commit();
            
            // Clear reset session
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_email']);
            
            echo "<script>alert('Password reset successful! You can now login.'); window.location.href='login.php'</script>";
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error updating password: " . $e->getMessage() . "'); window.location.href='login.php'</script>";
            exit();
        }
    }
}

/**
 * Helper function to send OTP email using PHPMailer
 */
function sendOTPEmail($toEmail, $otp) {
    // You need to have PHPMailer files in a folder named 'PHPMailer' inside your project
    // Download from: https://github.com/PHPMailer/PHPMailer
    
    require_once 'libs/PHPMailer/PHPMailer.php';
    require_once 'libs/PHPMailer/SMTP.php';
    require_once 'libs/PHPMailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
        $mail->Password   = 'xtjkyhatquzmhjzq';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare Hospital');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Verification Code - HealCare';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee;'>
                <h2 style='color: #1a73e8;'>HealCare Hospital</h2>
                <p>Hello,</p>
                <p>We received a request to reset your password. Use the following verification code to proceed:</p>
                <div style='font-size: 24px; font-weight: bold; color: #1a73e8; padding: 10px; background: #f8f9fa; display: inline-block;'>$otp</div>
                <p>If you didn't request this, please ignore this email.</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #777;'>Sent by HealCare Hospital System</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error if needed: error_log($mail->ErrorInfo);
        return false;
    }
}
?>
