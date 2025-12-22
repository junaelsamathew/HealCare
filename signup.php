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

    <!-- Styles -->
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/login.css">
    <style>
        /* Specific overrides for the Signup layout from the image */
        .signup-visual {
            flex: 1.2;
            position: relative;
            padding: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
            border-right: 4px solid #3b82f6; /* Blue divider line from image */
        }
        .signup-visual .visual-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('images/bgimg.jpg');
            background-size: cover;
            background-position: center;
            filter: blur(5px); /* Blurred background from image */
            opacity: 0.4;
            z-index: 1;
        }
        .signup-visual .visual-content {
            position: relative;
            z-index: 2;
            text-align: left;
            max-width: 550px;
        }
        .signup-visual h1 {
            font-size: 5rem;
            font-weight: 800;
            color: #0a192f;
            margin-bottom: 25px;
        }
        .signup-visual p {
            font-size: 1.35rem;
            font-weight: 600;
            line-height: 1.4;
            color: #000;
        }

        .signup-form-area {
            flex: 0.8;
            background-color: #0a192f; /* Deep dark blue from image */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }
        .form-container {
            width: 100%;
            max-width: 480px;
        }
        .auth-form h3 {
            font-size: 3.5rem;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 800;
        }
        .auth-form .subtitle {
            text-align: center;
            opacity: 0.9;
            margin-bottom: 40px;
            font-size: 1rem;
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
            text-align: right; /* Right align labels like in image */
        }
        .input-light {
            flex: 0.6;
            padding: 12px 15px;
            border-radius: 4px;
            border: none;
            font-size: 1rem;
        }

        .btn-submit-new {
            width: 80%; /* Smaller width like in image */
            margin: 20px auto 0;
            display: block;
            background-color: #1e40af;
            font-size: 1.8rem;
            padding: 15px;
        }
        
        .form-footer-custom {
            text-align: center;
            margin-top: 25px;
            color: #fff;
        }
        .form-footer-custom a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }
        .form-footer-custom a:hover {
            text-decoration: underline;
        }
        
        .or-divider {
            text-align: center;
            margin: 15px 0;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .google-signup-center {
            display: flex;
            justify-content: center;
            margin-top: 10px;
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

        /* Validation Styles */
        .input-wrapper {
            flex: 0.6;
            display: flex;
            flex-direction: column;
        }
        .input-wrapper .input-light {
            width: 100%;
            flex: unset; /* Remove flex from input since wrapper handles it */
        }
        .error-msg {
            color: #ff4444;
            font-size: 0.75rem;
            margin-top: 4px;
            display: none;
            text-align: left;
            font-weight: 500;
        }
        .input-light.error-border {
            border: 1px solid #ff4444;
        }
        .input-light.success-border {
            border: 1px solid #00c851;
        }
        
        /* Suggest Password Link */
        .pass-label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .suggest-container {
            text-align: center;
            margin-top: 5px;
            margin-bottom: 5px;
            width: 100%;
        }
        .suggest-pass-link {
            font-size: 0.82rem;
            color: #4fc3f7;
            cursor: pointer;
            text-decoration: underline;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap; /* Keep on one line */
        }
        .suggest-pass-link:hover {
            color: #fff;
            text-shadow: 0 0 5px rgba(79, 195, 247, 0.5);
        }
        .pass-strength-meter {
            height: 4px;
            background: #eee;
            margin-top: 5px;
            border-radius: 2px;
            transition: all 0.3s;
            width: 0%;
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
         data-context="signup"
         data-ux_mode="popup"
         data-callback="handleCredentialResponse"
         data-auto_prompt="false">
    </div>

    <!-- Header Section (Matches image top bar) -->
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
        <section class="signup-visual">
            <div class="visual-content">
                <h1>Heal Care</h1>
                <p>Create your Heal Care account to manage appointments, view medical records, and receive personalized healthcare services.</p>
            </div>
            <div class="visual-bg"></div>
        </section>

        <!-- Right Section -->
        <section class="signup-form-area">
            <div class="form-container">
                <form id="signupForm" class="auth-form active" action="auth_handler.php" method="POST">
                    <input type="hidden" name="action" value="signup">
                    
                    <h3>Sign Up</h3>
                    <p class="subtitle">Welcome! Please Sign Up to <span>your account</span></p>

                    <div class="input-row">
                        <label>Register as...</label>
                        <select name="role" id="signupRoleSelect" class="input-light" required>
                            <option value="patient" selected>Patient</option>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="input-row">
                        <label>Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" name="fullname" class="input-light" placeholder="Full Name" required>
                            <small class="error-msg">Name should not contain spaces</small>
                        </div>
                    </div>

                    <div class="input-row">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" class="input-light" placeholder="Email" required>
                            <small class="error-msg">Please enter a valid email</small>
                        </div>
                    </div>

                    <div class="input-row">
                        <label>Phone</label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" class="input-light" placeholder="Phone Number" maxlength="10" pattern="\d{10}" required>
                            <small class="error-msg">Phone must be 10 digits</small>
                        </div>
                    </div>

                    <div class="input-row">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <div class="password-relative-group">
                                <input type="password" name="password" id="passwordInput" class="input-light" placeholder="Password" required minlength="8">
                                <button type="button" class="toggle-eye-icon" onclick="togglePasswordVisibility(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="pass-strength-meter" id="strengthMeter"></div>
                            <div class="suggest-container">
                                <span class="suggest-pass-link" id="suggestPassBtn">Suggest Strong Password</span>
                            </div>
                            <small class="error-msg">Weak password</small>
                        </div>
                    </div>

                    <div class="input-row">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <div class="password-relative-group">
                                <input type="password" name="confirm_password" class="input-light" placeholder="Confirm Password" required>
                                <button type="button" class="toggle-eye-icon" onclick="togglePasswordVisibility(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>
                            <small class="error-msg">Passwords do not match</small>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-new">Create Account</button>

                    <div class="or-divider">Or</div>

                    <div class="google-signup-center">
                        <div class="g_id_signin" 
                             data-type="standard" 
                             data-shape="rectangular" 
                             data-theme="outline" 
                             data-text="signup_with" 
                             data-size="large" 
                             data-logo_alignment="center" 
                             data-width="320">
                        </div>
                    </div>

                    <div class="form-footer-custom">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="index.php" class="back-home-pill">← Back to Home</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script src="js/login.js"></script>
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
                // Show 'Eye Off' (Slash) - now visible
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>`;
            } else {
                // Show 'Eye' - now hidden
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>`;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            
            // Inputs
            const nameInput = form.querySelector('input[name="fullname"]');
            const emailInput = form.querySelector('input[name="email"]');
            const phoneInput = form.querySelector('input[name="phone"]');
            const pwdInput = form.querySelector('input[name="password"]');
            const cpwdInput = form.querySelector('input[name="confirm_password"]');

            // Regex Patterns
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            // Phone validation: exactly 10 digits
            const phonePattern = /^\d{10}$/;
            // Strong Password: min 8, max 32, 1 upper, 1 lower, 1 num, 1 special
            // Note: {8,32} enforces length constraints
            const strongPasswordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,32}$/;

            // Helper to show/hide error
            function showError(input, message) {
                const wrapper = input.parentElement;
                const errorMsg = wrapper.querySelector('.error-msg');
                input.classList.add('error-border');
                input.classList.remove('success-border');
                errorMsg.textContent = message;
                errorMsg.style.display = 'block'; // Force visible
                return false;
            }

            function showSuccess(input) {
                const wrapper = input.parentElement;
                const errorMsg = wrapper.querySelector('.error-msg');
                input.classList.remove('error-border');
                input.classList.add('success-border');
                errorMsg.style.display = 'none';
                return true;
            }

            // --- Prevent Leading Space (Keydown) ---
            function blockLeadingSpace(e) {
                if (e.key === ' ' && this.value.length === 0) {
                    e.preventDefault();
                }
            }
            
            // --- Enforce Must Not Start With Space (Input Sanitizer) ---
            // Handles paste or any other insertion of leading spaces
            function sanitizeLeadingSpace() {
                if (this.value.startsWith(' ')) {
                    this.value = this.value.trimStart();
                }
            }

            // Apply to all inputs
            [nameInput, emailInput, phoneInput, pwdInput, cpwdInput].forEach(input => {
                input.addEventListener('keydown', blockLeadingSpace);
                input.addEventListener('input', sanitizeLeadingSpace); // Runs before validation logic usually
            });

            // --- Suggest Password Logic ---
            const suggestBtn = document.getElementById('suggestPassBtn');
            suggestBtn.addEventListener('click', function() {
                const length = 12;
                const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
                let retVal = "";
                // Ensure at least one of each required type
                retVal += "A"; // Upper
                retVal += "a"; // Lower
                retVal += "1"; // Number
                retVal += "!"; // Special
                
                for (let i = 0, n = charset.length; i < length - 4; ++i) {
                    retVal += charset.charAt(Math.floor(Math.random() * n));
                }
                
                // Shuffle logic (simple sort) to mix the forced chars
                retVal = retVal.split('').sort(() => 0.5 - Math.random()).join('');

                pwdInput.value = retVal;
                cpwdInput.value = retVal;
                
                // Briefly show password text so user can see it
                pwdInput.type = "text";
                cpwdInput.type = "text";
                setTimeout(() => {
                    pwdInput.type = "password";
                    cpwdInput.type = "password";
                }, 3000); 

                // Trigger validation visual
                showSuccess(pwdInput);
                showSuccess(cpwdInput);
                updateStrengthMeter(retVal);
            });

            function updateStrengthMeter(val) {
                const meter = document.getElementById('strengthMeter');
                if(!val) { meter.style.width = '0%'; return; }
                
                let strength = 0;
                if (val.length >= 8) strength++;
                if (val.match(/[A-Z]/)) strength++;
                if (val.match(/[a-z]/)) strength++;
                if (val.match(/[0-9]/)) strength++;
                if (val.match(/[^a-zA-Z0-9]/)) strength++; // Special char

                if (strength <= 2) {
                    meter.style.width = '30%'; 
                    meter.style.backgroundColor = '#ff4444'; // Red
                } else if (strength <= 4) {
                    meter.style.width = '60%'; 
                    meter.style.backgroundColor = '#ffbb33'; // Orange
                } else {
                    meter.style.width = '100%'; 
                    meter.style.backgroundColor = '#00c851'; // Green
                }
            }

            // --- 1. Name Validation ---
            nameInput.addEventListener('input', function() {
                const val = this.value; 
                
                // STRICT: First character must be Alphanumeric
                if (val.length > 0 && !/^[a-zA-Z0-9]/.test(val)) {
                    showError(this, 'Must start with a letter or number (no symbols).');
                }
                // Check if it's only spaces (covered by above, but strict trim check acts as backup)
                else if (val.trim().length === 0 && val.length > 0) {
                     showError(this, 'Please enter a valid name.');
                }
                else if (val.length > 0) {
                    showSuccess(this);
                } else {
                    this.classList.remove('error-border', 'success-border');
                    this.parentElement.querySelector('.error-msg').style.display = 'none';
                }
            });

            // --- 2. Email Validation ---
            emailInput.addEventListener('input', function() {
                const val = this.value; // Raw value to check first char
                
                if (val.length > 0) {
                    if (!/^[a-zA-Z0-9]/.test(val)) {
                        showError(this, 'Must start with a letter or number.');
                    }
                    else if (!emailPattern.test(val.trim())) {
                        showError(this, 'Invalid email format.');
                    } else {
                        showSuccess(this);
                    }
                } else {
                    this.classList.remove('error-border', 'success-border');
                    this.parentElement.querySelector('.error-msg').style.display = 'none';
                }
            });

            // --- 3. Phone Validation ---
            phoneInput.addEventListener('input', function() {
                const val = this.value; 
                
                // Existing check handles symbols/spaces globally, but let's be strict about start
                if (val.length > 0 && !/^[a-zA-Z0-9]/.test(val)) {
                     showError(this, 'Must start with a number.'); 
                }
                else if (/[^0-9]/.test(val)) {
                     showError(this, 'Only numbers are allowed, no alphabets.');
                } 
                else if (val.length > 0 && val.length !== 10) {
                    showError(this, 'Phone number must be exactly 10 digits.');
                } 
                else if (val.length === 10) {
                    showSuccess(this);
                } else {
                    this.classList.remove('error-border', 'success-border');
                    this.parentElement.querySelector('.error-msg').style.display = 'none';
                }
            });

            // --- 4. Password Validation ---
            pwdInput.addEventListener('input', function() {
                const val = this.value;
                updateStrengthMeter(val);

                if (val.length > 0) {
                     if (!/^[a-zA-Z0-9]/.test(val)) {
                         showError(this, 'Must start with a letter or number.');
                     } else if (val.length < 8) {
                         showError(this, 'Minimum 8 characters required.');
                     } else if (val.length > 32) {
                         showError(this, 'Maximum 32 characters allowed.');
                     } else if (!strongPasswordPattern.test(val)) {
                         showError(this, 'Weak: Needs Upper, Lower, Number & Special Char.');
                     } else {
                         showSuccess(this);
                     }
                } else {
                    this.classList.remove('error-border', 'success-border');
                    this.parentElement.querySelector('.error-msg').style.display = 'none';
                }
                
                // Re-validate confirm password
                if (cpwdInput.value.length > 0) {
                    if (cpwdInput.value !== val) {
                        showError(cpwdInput, 'Passwords do not match!');
                    } else {
                        showSuccess(cpwdInput);
                    }
                }
            });

            // --- 5. Confirm Password Validation ---
            cpwdInput.addEventListener('input', function() {
                const val = this.value;
                const pwd = pwdInput.value;
                
                if (val.length > 0) {
                    if (!/^[a-zA-Z0-9]/.test(val)) {
                         showError(this, 'Must start with a letter or number.');
                    } else if (val !== pwd) {
                        showError(this, 'Passwords do not match!');
                    } else {
                        showSuccess(this);
                    }
                } else {
                    this.classList.remove('error-border', 'success-border');
                    this.parentElement.querySelector('.error-msg').style.display = 'none';
                }
            });

            // --- Form Submit ---
            form.addEventListener('submit', function(e) {
                // Run all checks one last time
                let valid = true;

                // Helper regex
                const startRegex = /^[a-zA-Z0-9]/;

                // Name
                if (!startRegex.test(nameInput.value) || nameInput.value.trim().length === 0) {
                    valid = showError(nameInput, 'Full Name must start with alphanumeric and be valid.');
                }
                
                // Email
                if (!startRegex.test(emailInput.value) || !emailPattern.test(emailInput.value.trim())) {
                    valid = showError(emailInput, 'Invalid email format or start character.');
                }

                // Phone
                const phoneVal = phoneInput.value;
                // strict check for digits and start char (subset of digits)
                if (!startRegex.test(phoneVal) || /[^0-9]/.test(phoneVal) || phoneVal.length !== 10) {
                     valid = showError(phoneInput, 'Phone must be exactly 10 digits (numbers only).');
                }

                // Password
                if (!startRegex.test(pwdInput.value)) {
                     valid = showError(pwdInput, 'Password must start with a letter or number.');
                } else if (!strongPasswordPattern.test(pwdInput.value)) {
                    valid = showError(pwdInput, 'Password is too weak or invalid.');
                }

                // Confirm
                if (!startRegex.test(cpwdInput.value) || pwdInput.value !== cpwdInput.value) {
                    valid = showError(cpwdInput, 'Passwords do not match or invalid format!');
                }

                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
