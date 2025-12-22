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

    <!-- Styles -->
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/login.css">
    <style>
        /* Specific overrides for the Login layout from the image */
        .login-visual {
            flex: 1.2;
            position: relative;
            padding: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
            border-right: 4px solid #3b82f6; /* Blue divider line */
        }
        .login-visual .visual-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('images/bgimg.jpg');
            background-size: cover;
            background-position: center;
            filter: blur(5px);
            opacity: 0.4;
            z-index: 1;
        }
        .login-visual .visual-content {
            position: relative;
            z-index: 2;
            text-align: left;
            max-width: 550px;
        }
        .message-group {
            display: none;
        }
        .message-group.active {
            display: block !important;
        }
        .login-visual h1 {
            font-size: 5rem;
            font-weight: 800;
            color: #0a192f;
            margin-bottom: 10px;
        }
        .login-visual h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0a192f;
            margin-bottom: 20px;
            display: block !important; /* Ensure visibility */
        }
        .login-visual p {
            font-size: 1.35rem;
            font-weight: 600;
            line-height: 1.4;
            color: #000;
        }

        .login-form-area {
            flex: 0.8;
            background-color: #051631; /* Darker navy from image */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }
        .form-container {
            width: 100%;
            max-width: 480px;
            text-align: center;
        }
        .auth-form h3 {
            font-size: 3.5rem;
            margin-bottom: 10px;
            font-weight: 800;
            color: #fff;
        }
        .auth-form .subtitle {
            text-align: center;
            opacity: 0.9;
            margin-bottom: 40px;
            font-size: 1rem;
            color: #fff;
        }
        .auth-form .subtitle span {
            font-weight: 700;
        }

        .input-row {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
        }
        .input-row label {
            flex: 0.4;
            font-size: 1.2rem;
            font-weight: 500;
            color: #fff;
            text-align: right;
        }
        .input-light {
            flex: 0.6;
            padding: 12px 15px;
            border-radius: 4px;
            border: none;
            font-size: 1rem;
            background: #fff;
            color: #000;
        }

        .btn-submit-new {
            width: 70%;
            margin: 30px auto 20px;
            display: block;
            background-color: #2b50c0;
            font-size: 1.8rem;
            padding: 12px;
            font-weight: 800;
            border-radius: 6px;
        }
        
        .login-footer-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-top: 15px;
            padding: 0 10px;
            color: #fff;
            font-size: 0.95rem;
        }
        .login-footer-links a {
            color: #fff;
            text-decoration: underline;
            font-weight: 500;
        }

        /* Validation Overrides */
        .input-light.invalid {
            border: 2px solid #ff3333 !important;
            background-color: #fff0f0 !important;
            box-shadow: 0 0 5px rgba(255, 51, 51, 0.3) !important;
        }
        .error-msg {
            color: #ff3333 !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            display: block !important;
            text-align: right !important;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .error-msg.visible {
            opacity: 1 !important;
        }
        .login-footer-links span.or-text {
            text-decoration: none;
            font-weight: 700;
        }

        .google-login-container {
            margin-top: 30px;
            display: flex;
            justify-content: center;
        }

        /* Styling the Google button wrapper to look like the dark one in image */
        .google-btn-wrapper {
            background: #1a1a1a;
            padding: 10px 40px;
            border-radius: 4px;
            border: 1px solid #444;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            width: fit-content;
            margin: 0 auto;
        }
        .google-btn-wrapper:hover {
            background: #222;
        }

        .back-home-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 25px;
            opacity: 0.8;
            transition: all 0.3s;
        }
        .back-home-pill:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }

        /* Password Toggle Styles (Inlined) */
        .password-relative-group {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }
        .password-relative-group input {
            width: 100% !important;
            padding-right: 40px !important;
        }
        .toggle-eye-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            background: transparent;
            border: none;
            padding: 0;
        }
        .toggle-eye-icon:hover {
            color: #374151;
        }
        .toggle-eye-icon svg {
            width: 20px;
            height: 20px;
        }
    </style>

    <!-- Google Identity Services SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <!-- Google Auth Configuration -->
    <div id="g_id_onload"
         data-client_id="717092890700-fa055v1u37lthk6q6ao7jodl7c1jfrc9.apps.googleusercontent.com"
         data-context="signin"
         data-ux_mode="popup"
         data-callback="handleCredentialResponse"
         data-auto_prompt="false">
    </div>

    <!-- Header Section -->
    <header class="login-header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php">HEALCARE</a>
            </div>
            <div class="header-info">
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div class="info-text">
                        <span class="label">EMERGENCY</span>
                        <span class="value">(+254) 717 783 146</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="info-text">
                        <span class="label">WORK HOUR</span>
                        <span class="value">09:00 - 20:00 Everyday</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="info-text">
                        <span class="label">LOCATION</span>
                        <span class="value">Kanjirapally,Kottayam</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="login-main">
        <!-- Left Section -->
        <section class="login-visual">
            <div class="visual-content">
                <h1>Heal Care</h1>
                <div id="loginMessage" class="message-group active">
                    <h2>Welcome Back!</h2>
                    <p>Your health, our mission. Log in to access appointments, medical records, prescriptions, and personalized care services at Heal Care.</p>
                </div>
                <div id="forgotMessage" class="message-group">
                    <h2>Reset Password</h2>
                    <p>Don't worry! Enter your registered email address to verify your account and set a new password.</p>
                </div>
            </div>
            <div class="visual-bg"></div>
        </section>

        <!-- Right Section -->
        <section class="login-form-area">
            <div class="form-container">
                <form id="loginForm" class="auth-form active" action="auth_handler.php" method="POST" novalidate>
                    <input type="hidden" name="action" value="login">
                    
                    <h3>Login</h3>
                    <p class="subtitle">Welcome! Please login to <span>your account</span></p>


                    <div class="input-row">
                        <label>Username / Email</label>
                        <input type="text" name="identity" id="loginIdentity" class="input-light" placeholder="Username or Email" required>
                    </div>
                    <span class="error-msg" id="identityError"></span>

                    <div class="input-row">
                        <label>Password</label>
                        <div class="password-relative-group" style="flex: 0.6;">
                            <input type="password" name="password" id="loginPassword" class="input-light" style="width: 100%; flex: 1;" placeholder="••••••••••••" required>
                            <button type="button" class="toggle-eye-icon" onclick="togglePasswordVisibility(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <span class="error-msg" id="passwordError"></span>

                    <button type="submit" class="btn-submit-new">Login</button>

                    <div class="login-footer-links">
                        <a href="javascript:void(0)" class="toggle-auth" data-target="forgot">Forgot Password?</a>
                        <span class="or-text">Or</span>
                        <a href="signup.php">Sign Up</a>
                    </div>
                    
                    <div class="google-login-container">
                         <div class="g_id_signin" 
                             data-type="standard" 
                             data-shape="rectangular" 
                             data-theme="outline" 
                             data-text="signin_with" 
                             data-size="large" 
                             data-logo_alignment="center" 
                             data-width="320">
                        </div>
                    </div>
                    <a href="index.php" class="back-home-pill">← Back to Home</a>
                </form>

                <!-- Forgot Password Step 1 (Hidden by default but uses same visual style) -->
                <form id="forgotForm" class="auth-form" action="auth_handler.php" method="POST" novalidate>
                    <input type="hidden" name="action" value="request_otp">
                    <h3>Reset</h3>
                    <p class="subtitle">Enter your <span>email</span> to reset</p>
                    <div class="input-row">
                        <label>Email</label>
                        <input type="email" name="email" id="forgotEmail" class="input-light" placeholder="Email" required>
                    </div>
                    <span class="error-msg" id="forgotEmailError"></span>
                    <button type="submit" class="btn-submit-new" style="font-size: 1.2rem;">Send Code</button>
                    <div class="login-footer-links">
                        <a href="javascript:void(0)" class="toggle-auth" data-target="login">Back to Login</a>
                    </div>
                </form>

                <!-- Forgot Password Step 2: Verify and Reset (Hidden by default) -->
                <form id="resetForm" class="auth-form" action="auth_handler.php" method="POST" novalidate>
                    <input type="hidden" name="action" value="verify_and_reset">
                    <input type="hidden" name="email" id="resetEmailHidden">
                    
                    <h3>New Password</h3>
                    <p class="subtitle">Enter code & set <span>new password</span></p>

                    <div class="input-row">
                        <label>Code</label>
                        <input type="text" name="otp" class="input-light" placeholder="6-digit code" required maxlength="6">
                    </div>

                    <div class="input-row">
                        <label>New Pass</label>
                         <div class="password-relative-group" style="flex: 0.6;">
                            <input type="password" name="new_password" id="resetPassword" class="input-light" style="width: 100%; flex: 1;" placeholder="New Password" required>
                             <button type="button" class="toggle-eye-icon" onclick="togglePasswordVisibility(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 5px; margin-bottom: 20px; width: 100%;">
                        <span class="suggest-pass-link" id="resetSuggestBtn" style="color: #4fc3f7; cursor: pointer; text-decoration: underline; font-size: 0.82rem; font-weight: 500; white-space: nowrap;">Suggest Strong Password</span>
                    </div>
                    <span class="error-msg" id="resetPasswordError"></span>

                    <div class="input-row">
                        <label>Confirm</label>
                        <div class="password-relative-group" style="flex: 0.6;">
                            <input type="password" name="confirm_password" id="resetConfirmPassword" class="input-light" style="width: 100%; flex: 1;" placeholder="Confirm Password" required>
                             <button type="button" class="toggle-eye-icon" onclick="togglePasswordVisibility(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <span class="error-msg" id="resetConfirmPasswordError"></span>

                    <button type="submit" class="btn-submit-new" style="font-size: 1.2rem;">Reset Password</button>

                    <div class="login-footer-links">
                        <a href="javascript:void(0)" class="toggle-auth" data-target="forgot">Back to Step 1</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script src="js/login.js?v=1.3"></script>
    <script>
        // Inline function to ensure it loads and works immediately
        function togglePasswordVisibility(btn) {
            const wrapper = btn.closest('.password-relative-group');
            const input = wrapper.querySelector('input');
            if (!input) return;

            const isPassword = input.type === 'password';
            
            // Toggle Type
            input.type = isPassword ? 'text' : 'password';

            // Toggle Icon
            if (isPassword) {
                // Show 'Eye Off' (Slash)
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>`;
            } else {
                // Show 'Eye'
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>`;
            }
        }
    </script>
</body>
</html>
