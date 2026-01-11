<?php
session_start();
include 'includes/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

$email = $_SESSION['pending_email'] ?? '';
$action = $_POST['action'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $otp = $_POST['otp_code'];
    
    // Validate OTP
    if (!isset($_SESSION['pending_app_otp']) || !isset($_SESSION['pending_app_data'])) {
        echo "<script>alert('Session expired. Please apply again.'); window.location.href='apply.php';</script>";
        exit();
    }
    
    if ($otp != $_SESSION['pending_app_otp']) {
        $error = "Invalid OTP. Please try again.";
    } elseif (time() > $_SESSION['pending_app_expiry']) {
        $error = "OTP Expired. Please apply again.";
    } else {
        // SUCCESS: Insert into Database
        $data = $_SESSION['pending_app_data'];
        
        $role = $data['role'];
        $staff_type = $data['staff_type'];
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $address = $data['address'];
        $h_qual = $data['h_qual'];
        $t_exp = $data['t_exp'];
        $shift = $data['shift'];
        $doj = $data['doj'];
        $qual = $data['qual'];
        $spec = $data['spec'];
        $license = $data['license'];
        $dept = $data['dept'];
        $designation = $data['designation'];
        $langs = $data['langs'];
        $fd_exp = $data['fd_exp'];
        $c_role = $data['c_role'];
        $rel_exp = $data['rel_exp'];
        $qual_details = $data['qual_details'];
        $app_id = $data['app_id'];
        $resume_path = $data['resume_path'];
        $photo_path = $data['photo_path'];

        $sql = "INSERT INTO registrations 
                (app_id, name, email, phone, user_type, staff_type, status, address, profile_photo, highest_qualification, total_experience, resume_path, specialization, designation, license_number, dept_preference, languages_known, front_desk_exp, canteen_job_role, shift_preference, date_of_joining, relevant_experience, qualification_details) 
                VALUES 
                ('$app_id', '$name', '$email', '$phone', '$role', '$staff_type', 'Pending', '$address', '$photo_path', '$h_qual', '$t_exp', '$resume_path', '$spec', '$designation', '$license', '$dept', '$langs', '$fd_exp', '$c_role', '$shift', '$doj', '$rel_exp', '$qual_details')";
        
        if ($conn->query($sql)) {
            // Clear session
            unset($_SESSION['pending_app_otp']);
            unset($_SESSION['pending_app_data']);
            unset($_SESSION['pending_app_expiry']);
            unset($_SESSION['pending_email']);
            
            header("Location: pending_approval.php?email=" . urlencode($email) . "&app_id=" . urlencode($app_id));
            exit();
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

// Resend Logic
if (isset($_POST['resend_otp'])) {
    // Regenerate and Resend
    if (!isset($_SESSION['pending_app_data'])) {
        header("Location: apply.php");
        exit();
    }
    
    $otp = rand(1000, 9999);
    $_SESSION['pending_app_otp'] = $otp;
    $_SESSION['pending_app_expiry'] = time() + 600;
    
    // Copied from auth_handler.php mail logic
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'healcare.mail.services@gmail.com';
        $mail->Password   = 'yiuwcrykatkfzdwv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->SMTPOptions = array('ssl' => array('verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true));
        
        $mail->setFrom('healcare.mail.services@gmail.com', 'HealCare HR');
        $mail->addAddress($email, $_SESSION['pending_app_data']['name']);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - HealCare Application';
        $mail->Body = 'Your verification code is: <b>' . $otp . '</b>';
        
        $mail->send();
        $resend_msg = "New code sent!";
    } catch (Exception $e) {
        $error = "Mail Error: " . $mail->ErrorInfo;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Application - HealCare</title>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .verify-container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #1e293b; margin-bottom: 10px; }
        p { color: #64748b; font-size: 0.9em; margin-bottom: 25px; }
        .otp-input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1.2rem; text-align: center; letter-spacing: 5px; margin-bottom: 20px; box-sizing: border-box; }
        .otp-input:focus { border-color: #3b82f6; outline: none; }
        .btn { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #2563eb; }
        .error { color: #ef4444; font-size: 0.9em; margin-bottom: 15px; }
        .success { color: #10b981; font-size: 0.9em; margin-bottom: 15px; }
        .resend { margin-top: 20px; font-size: 0.9em; }
        .resend button { background: none; border: none; color: #3b82f6; cursor: pointer; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="verify-container">
        <h2>Verify Email</h2>
        <p>Please enter the 4-digit code sent to <strong><?php echo htmlspecialchars($email); ?></strong> to complete your application.</p>
        
        <?php if (isset($error)) echo '<div class="error">' . $error . '</div>'; ?>
        <?php if (isset($resend_msg)) echo '<div class="success">' . $resend_msg . '</div>'; ?>

        <form method="POST">
            <input type="text" name="otp_code" class="otp-input" placeholder="0000" maxlength="4" required autofocus>
            <button type="submit" name="verify_otp" class="btn">Verify & Submit</button>
        </form>
        
        <div class="resend">
            <form method="POST">
                Didn't receive code? <button type="submit" name="resend_otp">Resend</button>
            </form>
        </div>
    </div>
</body>
</html>
