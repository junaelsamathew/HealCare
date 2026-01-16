<?php
session_start();
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HealCare</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="styles/main.css">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --navy-dark: #0f172a; /* Dark blue/navy */
            --text-light: #f8fafc;
            --error-red: #ef4444;
            --input-bg: rgba(255, 255, 255, 0.05);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Top Bar Header */
        .login-header {
            height: 80px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 10;
        }

        .logo-main {
            font-size: 24px;
            font-weight: 800;
            color: var(--navy-dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-info-group {
            display: flex;
            gap: 40px;
        }

        .header-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--navy-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--navy-dark);
        }

        .info-details {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .info-label {
            font-size: 10px;
            font-weight: 800;
            color: var(--navy-dark);
            text-transform: uppercase;
        }

        .info-value {
            font-size: 12px;
            color: var(--primary-blue);
            font-weight: 600;
        }

        /* Main Split Layout */
        .login-container {
            flex: 1;
            display: flex;
            width: 100%;
        }

        /* Left Section (50%) */
        .left-section {
            flex: 1;
            position: relative;
            background: #f1f5f9;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            overflow: hidden;
        }

        .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('images/bgimg.jpg'); /* Fallback image */
            background-size: cover;
            background-position: center;
            filter: blur(8px);
            opacity: 0.6;
            z-index: 0;
        }

        .left-content {
            position: relative;
            z-index: 2;
            max-width: 500px;
        }

        .left-content h1 {
            font-size: 4rem;
            font-weight: 800;
            color: var(--navy-dark);
            margin-bottom: 10px;
            line-height: 1.1;
        }

        .left-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--navy-dark);
            margin-bottom: 20px;
        }

        .left-content p {
            font-size: 1.1rem;
            color: #334155;
            line-height: 1.6;
            font-weight: 500;
        }

        /* Right Section (50%) */
        .right-section {
            flex: 1;
            background: var(--navy-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            text-align: center;
            color: #fff;
        }

        .login-card h3 {
            font-size: 2.5rem;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .login-card .subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-bottom: 35px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            margin-left: 10px; /* Align with rounded input start */
            color: #cbd5e1;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 14px 20px;
            border-radius: 30px; /* Fully rounded */
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s;
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-blue);
        }
        
        .form-input.invalid {
            border-color: var(--error-red);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 5px;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 30px;
            background: var(--primary-blue);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: #2563eb;
        }

        .form-links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 0.9rem;
            padding: 0 10px;
        }

        .form-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .form-links a:hover {
            color: #fff;
        }

        .divider {
            margin: 25px 0;
            position: relative;
            text-align: center;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .divider span {
            background: var(--navy-dark);
            padding: 0 15px;
            position: relative;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Google Button Custom Styling Wrap */
        .google-btn-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8; /* Muted text */
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 20px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .error-msg {
            color: var(--error-red);
            font-size: 0.85rem;
            margin-top: 5px;
            margin-left: 15px;
            display: none;
            text-align: left;
        }
        .error-msg.visible {
            display: block;
        }
        
        /* Message Group Toggle Logic */
        .message-group { display: none; }
        .message-group.active { display: block; }

        .auth-form { display: none; }
        .auth-form.active { display: block; }
        
        @media (max-width: 900px) {
            .left-section { display: none; }
        }
    </style>
     <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

    <!-- Header -->
    <header class="login-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <!-- Hidden on mobile usually, but requirement says "Header Requirements" -->
             <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-phone-alt"></i></div>
                <div class="info-details"><span class="info-label">EMERGENCY</span><span class="info-value">(+254) 717 783 146</span></div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-clock"></i></div>
                <div class="info-details"><span class="info-label">WORK HOUR</span><span class="info-value">09:00 - 20:00 Everyday</span></div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-details"><span class="info-label">LOCATION</span><span class="info-value">Kanjirapally, Kottayam</span></div>
            </div>
        </div>
    </header>

    <div class="login-container">
        <!-- Left Section -->
        <div class="left-section">
            <div class="bg-image"></div>
            <div class="left-content">
                <h1>Heal Care</h1>
                <div id="loginMessage" class="message-group active">
                    <h2>Welcome Back!</h2>
                    <p>Log in to access appointments, medical records, prescriptions, and personalize your healthcare experience.</p>
                </div>
                <div id="forgotMessage" class="message-group">
                    <h2>Reset Password</h2>
                    <p>Enter your verified email address to receive a secure code and reset your account password.</p>
                </div>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <div class="login-card">
                
                <!-- LOGIN FORM -->
                <form id="loginForm" class="auth-form active" action="auth_handler.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <h3>Login</h3>
                    <p class="subtitle">Welcome! Please login to your account</p>

                    <div class="form-group">
                        <!-- Label removed as placeholder serves well for modern UI, but request said "Input fields..." 
                             Usually labels are good for a11y. I'll include them small above. -->
                        <div class="input-wrapper">
                            <input type="text" name="identity" id="loginIdentity" class="form-input" placeholder="Username / Email" required>
                        </div>
                        <div class="error-msg" id="identityError"></div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="password" name="password" id="loginPassword" class="form-input" placeholder="Password" required>
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-msg" id="passwordError"></div>
                    </div>

                    <button type="submit" class="btn-login">Login</button>

                    <div class="form-links">
                        <a href="javascript:void(0)" class="toggle-auth" data-target="forgot">Forgot Password?</a>
                        <a href="signup.php">Sign Up</a>
                    </div>
                    
                    <div class="divider"><span>OR</span></div>

                    <div class="google-btn-container">
                        <div id="g_id_onload"
                             data-client_id="717092890700-fa055v1u37lthk6q6ao7jodl7c1jfrc9.apps.googleusercontent.com"
                             data-context="signin"
                             data-ux_mode="popup"
                             data-callback="handleCredentialResponse"
                             data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin" 
                             data-type="standard" 
                             data-shape="pill" 
                             data-theme="filled_blue" 
                             data-text="signin_with" 
                             data-size="large" 
                             data-logo_alignment="left"
                             data-width="380">
                        </div>
                    </div>

                    <a href="index.php" class="back-link">← Back to Home</a>
                </form>

                <!-- FORGOT PASSWORD FORM (Hidden) -->
                <form id="forgotForm" class="auth-form" action="auth_handler.php" method="POST">
                    <input type="hidden" name="action" value="request_otp">
                    <h3>Reset</h3>
                    <p class="subtitle">Enter your email to receive code</p>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="email" name="email" id="forgotEmail" class="form-input" placeholder="Registered Email" required>
                        </div>
                        <div class="error-msg" id="forgotEmailError"></div>
                    </div>

                    <button type="submit" class="btn-login">Send Code</button>

                    <div class="form-links" style="justify-content: center;">
                        <a href="javascript:void(0)" class="toggle-auth" data-target="login">Back to Login</a>
                    </div>
                </form>

                <!-- RESET FORM (Hidden) -->
                <form id="resetForm" class="auth-form" action="auth_handler.php" method="POST">
                    <input type="hidden" name="action" value="verify_and_reset">
                    <input type="hidden" name="email" id="resetEmailHidden">
                    
                    <h3>New Password</h3>
                    <p class="subtitle">Set your new secure password</p>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="text" name="otp" id="resetOtp" class="form-input" placeholder="6-digit Code" required maxlength="6">
                        </div>
                        <div class="error-msg" id="otpError"></div>
                    </div>

                    <div class="form-group">
                         <div class="input-wrapper">
                            <input type="password" name="new_password" id="resetNewPassword" class="form-input" placeholder="New Password" required>
                             <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="error-msg" id="newPasswordError"></div>
                    </div>
                    
                    <div class="form-group">
                         <div class="input-wrapper">
                            <input type="password" name="confirm_password" id="resetConfirmPassword" class="form-input" placeholder="Confirm Password" required>
                        </div>
                        <div class="error-msg" id="confirmPasswordError"></div>
                    </div>

                    <button type="submit" class="btn-login">Change Password</button>
                </form>

            </div>
        </div>
    </div>

    <!-- Include Logic Script -->
    <script src="js/login.js"></script>
</body>
</html>
