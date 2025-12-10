<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HealCare</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/login.css">
</head>
<body>

    <div class="login-container">
        <!-- Step 1: Role Selection -->
        <div id="roleSelection" class="login-step active">
            <h1 class="fade-in-up">Welcome to HealCare</h1>
            <p class="fade-in-up delay-1">Please select your role to continue</p>
            
            <div class="roles-grid fade-in-up delay-2">
                <div class="role-card" onclick="selectRole('patient')">
                    <div class="role-icon">👤</div>
                    <h3>Patient</h3>
                    <p>Book appointments & view history</p>
                </div>
                <div class="role-card" onclick="selectRole('doctor')">
                    <div class="role-icon">🩺</div>
                    <h3>Doctor</h3>
                    <p>Manage patients & schedule</p>
                </div>
                <div class="role-card" onclick="selectRole('staff')">
                    <div class="role-icon">🏥</div>
                    <h3>Staff</h3>
                    <p>Administration & billing</p>
                </div>
                <div class="role-card" onclick="selectRole('admin')">
                    <div class="role-icon">⚙️</div>
                    <h3>Admin</h3>
                    <p>System control & reports</p>
                </div>
            </div>
            <a href="index.php" class="back-link">← Back to Home</a>
        </div>

        <!-- Step 2: Authentication Form -->
        <div id="authStep" class="login-step">
            <div class="auth-box glass-panel">
                <button class="back-btn" onclick="showRoles()">← Back</button>
                <div class="auth-header">
                    <div class="role-badge" id="selectedRoleBadge">Patient</div>
                    <h2>Log In</h2>
                </div>
                
                <div class="auth-tabs">
                    <button class="tab-btn active" data-tab="login">Log In</button>
                    <button class="tab-btn" data-tab="signup">Sign Up</button>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="auth-form active" action="auth_handler.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="role" id="loginRoleInput" value="patient">

                    <div class="input-group">
                        <label>Email ID</label>
                        <input type="email" name="email" placeholder="example@email.com" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="form-actions">
                        <label class="remember-me">
                            <input type="checkbox" name="remember"> Remember me
                        </label>
                        <a href="#" class="forgot-link">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Access Dashboard</button>
                </form>

                <!-- Signup Form -->
                <form id="signupForm" class="auth-form" action="auth_handler.php" method="POST">
                    <input type="hidden" name="action" value="signup">
                    <input type="hidden" name="role" id="signupRoleInput" value="patient">

                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" placeholder="John Doe" required>
                    </div>
                    <div class="input-group">
                        <label>Email ID</label>
                        <input type="email" name="email" placeholder="example@email.com" required>
                    </div>
                    <div class="input-group">
                        <label>Create Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <!-- Doctor specific fields (hidden by default, shown via JS) -->
                    <div id="doctorFields" style="display: none;">
                        <div class="input-group">
                            <label>Specialization</label>
                            <select name="specialization">
                                <option value="cardiology">Cardiology</option>
                                <option value="neurology">Neurology</option>
                                <option value="pediatrics">Pediatrics</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>License Number</label>
                            <input type="text" name="license" placeholder="LIC-12345">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Background Elements -->
    <div class="login-bg shadow-overlay"></div>

    <script src="js/login.js"></script>
    <script>
        // Update hidden inputs when role changes
        const originalSelectRole = selectRole;
        selectRole = function(role) {
            originalSelectRole(role);
            document.getElementById('loginRoleInput').value = role;
            document.getElementById('signupRoleInput').value = role;
        }
    </script>
</body>
</html>
