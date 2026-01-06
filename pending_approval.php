<?php
session_start();
if (!isset($_GET['email'])) {
    header("Location: login.php");
    exit();
}
include 'includes/db_connect.php';

$email = mysqli_real_escape_string($conn, trim($_GET['email'] ?? ''));
$app_id_get = mysqli_real_escape_string($conn, trim($_GET['app_id'] ?? ''));

// Check current status
if (!empty($app_id_get)) {
    $res = $conn->query("SELECT status, user_type, app_id FROM registrations WHERE email='$email' AND app_id='$app_id_get'");
} else {
    // Fallback for direct redirects that might only pass email
    $res = $conn->query("SELECT status, user_type, app_id FROM registrations WHERE email='$email' AND user_type != 'patient'");
}

if ($res->num_rows == 0) {
    echo "<script>alert('No application found matching that Email and Application ID. Please check your credentials.'); window.location.href='check_status.php';</script>";
    exit();
}

$reg = $res->fetch_assoc();
$status = $reg['status'] ?? 'Pending';
$role = $reg['user_type'] ?? 'User';
$app_id = $reg['app_id'] ?? 'N/A';

if ($status == 'Approved') {
    $user_res = $conn->query("SELECT email, username FROM users WHERE email='$email'");
    $user_data = $user_res->fetch_assoc();
    $generated_id = $user_data['username'] ?? 'Not assigned yet';

    $message = "Your application has been approved!";
    $subtext = "Welcome to the team! Your record matches our professional standards. Please use your unique Login ID below to access the system.";
    $show_login = true;
    $credentials = [
        'Login ID' => $generated_id,
        'Temporary Password' => 'HealCare123'
    ];
} elseif ($status == 'Rejected') {
    $message = "Application Status: Rejected";
    $subtext = "Unfortunately, your application was not approved at this time. Our standards for Medical and Staff roles require specific criteria that were not met.";
    $show_login = false;
} else {
    $message = "Application Successfully Received";
    $subtext = "Thank you for applying to HealCare. Your professional profile is now under review. <br><br> <strong>IMPORTANT:</strong> Save your Application ID below to check your status later.";
    $show_login = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/login.css">
    <style>
        .status-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a192f;
            color: #fff;
            text-align: center;
            padding: 20px;
        }
        .status-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 60px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 600px;
            width: 100%;
        }
        .status-icon {
            width: 100px;
            height: 100px;
            background: #1e40af;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .status-icon svg {
            width: 50px;
            height: 50px;
            color: #fff;
        }
        h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 800;
        }
        p {
            font-size: 1.1rem;
            opacity: 0.8;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        .btn-status {
            display: inline-block;
            background: #1e40af;
            color: #fff;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
        }
        .btn-status:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="status-container">
        <div class="status-card">
            <div class="status-icon">
                <?php if ($status == 'Approved'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php elseif ($status == 'Rejected'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <?php endif; ?>
            </div>
            <h2><?php echo $message; ?></h2>
            <p><?php echo $subtext; ?></p>

            <div class="app-info-section" style="margin-bottom: 30px;">
                <div class="app-id-badge" style="background: rgba(255, 255, 255, 0.1); padding: 15px 30px; border-radius: 12px; display: inline-block; border: 1px solid rgba(255, 255, 255, 0.2);">
                    <span style="opacity: 0.6; font-size: 0.75rem; display: block; margin-bottom: 5px; letter-spacing: 1px;">REFERENCE APPLICATION ID</span>
                    <strong style="font-size: 1.4rem; color: #4fc3f7; font-family: monospace;"><?php echo $app_id; ?></strong>
                </div>
            </div>

            <?php if ($status == 'Approved' && isset($credentials)): ?>
                <div class="credentials-box" style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; padding: 25px; margin-bottom: 30px; text-align: left;">
                    <h4 style="margin-top:0; color: #10b981; margin-bottom: 15px;">Your Access Credentials</h4>
                    <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                        <span style="opacity: 0.7;">Login ID:</span>
                        <strong style="color: #fff; font-size: 0.95rem;"><?php echo $credentials['Login ID']; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="opacity: 0.7;">Temporary Password:</span>
                        <strong style="color: #fff; font-size: 0.95rem;"><?php echo $credentials['Temporary Password']; ?></strong>
                    </div>
                    <div style="border-top: 1px solid rgba(16, 185, 129, 0.2); pt-15; margin-top: 15px; pt: 15px; padding-top: 15px;">
                        <span style="display: block; font-size: 0.85rem; opacity: 0.8; margin-bottom: 5px;">Login URL:</span>
                        <code style="background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 4px; font-size: 0.9rem; color: #4fc3f7; display: block; overflow-x: auto;">http://localhost/HealCare/login.php</code>
                    </div>
                    <small style="display: block; margin-top: 15px; text-align: center; color: #fbbf24; font-weight: 600;">* Required: You must change this temporary password on your first login.</small>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <?php if ($show_login): ?>
                    <a href="login.php" class="btn-status" style="background: #10b981;">Proceed to Login</a>
                <?php else: ?>
                    <a href="index.php" class="btn-status">Back to Homepage</a>
                    <a href="check_status.php" class="btn-status" style="background: transparent; border: 1px solid #fff; margin-left: 10px;">Check Another</a>
                <?php endif; ?>
            </div>


        </div>
    </div>
</body>
</html>
