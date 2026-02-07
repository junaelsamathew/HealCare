<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HealCare</title>
    
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
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
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
            padding: 40px 0; /* Add vertical padding for scrolling clearance */
        }

        .login-card {
            width: 100%;
            max-width: 440px; /* Slightly wider for signup fields if needed */
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
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 15px; /* Tighter spacing for more fields */
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.85rem;
            margin-left: 10px;
            color: #cbd5e1;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 14px 20px;
            border-radius: 30px; /* Pillow shape like Login */
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 0.95rem;
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
            z-index: 2;
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
            margin-top: 15px;
            transition: background 0.3s;
        }

        .btn-login:disabled {
            background: #64748b;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .btn-login:hover:not(:disabled) {
            background: #2563eb;
        }

        .form-links {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            font-size: 0.9rem;
            padding: 0 10px;
        }

        .form-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .form-links strong {
            color: var(--primary-blue);
        }

        .form-links a:hover {
            color: #fff;
        }
        
        .divider {
            margin: 20px 0;
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
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Google Button Custom Styling Wrap */
        .google-btn-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .apply-btn {
            color: #10b981 !important; /* Green color for staff apply */
            font-weight: 600;
            font-size: 0.85rem;
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
            margin-top: 10px;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .error-msg {
            color: var(--error-red);
            font-size: 0.75rem;
            margin-top: 4px;
            margin-left: 15px;
            display: none;
            text-align: left;
        }
        .error-msg.visible {
            display: block;
        }


        @media (max-width: 900px) {
            .left-section { display: none; }
            .login-card { padding: 15px; }
        }
    </style>
     <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

    <!-- Header -->
    <header class="login-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
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
                <p>Create your Heal Care account to manage appointments, view medical records, and receive personalized healthcare services.</p>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <div class="login-card">
                
                <h3>Sign Up</h3>
                <p class="subtitle">Welcome! Please Sign Up to <strong>your account</strong></p>

                <form id="signupForm" action="auth_handler.php" method="POST">
                    <input type="hidden" name="action" value="signup">
                    <input type="hidden" name="role" value="patient">

                    <div class="form-group">
                        <label>Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" name="fullname" id="fullname" class="form-input" placeholder="Enter Full Name" required>
                        </div>
                        <div class="error-msg" id="nameError">Name is required</div>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" id="email" class="form-input" placeholder="Enter Email Address" required>
                        </div>
                        <div class="error-msg" id="emailError">Invalid email format</div>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone_number" id="phone" class="form-input" placeholder="Enter 10-digit Phone" required pattern="[0-9]{10}" maxlength="10">
                        </div>
                        <div class="error-msg" id="phoneError">Must be exactly 10 digits</div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" class="form-input" placeholder="Create Password" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="password-tools" style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px; padding: 0 10px;">
                            <div class="strength-meter" style="flex: 1; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; margin-right: 10px;">
                                <div id="strengthBar" style="width: 0%; height: 100%; background: #ef4444; transition: all 0.3s;"></div>
                            </div>
                            <span id="strengthText" style="font-size: 10px; color: #94a3b8; min-width: 50px;">Weak</span>
                            <a href="javascript:void(0)" onclick="suggestPassword()" style="font-size: 11px; color: #3b82f6; text-decoration: none; margin-left: 10px; font-weight: 600;">Suggest Strong Password</a>
                        </div>
                        <div class="error-msg" id="passError">Minimum 6 characters</div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" id="cpassword" class="form-input" placeholder="Confirm Password" required>
                            <button type="button" class="toggle-password" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="error-msg" id="cpassError">Passwords do not match</div>
                    </div>

                    <button type="submit" id="submitBtn" class="btn-login" disabled>Create Account</button>

                     <div class="divider"><span>OR</span></div>

                    <div class="google-btn-container">
                        <div id="g_id_onload"
                             data-client_id="717092890700-fa055v1u37lthk6q6ao7jodl7c1jfrc9.apps.googleusercontent.com"
                             data-context="signup"
                             data-ux_mode="popup"
                             data-callback="handleCredentialResponse"
                             data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin" 
                             data-type="standard" 
                             data-shape="pill" 
                             data-theme="filled_blue" 
                             data-text="signup_with" 
                             data-size="large" 
                             data-logo_alignment="left"
                             data-width="380">
                        </div>
                    </div>

                    <div class="form-links">
                        <a href="login.php">Already have an account? <strong>Login</strong></a>
                        <a href="apply.php" class="apply-btn">Are you a Doctor or Staff? Apply Now</a>
                    </div>
                    
                    <a href="index.php" class="back-link">← Back to Home</a>
                </form>

            </div>
        </div>
    </div>

    <!-- Reusing existing script logic but custom inline validation below -->
    <script src="js/login.js"></script> 
    <script>
        function togglePass(btn) {
            const input = btn.previousElementSibling;
            if (input.type === "password") {
                input.type = "text";
                btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = "password";
                btn.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }

        // Live Inline Validation
        const inputs = {
            fullname: document.getElementById('fullname'),
            email: document.getElementById('email'),
            phone: document.getElementById('phone'),
            pass: document.getElementById('password'),
            cpass: document.getElementById('cpassword')
        };
        const errors = {
            name: document.getElementById('nameError'),
            email: document.getElementById('emailError'),
            phone: document.getElementById('phoneError'),
            pass: document.getElementById('passError'),
            cpass: document.getElementById('cpassError')
        };
        const submitBtn = document.getElementById('submitBtn');

        function validateForm() {
            let valid = true;

            // 1. Full Name: Min 3 chars, Alphabets only
            const nameRegex = /^[a-zA-Z\s]{3,}$/;
            if(nameRegex.test(inputs.fullname.value.trim())) {
                errors.name.classList.remove('visible');
                inputs.fullname.classList.remove('invalid');
                errors.name.textContent = "";
            } else {
                if(inputs.fullname.value.length > 0) {
                     errors.name.textContent = "Please enter a valid full name (min 3 chars)";
                     errors.name.classList.add('visible');
                     inputs.fullname.classList.add('invalid');
                }
                valid = false;
            }

            // 2. Email Verification
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if(emailRegex.test(inputs.email.value.trim())) {
                 errors.email.classList.remove('visible');
                 inputs.email.classList.remove('invalid');
            } else {
                 if(inputs.email.value.length > 0) {
                    errors.email.textContent = "Please enter a valid email address";
                    errors.email.classList.add('visible');
                    inputs.email.classList.add('invalid');
                 }
                 valid = false;
            }

            // 3. Phone: Exactly 10 digits, numeric
            const phoneRegex = /^[0-9]{10}$/;
            if(phoneRegex.test(inputs.phone.value.trim())) {
                errors.phone.classList.remove('visible');
                inputs.phone.classList.remove('invalid');
            } else {
                 if(inputs.phone.value.length > 0) {
                    errors.phone.textContent = "Enter a valid 10-digit phone number";
                    errors.phone.classList.add('visible');
                    inputs.phone.classList.add('invalid');
                 }
                 valid = false;
            }

            // 4. Password Strict: Min 8, AZ, az, 09, special
            const passVal = inputs.pass.value;
            // Relaxed Regex: Allows any special character (non-alphanumeric)
            const strictPassRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
            
            if(strictPassRegex.test(passVal)) {
                errors.pass.classList.remove('visible');
                inputs.pass.classList.remove('invalid');
            } else {
                 if(passVal.length > 0) {
                     // Descriptive error message
                     errors.pass.textContent = "Must be 8+ chars with Upper, Lower, Number & Symbol";
                     errors.pass.classList.add('visible');
                     inputs.pass.classList.add('invalid');
                 }
                 valid = false;
            }

            // 5. Confirm Password
            if(inputs.cpass.value === inputs.pass.value && inputs.cpass.value !== '') {
                errors.cpass.classList.remove('visible');
                inputs.cpass.classList.remove('invalid');
            } else {
                if(inputs.cpass.value.length > 0) {
                    errors.cpass.textContent = "Passwords do not match";
                    errors.cpass.classList.add('visible');
                    inputs.cpass.classList.add('invalid');
                }
                valid = false;
            }
            
            // Final check to enable button
             if(valid && nameRegex.test(inputs.fullname.value) && emailRegex.test(inputs.email.value) && phoneRegex.test(inputs.phone.value) && strictPassRegex.test(inputs.pass.value) && inputs.pass.value === inputs.cpass.value) {
                 submitBtn.disabled = false;
             } else {
                 submitBtn.disabled = true;
             }
        }

        // Attach listeners
        Object.values(inputs).forEach(input => {
            input.addEventListener('input', validateForm);
            input.addEventListener('blur', validateForm);
        });

        // Password Strength Logic
        const passInput = inputs.pass;
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        passInput.addEventListener('input', function() {
            const val = this.value;
            let strength = 0;
            if(val.length > 5) strength += 20;
            if(val.length > 8) strength += 20;
            if(/[A-Z]/.test(val)) strength += 20;
            if(/[0-9]/.test(val)) strength += 20;
            if(/[^A-Za-z0-9]/.test(val)) strength += 20;

            strengthBar.style.width = strength + '%';
            
            if(strength < 40) {
                strengthBar.style.background = '#ef4444'; // Red
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#ef4444';
            } else if(strength < 80) {
                strengthBar.style.background = '#f59e0b'; // Orange
                strengthText.textContent = 'Medium';
                strengthText.style.color = '#f59e0b';
            } else {
                strengthBar.style.background = '#10b981'; // Green
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#10b981';
            }
        });

        // Password Suggestion logic
        function suggestPassword() {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
            let password = "";
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            inputs.pass.value = password;
            inputs.cpass.value = password;
            
            // Trigger events to update UI
            inputs.pass.dispatchEvent(new Event('input'));
            inputs.cpass.dispatchEvent(new Event('input'));
            
            // Toggle visibility to show the user
            const btn = inputs.pass.nextElementSibling;
            if(inputs.pass.type === 'password') {
                togglePass(btn);
            }
        }
    </script></body>
</html>
