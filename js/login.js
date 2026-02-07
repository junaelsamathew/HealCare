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
    // Initialize Brand Animation
    initBrandAnimation();

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

    // --- Live Validations ---
    const loginForm = document.getElementById('loginForm');
    const loginIdentity = document.getElementById('loginIdentity');
    const loginPassword = document.getElementById('loginPassword');

    const forgotEmail = document.getElementById('forgotEmail');
    const resetOtp = document.getElementById('resetOtp');
    const resetNewPassword = document.getElementById('resetNewPassword');
    const resetConfirmPassword = document.getElementById('resetConfirmPassword');

    // Helper to show error
    function showError(input, errorId, message) {
        const errorDiv = document.getElementById(errorId);
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.add('visible');
        }
        input.classList.add('invalid');
    }

    // Helper to clear error
    function clearError(input, errorId) {
        const errorDiv = document.getElementById(errorId);
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.remove('visible');
        }
        input.classList.remove('invalid');
    }

    // Validation Rules
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function validateAlphanumericStart(val) {
        if (!val) return true; // Handled by 'required'
        return /^[a-zA-Z0-9]/.test(val);
    }

    // 1. Identity Validation (Login) -> Now strictly Email
    function validateIdentityField() {
        const val = loginIdentity.value.trim();
        if (!val) {
            showError(loginIdentity, 'identityError', 'Email address is required.');
            return false;
        }
        if (!validateEmail(val)) {
            showError(loginIdentity, 'identityError', 'Please enter a valid email address.');
            return false;
        }
        clearError(loginIdentity, 'identityError');
        return true;
    }

    // 2. Password Validation (Login)
    function validatePasswordField() {
        const val = loginPassword.value;
        if (!val) {
            showError(loginPassword, 'passwordError', 'Password is required.');
            return false;
        }
        clearError(loginPassword, 'passwordError');
        return true;
    }

    // 3. Email Validation (Forgot)
    function validateForgotEmail() {
        if (!forgotEmail) return true;
        const val = forgotEmail.value.trim();
        if (!val) {
            showError(forgotEmail, 'forgotEmailError', 'Email is required.');
            return false;
        }
        if (!validateEmail(val)) {
            showError(forgotEmail, 'forgotEmailError', 'Please enter a valid email.');
            return false;
        }
        clearError(forgotEmail, 'forgotEmailError');
        return true;
    }

    // 4. Reset Forms Validations
    function validateOtp() {
        if (!resetOtp) return true;
        const val = resetOtp.value.trim();
        if (!val || val.length !== 6 || !/^\d+$/.test(val)) {
            showError(resetOtp, 'otpError', 'Enter 6-digit numeric code.');
            return false;
        }
        clearError(resetOtp, 'otpError');
        return true;
    }

    function validateNewPassword() {
        if (!resetNewPassword) return true;
        const val = resetNewPassword.value;
        if (val.length < 8) {
            showError(resetNewPassword, 'newPasswordError', 'Minimum 8 characters.');
            return false;
        }
        clearError(resetNewPassword, 'newPasswordError');
        return true;
    }

    function validateConfirmPassword() {
        if (!resetConfirmPassword) return true;
        const val1 = resetNewPassword.value;
        const val2 = resetConfirmPassword.value;
        if (val1 !== val2) {
            showError(resetConfirmPassword, 'confirmPasswordError', 'Passwords do not match.');
            return false;
        }
        clearError(resetConfirmPassword, 'confirmPasswordError');
        return true;
    }

    // Event Listeners for Live Feedback
    if (loginIdentity) {
        loginIdentity.addEventListener('input', validateIdentityField);
        loginIdentity.addEventListener('blur', validateIdentityField);
    }
    if (loginPassword) {
        loginPassword.addEventListener('input', validatePasswordField);
        loginPassword.addEventListener('blur', validatePasswordField);
    }
    if (forgotEmail) {
        forgotEmail.addEventListener('input', validateForgotEmail);
        forgotEmail.addEventListener('blur', validateForgotEmail);
    }
    if (resetOtp) {
        resetOtp.addEventListener('input', validateOtp);
        resetOtp.addEventListener('blur', validateOtp);
    }
    if (resetNewPassword) {
        resetNewPassword.addEventListener('input', validateNewPassword);
        resetNewPassword.addEventListener('blur', validateNewPassword);
    }
    if (resetConfirmPassword) {
        resetConfirmPassword.addEventListener('input', validateConfirmPassword);
        resetConfirmPassword.addEventListener('blur', validateConfirmPassword);
    }

    // Handle Login Form Submission
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const isIdentityValid = validateIdentityField();
            const isPasswordValid = validatePasswordField();

            if (!isIdentityValid || !isPasswordValid) {
                e.preventDefault();
            }
        });
    }

    // --- Forgot Password AJAX ---
    const forgotForm = document.getElementById('forgotForm');
    if (forgotForm) {
        forgotForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate first
            if (!validateForgotEmail()) return;

            const email = this.querySelector('input[name="email"]').value;
            const formData = new FormData(this);

            fetch('auth_handler.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.text())
                .then(text => {
                    if (text.includes('OTP_SENT')) {
                        alert('A verification code has been sent to your email.');
                        document.getElementById('resetEmailHidden').value = email;

                        // Switch to Reset Form
                        authForms.forEach(f => f.classList.remove('active'));
                        document.getElementById('resetForm').classList.add('active');

                        // Switch message
                        messageGroups.forEach(m => m.classList.remove('active'));
                        document.getElementById('forgotMessage').classList.add('active');
                    } else {
                        // Handle server-side errors/alerts
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = text;
                        const scripts = tempDiv.getElementsByTagName('script');
                        for (let i = 0; i < scripts.length; i++) {
                            eval(scripts[i].innerText);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    }

    // --- Doctor Fields Toggle ---
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

    // --- Reset Form Submission ---
    const resetForm = document.getElementById('resetForm');
    if (resetForm) {
        resetForm.addEventListener('submit', function (e) {
            const i1 = validateOtp();
            const i2 = validateNewPassword();
            const i3 = validateConfirmPassword();
            if (!i1 || !i2 || !i3) {
                e.preventDefault();
            }
        });
    }
});


// Initialize password toggles
window.togglePasswordVisibility = function (btn) {
    const input = btn.previousElementSibling;
    if (!input || input.tagName !== 'INPUT') return;

    const isPassword = input.getAttribute('type') === 'password';

    // Toggle Type
    input.setAttribute('type', isPassword ? 'text' : 'password');

    // Toggle Icon
    btn.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
}

// Fix: Remove annoying "Minimize" tooltip if present (e.g. from extensions or browser features)
document.addEventListener('DOMContentLoaded', function () {
    const removeMinimizeTooltip = () => {
        document.querySelectorAll('[title="Minimize"]').forEach(el => {
            el.removeAttribute('title');
        });
    };
    removeMinimizeTooltip();
    // Observe for dynamic additions
    new MutationObserver(removeMinimizeTooltip).observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['title']
    });
});

/* -------------------------------------------------------------------------- */
/*                                BRAND ANIMATION                             */
/* -------------------------------------------------------------------------- */
function initBrandAnimation() {
    const brandElement = document.querySelector('.animated-brand');
    if (!brandElement) return;

    const text = brandElement.textContent.trim();
    brandElement.textContent = ''; // Clear text

    // Create spans
    const letters = [];
    for (let char of text) {
        const span = document.createElement('span');
        span.textContent = char;
        span.classList.add('brand-letter');
        if (char === ' ') {
            span.style.width = '0.3em'; // Space
            span.style.display = 'inline-block';
        }
        brandElement.appendChild(span);
        letters.push(span);
    }

    function animate() {
        // Show sequence
        letters.forEach((letter, index) => {
            setTimeout(() => {
                letter.classList.add('visible');
            }, index * 100); // 100ms staggering
        });

        // Hide sequence (after full text is shown + delay)
        const totalTime = (letters.length * 100) + 2000; // 2s pause

        setTimeout(() => {
            letters.forEach((letter, index) => {
                setTimeout(() => {
                    letter.classList.remove('visible');
                }, index * 50); // Faster fade out
            });
        }, totalTime);

        const cycleTime = totalTime + (letters.length * 50) + 500; // time to hide + pause

        setTimeout(animate, cycleTime);
    }

    // Start animation
    animate();
}
