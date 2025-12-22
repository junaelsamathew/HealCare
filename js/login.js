console.group("HealCare Login Script Debug");
console.log("Script Version: 1.3");
console.groupEnd();

// 1. Google Auth Callbacks (Global Scope)
window.handleCredentialResponse = function (response) {
    console.log("Google response received");
    try {
        const responsePayload = decodeJwtResponse(response.credential);
        console.log("User email:", responsePayload.email);
        postGoogleLogin(responsePayload.email, responsePayload.name);
    } catch (e) {
        console.error("Error handling Google response:", e);
        alert("Authentication error. Please try again.");
    }
}

function decodeJwtResponse(token) {
    let base64Url = token.split('.')[1];
    let base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    let jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function (c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
    return JSON.parse(jsonPayload);
}

window.postGoogleLogin = function (email, name) {
    console.log("Preparing to authenticate with backend for:", email);

    const isSignup = document.getElementById('signupForm') &&
        document.getElementById('signupForm').classList.contains('active');

    const roleSelect = document.getElementById('signupRoleSelect');
    const role = isSignup ? (roleSelect ? roleSelect.value : 'patient') : '';

    const formData = new FormData();
    formData.append('action', isSignup ? 'google_signup' : 'google_login');
    formData.append('email', email);
    formData.append('fullname', name);
    formData.append('role', role);

    console.log("Sending request to auth_handler.php...");

    fetch('auth_handler.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            console.log("Response received from server. Status:", response.status);
            if (response.redirected) {
                console.log("Server redirected to:", response.url);
                window.location.href = response.url;
                return;
            }
            return response.text();
        })
        .then(text => {
            if (!text) return;
            console.log("Server response text:", text);

            // If the server sent a script (like an alert), execute it
            if (text.includes('<script>')) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = text;
                const scripts = tempDiv.getElementsByTagName('script');
                for (let i = 0; i < scripts.length; i++) {
                    eval(scripts[i].innerText);
                }
            } else {
                // Handle plain text response or error
                try {
                    const json = JSON.parse(text);
                    if (json.redirect) window.location.href = json.redirect;
                } catch (e) {
                    console.log("Not JSON, appearing to handle as redirect if no alert was present");
                }
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            alert("An error occurred during authentication. Please check your connection.");
        });
}

// 2. DOM Content Loaded Events
document.addEventListener('DOMContentLoaded', () => {
    // Auth Mode Toggling (Login vs Signup vs Forgot)
    const toggleLinks = document.querySelectorAll('.toggle-auth');
    const authForms = document.querySelectorAll('.auth-form');
    const messageGroups = document.querySelectorAll('.message-group');

    toggleLinks.forEach(link => {
        link.addEventListener('click', () => {
            const target = link.getAttribute('data-target');

            // Toggle Forms
            authForms.forEach(f => f.classList.remove('active'));
            if (target === 'login') {
                document.getElementById('loginForm').classList.add('active');
            } else if (target === 'signup') {
                document.getElementById('signupForm').classList.add('active');
            } else if (target === 'forgot') {
                document.getElementById('forgotForm').classList.add('active');
            } else if (target === 'reset') {
                document.getElementById('resetForm').classList.add('active');
            }

            // Toggle Visual Messages
            messageGroups.forEach(m => m.classList.remove('active'));
            if (target === 'login') {
                document.getElementById('loginMessage').classList.add('active');
            } else if (target === 'signup') {
                document.getElementById('signupMessage').classList.add('active');
            } else if (target === 'forgot' || target === 'reset') {
                document.getElementById('forgotMessage').classList.add('active');
            }
        });
    });

    // Handle Doctor Fields in Signup
    const signupRoleSelect = document.getElementById('signupRoleSelect');
    const doctorFields = document.getElementById('doctorFields');

    if (signupRoleSelect && doctorFields) {
        signupRoleSelect.addEventListener('change', function () {
            if (this.value === 'doctor') {
                doctorFields.style.display = 'block';
                doctorFields.querySelectorAll('input').forEach(field => {
                    field.setAttribute('required', 'true');
                });
            } else {
                doctorFields.style.display = 'none';
                doctorFields.querySelectorAll('input').forEach(field => {
                    field.removeAttribute('required');
                });
            }
        });
    }
    console.log("%c HealCare Auth Script Attached ", "background: #2563eb; color: #fff; padding: 5px; border-radius: 3px;");

    // --- Core Validation Helper Functions ---
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function showError(input, errorSpan, message) {
        if (!input || !errorSpan) return;
        input.classList.add('invalid');
        errorSpan.textContent = message;
        errorSpan.classList.add('visible');
    }

    function hideError(input, errorSpan) {
        if (!input || !errorSpan) return;
        input.classList.remove('invalid');
        errorSpan.textContent = "";
        errorSpan.classList.remove('visible');
    }

    // --- Validation Logic for Identity (Username/Email) ---
    function validateLoginIdentity(input, errorSpan) {
        const val = input.value.trim();
        console.log("Validating Identity:", val);

        if (val === "") {
            showError(input, errorSpan, "Email is required");
            return false;
        }

        // If it contains a dot or @, we treat it as an email attempt
        if (val.includes('@') || val.includes('.')) {
            if (!emailRegex.test(val)) {
                console.log("Invalid email format detected");
                showError(input, errorSpan, "Enter a valid email address");
                return false;
            }
        } else {
            // It's a username - check if it's too short or has invalid characters if needed
            // For now, if no dot or @, we assume it's a potential username and allow it 
            // unless it's empty (already checked)
        }

        hideError(input, errorSpan);
        return true;
    }

    // --- Validation Logic for Password ---
    function validateLoginPassword(input, errorSpan) {
        const val = input.value;
        if (val === "") {
            showError(input, errorSpan, "Password is required");
            return false;
        }
        if (val.length < 8) {
            showError(input, errorSpan, "Password must be at least 8 characters");
            return false;
        }
        hideError(input, errorSpan);
        return true;
    }

    // --- Initialize Login Form ---
    const loginForm = document.getElementById('loginForm');
    const identityInput = document.getElementById('loginIdentity');
    const passwordInput = document.getElementById('loginPassword');
    const identityError = document.getElementById('identityError');
    const passwordError = document.getElementById('passwordError');

    if (loginForm && identityInput) {
        console.log("Attaching listeners to identity:", identityInput.id);

        const triggerValidation = () => {
            console.log("Triggering validation for:", identityInput.id);
            validateLoginIdentity(identityInput, identityError);
        };

        identityInput.addEventListener('input', triggerValidation);
        identityInput.addEventListener('blur', triggerValidation);
        identityInput.addEventListener('change', triggerValidation);

        passwordInput.addEventListener('input', () => validateLoginPassword(passwordInput, passwordError));
        passwordInput.addEventListener('blur', () => validateLoginPassword(passwordInput, passwordError));

        loginForm.addEventListener('submit', function (e) {
            console.log("Final submission validation...");
            const isIdValid = validateLoginIdentity(identityInput, identityError);
            const isPassValid = validateLoginPassword(passwordInput, passwordError);
            if (!isIdValid || !isPassValid) {
                console.warn("Login blocked: Validation failed.");
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            console.log("Login form valid, submitting...");
        });
    }

    // --- Initialize Forgot Form (With AJAX) ---
    const forgotForm = document.getElementById('forgotForm');
    const emailInput = document.getElementById('forgotEmail');
    const emailError = document.getElementById('forgotEmailError');

    if (forgotForm && emailInput) {
        const validateForgot = () => {
            const val = emailInput.value.trim();
            if (val === "") { showError(emailInput, emailError, "Email is required"); return false; }
            if (!emailRegex.test(val)) { showError(emailInput, emailError, "Enter a valid email address"); return false; }
            hideError(emailInput, emailError);
            return true;
        };
        emailInput.addEventListener('input', validateForgot);
        emailInput.addEventListener('blur', validateForgot); // Added blur event for consistency

        forgotForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateForgot()) return;

            const formData = new FormData(this);
            fetch('auth_handler.php', { method: 'POST', body: formData })
                .then(response => response.text())
                .then(text => {
                    if (text.includes('OTP_SENT')) {
                        alert('A verification code has been sent to your email.');
                        document.getElementById('resetEmailHidden').value = emailInput.value;
                        document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
                        document.getElementById('resetForm').classList.add('active');
                        document.querySelectorAll('.message-group').forEach(m => m.classList.remove('active'));
                        document.getElementById('forgotMessage').classList.add('active');
                    } else {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = text;
                        tempDiv.querySelectorAll('script').forEach(s => eval(s.innerText));
                    }
                })
                .catch(err => console.error('Error:', err));
        });
    }

    // --- Initialize Reset Form ---
    const resetForm = document.getElementById('resetForm');
    if (resetForm) {
        const newPass = document.getElementById('resetPassword');
        const confPass = document.getElementById('resetConfirmPassword');
        const passErr = document.getElementById('resetPasswordError');
        const confErr = document.getElementById('resetConfirmPasswordError');

        const validateReset = () => {
            let v = true;
            if (newPass.value === "") { showError(newPass, passErr, "Password is required"); v = false; }
            else if (newPass.value.length < 8) { showError(newPass, passErr, "Min 8 characters"); v = false; }
            else hideError(newPass, passErr);

            if (confPass.value === "") { showError(confPass, confErr, "Confirm password is required"); v = false; } // Added check for empty confirm password
            else if (confPass.value !== newPass.value) { showError(confPass, confErr, "Passwords do not match"); v = false; }
            else hideError(confPass, confErr);
            return v;
        };

        [newPass, confPass].forEach(i => i && i.addEventListener('input', validateReset));
        [newPass, confPass].forEach(i => i && i.addEventListener('blur', validateReset)); // Added blur event for consistency

        // --- Suggest Password for Reset ---
        const resetSuggestBtn = document.getElementById('resetSuggestBtn');
        if (resetSuggestBtn) {
            resetSuggestBtn.addEventListener('click', function () {
                const length = 12;
                const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
                let retVal = "";
                retVal += "A"; retVal += "a"; retVal += "1"; retVal += "!"; // Ensure complexity
                for (let i = 0, n = charset.length; i < length - 4; ++i) {
                    retVal += charset.charAt(Math.floor(Math.random() * n));
                }
                retVal = retVal.split('').sort(() => 0.5 - Math.random()).join('');

                if (newPass) {
                    newPass.value = retVal;
                    newPass.type = "text";
                }
                if (confPass) {
                    confPass.value = retVal;
                    confPass.type = "text";
                }

                setTimeout(() => {
                    if (newPass) newPass.type = "password";
                    if (confPass) confPass.type = "password";
                }, 3000);

                validateReset();
            });
        }

        resetForm.addEventListener('submit', function (e) { if (!validateReset()) e.preventDefault(); });
    }
});


// Initialize password toggles
window.togglePasswordVisibility = function (btn) {
    const wrapper = btn.closest('.password-relative-group');
    const input = wrapper.querySelector('input');

    if (!input) return;

    const isPassword = input.getAttribute('type') === 'password';

    // Toggle Type
    input.setAttribute('type', isPassword ? 'text' : 'password');

    // Toggle Icon
    if (isPassword) {
        // Switched to Text -> Show 'Eye Off' (Slash)
        btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        `;
    } else {
        // Switched to Password -> Show 'Eye'
        btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        `;
    }
}

