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
            font-size: 0.85rem;
            margin-top: 6px;
            display: none;
            text-align: right;
            font-weight: 600;
        }
        .input-light.error-border {
            border: 2px solid #ff4444;
            background-color: #ffe6e6 !important;
            color: #c00;
        }
        .input-light.success-border {
            border: 2px solid #00c851;
            background-color: #e8f5e9 !important;
            color: #007E33;
        }
        
        /* Suggest Password Link */
        .suggest-pass-link {
            font-size: 0.85rem;
            color: #4fc3f7;
            cursor: pointer;
            text-decoration: underline;
            display: block;
            text-align: center;
            margin-top: 10px;
            font-weight: 500;
        }
        .suggest-pass-link:hover {
            color: #fff;
        }
        .pass-strength-meter {
            height: 4px;
            background: #eee;
            margin-top: 5px;
            border-radius: 2px;
            transition: all 0.3s;
            width: 0%;
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
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>
                            <span class="suggest-pass-link" id="suggestPassBtn">Suggest Strong Password</span>
                            <div class="pass-strength-meter" id="strengthMeter"></div>
                            <small class="error-msg">Weak password</small>
                        </div>
                    </div>

                    <div class="input-row">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <div class="password-relative-group">
                                <input type="password" name="confirm_password" class="input-light" placeholder="Confirm Password" required>
                                <button type="button" class="toggle-eye-icon" onclick="togglePasswordVisibility(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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

                    <div style="text-align: center; margin-top: 15px; margin-bottom: 20px;">
                        <span style="color: #fff; opacity: 0.8; font-size: 0.9rem;">Are you a doctor or staff?</span>
                        <a href="apply.php" style="color: #4fc3f7; font-weight: 700; text-decoration: underline; margin-left: 5px;">Apply here</a>
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            if(!form) return;
            
            // Inputs
            const nameInput = form.querySelector('input[name="fullname"]');
            const emailInput = form.querySelector('input[name="email"]');
            const phoneInput = form.querySelector('input[name="phone"]');
            const pwdInput = form.querySelector('input[name="password"]');
            const cpwdInput = form.querySelector('input[name="confirm_password"]');

            // Strong Password: min 8, max 32, 1 upper, 1 lower, 1 num, 1 special char
            const strongPasswordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,32}$/;

            // Helper to show/hide error
            function showError(input, message) {
                const wrapper = input.closest('.input-wrapper');
                const errorMsg = wrapper.querySelector('.error-msg');
                input.classList.add('error-border');
                input.classList.remove('success-border');
                errorMsg.textContent = message;
                errorMsg.style.display = 'block';
                return false;
            }

            function showSuccess(input) {
                const wrapper = input.closest('.input-wrapper');
                const errorMsg = wrapper.querySelector('.error-msg');
                input.classList.remove('error-border');
                input.classList.add('success-border');
                errorMsg.style.display = 'none';
                return true;
            }

            // --- Enforce Must Not Start With Space ---
            function sanitizeLeadingSpace() {
                if (this.value.startsWith(' ')) {
                    this.value = this.value.trimStart();
                }
            }

            // Apply to all inputs
            const inputs = [nameInput, emailInput, phoneInput, pwdInput, cpwdInput];
            inputs.forEach(input => {
                if(input) {
                    input.addEventListener('input', sanitizeLeadingSpace);
                }
            });

            // --- Suggest Password Logic ---
            const suggestBtn = document.getElementById('suggestPassBtn');
            if(suggestBtn) {
                suggestBtn.addEventListener('click', function() {
                    const length = 12;
                    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*#_";
                    let retVal = "";
                    retVal += "A";
                    retVal += "a";
                    retVal += "1";
                    retVal += "!";
                    
                    for (let i = 0, n = charset.length; i < length - 4; ++i) {
                        retVal += charset.charAt(Math.floor(Math.random() * n));
                    }
                    retVal = retVal.split('').sort(() => 0.5 - Math.random()).join('');

                    pwdInput.value = retVal;
                    cpwdInput.value = retVal;
                    
                    // Visual feedback
                    validateField(pwdInput);
                    validateField(cpwdInput);
                    updateStrengthMeter(retVal);
                });
            }

            function updateStrengthMeter(val) {
                const meter = document.getElementById('strengthMeter');
                if(!meter) return;
                
                if(!val) { meter.style.width = '0%'; return; }
                
                let strength = 0;
                if (val.length >= 8) strength++;
                if (val.match(/[A-Z]/)) strength++;
                if (val.match(/[a-z]/)) strength++;
                if (val.match(/[0-9]/)) strength++;
                if (val.match(/[^a-zA-Z0-9]/)) strength++;

                if (strength <= 2) {
                    meter.style.width = '30%'; 
                    meter.style.backgroundColor = '#ff4444';
                } else if (strength <= 4) {
                    meter.style.width = '60%'; 
                    meter.style.backgroundColor = '#ffbb33';
                } else {
                    meter.style.width = '100%'; 
                    meter.style.backgroundColor = '#00c851';
                }
            }

            // Unified validation function
            function validateField(input) {
                if(!input) return true;
                const val = input.value.trim(); // Trim for checking but keep input value for password
                const rawVal = input.value;
                const name = input.name;

                // Basic Required Check (skip empty check if focused? No, live feedback wants aggressive check)
                if (!val && input.required) {
                    // Only show 'required' error if the user has interacted with it (dirty) or on blur
                    // But user asked for LIVE text feedback. Let's show it if length is 0?
                    // Usually annoying if it shows up before typing. 
                    // Let's rely on standard logic: if value is empty assign error but maybe handled by UI?
                    // We'll return 'false' but only show Error if it is 'touched'?
                    // For simplicity, we show error if empty on blur, but valid/invalid status on input might be too noisy.
                    // Lets compromise:
                    return showError(input, 'This field is required.');
                }

                if (val && !/^[a-zA-Z0-9]/.test(val) && name !== 'password' && name !== 'confirm_password') { // Passwords can start with anything? usually yes
                    return showError(input, 'Must start with a letter or number.');
                }

                switch(name) {
                    case 'fullname':
                        if (val.length < 3) return showError(input, 'Minimum 3 characters required.');
                        if (/\d/.test(val)) return showError(input, 'Name should not contain numbers.');
                        break;
                    case 'email':
                        const robustEmailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                        if (!robustEmailPattern.test(val)) return showError(input, 'Enter a valid email address (e.g. user@example.com).');
                        break;
                    case 'phone':
                        if (!/^\d+$/.test(val)) return showError(input, 'Phone currently contains non-digits.');
                        if (val.length !== 10) return showError(input, `Phone must be exactly 10 digits (Current: ${val.length}).`);
                        break;
                    case 'password':
                        updateStrengthMeter(rawVal);
                        if (rawVal.length < 8) return showError(input, 'Password must be at least 8 characters.');
                        if (!strongPasswordPattern.test(rawVal)) return showError(input, 'Must contain Upper, Lower, Number & Special Character.');
                        break;
                    case 'confirm_password':
                        if (rawVal !== pwdInput.value) return showError(input, 'Passwords do not match.');
                        break;
                }

                return showSuccess(input);
            }

            // Attach Live Listeners
            inputs.forEach(input => {
                if(!input) return;
                
                // On Input (Live)
                input.addEventListener('input', function() {
                    // For 'required' fields, don't show error immediately if empty (user just cleared it), 
                    // unless they already blurred? 
                    // Let's just validate. User asked for live feedback.
                    validateField(input);
                });

                // On Blur (Lost focus) - Ensure validation message persists
                input.addEventListener('blur', () => validateField(input));
            });

            // --- Form Submit ---
            form.addEventListener('submit', function(e) {
                let isFormValid = true;
                inputs.forEach(input => {
                    if (!validateField(input)) isFormValid = false;
                });

                if (!isFormValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = form.querySelector('.error-border');
                    if (firstError) firstError.focus();
                }
            });

        });
    </script>
</body>
</html>
