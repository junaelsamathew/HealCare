<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Team Application | HealCare</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Dark Theme Variables from Dashboard */
            --hc-primary: #3b82f6;
            --hc-primary-dark: #2563eb;
            --hc-bg-darkest: #050a15;
            --hc-card-bg: #111d33;
            --hc-input-bg: rgba(255, 255, 255, 0.05);
            --hc-border-color: rgba(255, 255, 255, 0.1);
            --hc-text-main: #ffffff;
            --hc-text-muted: #94a3b8;
            
            --hc-danger: #ef4444;
            --hc-success: #10b981;
            
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.5);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.5);
            --radius-lg: 1rem;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: var(--hc-bg-darkest);
            color: var(--hc-text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .main-container {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 992px) {
            .main-container { grid-template-columns: 1fr; }
            .preview-card-container { display: none; }
        }

        /* --- Left Side: Form wizard --- */
        .form-card {
            background: var(--hc-card-bg);
            border: 1px solid var(--hc-border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Step Indicator */
        .steps-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .steps-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--hc-border-color);
            z-index: 0;
            transform: translateY(-50%);
        }

        .step {
            position: relative;
            z-index: 1;
            background: var(--hc-card-bg);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--hc-border-color);
            color: var(--hc-text-muted);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .step.active {
            border-color: var(--hc-primary);
            background: var(--hc-primary);
            color: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }

        .step.completed {
            border-color: var(--hc-success);
            background: var(--hc-success);
            color: white;
        }

        /* Form Sections */
        .form-section {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .form-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-header {
            margin-bottom: 2rem;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            color: var(--hc-text-main);
            margin-bottom: 0.5rem;
        }
        
        .section-header p {
            color: var(--hc-text-muted);
            font-size: 0.95rem;
        }

        /* Floating Inputs */
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }

        .floating-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem; /* Left padding for icon */
            font-size: 1rem;
            border: 1px solid var(--hc-border-color);
            border-radius: 0.75rem;
            outline: none;
            transition: all 0.3s ease;
            background: var(--hc-input-bg);
            color: var(--hc-text-main);
        }

        .floating-input:focus {
            border-color: var(--hc-primary);
            background: rgba(59, 130, 246, 0.1);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        .floating-label {
            position: absolute;
            left: 3rem;
            top: 1rem;
            color: var(--hc-text-muted);
            pointer-events: none;
            transition: all 0.2s ease;
            font-size: 1rem;
        }
        
        /* Floating logic */
        .floating-input:focus ~ .floating-label,
        .floating-input:not(:placeholder-shown) ~ .floating-label,
        .floating-input.has-value ~ .floating-label {
            top: -0.6rem;
            left: 1rem;
            font-size: 0.75rem;
            background: var(--hc-card-bg); /* Match card bg to hide line */
            padding: 0 5px;
            color: var(--hc-primary);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 1.1rem;
            color: var(--hc-text-muted);
            transition: 0.3s;
        }
        
        .floating-input:focus ~ .input-icon {
            color: var(--hc-primary);
        }

        /* Validation Tooltip */
        .validation-tooltip {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 5px;
            padding: 0.5rem 0.8rem;
            background: #2d1b1b; /* Darker red bg */
            border-left: 3px solid var(--hc-danger);
            border-radius: 0.3rem;
            box-shadow: var(--shadow-md);
            font-size: 0.8rem;
            color: #fca5a5; /* Lighter red text */
            display: none;
            z-index: 10;
            width: 100%;
            opacity: 0; 
            transform: translateY(-5px);
            transition: all 0.3s;
        }

        .input-group.error .floating-input {
            border-color: var(--hc-danger);
            background: rgba(239, 68, 68, 0.05);
        }
        
        .input-group.error .validation-tooltip {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        .input-group.success .floating-input {
            border-color: var(--hc-success);
        }
        
        .status-icon {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1rem;
            display: none;
        }

        .input-group.error .status-icon.fa-circle-exclamation { display: block; color: var(--hc-danger); }
        .input-group.success .status-icon.fa-circle-check { display: block; color: var(--hc-success); }


        /* Action Buttons */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-prev { background: rgba(255, 255, 255, 0.1); color: var(--hc-text-muted); }
        .btn-prev:hover { background: rgba(255, 255, 255, 0.2); color: white; }

        .btn-next, .btn-submit {
            background: linear-gradient(135deg, var(--hc-primary) 0%, var(--hc-primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .btn-next:hover, .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.4);
        }

        /* --- Right Side: Preview Card --- */
        .preview-card {
            background: var(--hc-card-bg);
            border: 1px solid var(--hc-border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 2rem;
        }

        .preview-header {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--hc-border-color);
            margin-bottom: 1.5rem;
        }

        .profile-photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--hc-text-muted);
            font-size: 2rem;
            overflow: hidden;
            border: 3px solid var(--hc-card-bg);
            box-shadow: 0 0 0 2px var(--hc-border-color);
        }
        
        .profile-photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--hc-text-main);
            margin-bottom: 0.25rem;
        }

        .preview-role {
            font-size: 0.9rem;
            color: var(--hc-primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .preview-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 0.9rem;
            color: var(--hc-text-muted);
        }
        
        .detail-item i { width: 20px; text-align: center; color: var(--hc-primary); opacity: 0.8; }

        .trust-badge {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 0.5rem;
            font-size: 0.8rem;
            color: var(--hc-text-muted);
            text-align: center;
            border: 1px dashed var(--hc-border-color);
        }
        
        .hidden { display: none !important; }

        select.floating-input {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        /* Specific overrides for color in dark mode */
        select option {
            background-color: var(--hc-card-bg);
            color: white;
        }

    </style>
</head>
<body>

    <div class="main-container">
        
        <!-- Left Column: Form -->
        <div class="form-card">
            
            <Header style="text-align: center; margin-bottom: 25px;">
                <h1 style="font-size: 1.8rem; font-weight: 800; color: #ffffff;">Join Medical Team</h1>
                <p style="color: #ffffff; margin-top: 5px; opacity: 0.8;">Secure Application Portal</p>
                <a href="check_status.php" style="margin-top: 5rem; font-size: 0.8rem; text-decoration: none; font-weight: 600; color: var(--hc-primary);">Check Application Status</a>
            </Header>

            <!-- Step Progress -->
            <div class="steps-container">
                <div class="step active" id="stepIndicator1">1</div>
                <div class="step" id="stepIndicator2">2</div>
                <div class="step" id="stepIndicator3">3</div>
            </div>

            <form id="applyForm" action="auth_handler.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="apply">
                
                <!-- STEP 1: Personal Info -->
                <div class="form-section active" id="step1">
                    <div class="section-header">
                        <h2>Personal Information</h2>
                        <p>Tell us who you are. Your identity is verified securely.</p>
                    </div>

                    <div class="input-group">
                        <i class="fa-solid fa-user-doctor input-icon"></i>
                        <select name="role" id="role" class="floating-input" required onchange="handleRoleChange(this.value)">
                            <option value=""></option>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Hospital Staff</option>
                        </select>
                        <i class="fa-solid fa-circle-exclamation status-icon"></i>
                        <label class="floating-label">Applying For Role</label>
                        <div class="validation-tooltip">Please select a role.</div>
                    </div>

                    <div class="input-group hidden" id="staffTypeGroup">
                        <i class="fa-solid fa-users input-icon"></i>
                        <select name="staff_type" id="staff_type" class="floating-input" onchange="handleStaffTypeChange()">
                            <option value=""></option>
                            <option value="receptionist">Receptionist</option>
                            <option value="nurse">Nurse</option>
                            <option value="pharmacist">Pharmacist</option>
                            <option value="lab_staff">Lab Staff</option>
                            <option value="canteen_staff">Canteen Staff</option>
                        </select>
                        <label class="floating-label">Staff Department</label>
                    </div>

                    <div class="grid-2">
                        <div class="input-group">
                            <i class="fa-regular fa-user input-icon"></i>
                            <input type="text" name="fullname" id="fullname" class="floating-input" placeholder=" " required>
                            <i class="fa-solid fa-circle-exclamation status-icon"></i>
                            <i class="fa-solid fa-circle-check status-icon"></i>
                            <label class="floating-label">Full Name</label>
                            <div class="validation-tooltip">Required. No numbers allowed.</div>
                        </div>

                        <div class="input-group">
                            <i class="fa-solid fa-phone input-icon"></i>
                            <input type="tel" name="phone" id="phone" class="floating-input" placeholder=" " maxlength="10" required>
                            <i class="fa-solid fa-circle-exclamation status-icon"></i>
                            <i class="fa-solid fa-circle-check status-icon"></i>
                            <label class="floating-label">Phone Number</label>
                            <div class="validation-tooltip">Must be 10 digits.</div>
                        </div>
                    </div>

                    <div class="input-group">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" class="floating-input" placeholder=" " required>
                        <i class="fa-solid fa-circle-exclamation status-icon"></i>
                        <i class="fa-solid fa-circle-check status-icon"></i>
                        <label class="floating-label">Email Address (Gmail Only)</label>
                        <div class="validation-tooltip" id="emailTooltip">Please use a valid Gmail address.</div>
                    </div>

                    <div class="input-group">
                        <i class="fa-solid fa-location-dot input-icon"></i>
                        <input type="text" name="address" id="address" class="floating-input" placeholder=" " required>
                        <label class="floating-label">Current Address</label>
                    </div>
                     <div class="input-group">
                         <i class="fa-regular fa-image input-icon"></i>
                         <input type="file" name="profile_photo" id="profile_photo" class="floating-input" accept="image/*" style="padding-top: 0.8rem;">
                         <label class="floating-label" style="top: -0.6rem; left: 1rem; font-size: 0.75rem; background: white; padding: 0 5px; color: var(--hc-primary);">Profile Photo (Optional)</label>
                     </div>

                    <div class="nav-buttons" style="justify-content: flex-end;">
                        <button type="button" class="btn btn-next" onclick="nextStep(2)">Next Step <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- STEP 2: Professional Info -->
                <div class="form-section" id="step2">
                    <div class="section-header">
                        <h2>Professional Information</h2>
                        <p id="profSubText">Share your qualifications and experience.</p>
                    </div>

                    <!-- Dynamic Fields based on Role -->
                    <div id="dynamicProfessionalFields">
                        <!-- Injected via JS -->
                        <div class="grid-2">
                            <div class="input-group">
                                <i class="fa-solid fa-graduation-cap input-icon"></i>
                                <input type="text" name="highest_qualification" id="highest_qualification" class="floating-input" placeholder=" " required>
                                <label class="floating-label">Highest Qualification</label>
                            </div>
                            <div class="input-group">
                                <i class="fa-solid fa-briefcase input-icon"></i>
                                <input type="number" name="total_experience" id="total_experience" class="floating-input" placeholder=" " min="0" required>
                                <label class="floating-label">Total Experience (Years)</label>
                            </div>
                        </div>
                        
                        <div class="input-group">
                             <i class="fa-regular fa-file-pdf input-icon"></i>
                             <input type="file" name="resume" id="resume" class="floating-input" accept=".pdf" style="padding-top: 0.8rem;">
                             <label class="floating-label" style="top: -0.6rem; left: 1rem; font-size: 0.75rem; background: white; padding: 0 5px; color: var(--hc-primary);">Upload Resume (PDF)</label>
                             <div class="validation-tooltip">PDF Only.</div>
                         </div>
                    </div>

                    <div class="nav-buttons">
                        <button type="button" class="btn btn-prev" onclick="prevStep(1)"><i class="fa-solid fa-arrow-left"></i> Previous</button>
                        <button type="button" class="btn btn-next" onclick="nextStep(3)">Next Step <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- STEP 3: Role Specifics & Finish -->
                <div class="form-section" id="step3">
                    <div class="section-header">
                        <h2>Role-Specific Details</h2>
                        <p>Finalize your application parameters.</p>
                    </div>

                    <div id="roleSpecificFields">
                         <!-- Injected JS Content Here -->
                    </div>

                    <div style="margin-top: 2rem; border-top: 1px dashed #e2e8f0; padding-top: 2rem;">
                         <div class="section-header">
                            <h2 style="font-size: 1.2rem;">Availability</h2>
                         </div>
                         <div class="grid-2">
                             <div class="input-group">
                                 <i class="fa-regular fa-calendar input-icon"></i>
                                 <input type="date" name="date_of_joining" id="date_of_joining" class="floating-input" required>
                                 <label class="floating-label" style="top: -0.6rem; left: 1rem; font-size: 0.75rem; background: white; padding: 0 5px; color: var(--hc-primary);">Expected Joining Date</label>
                             </div>
                             <div class="input-group">
                                 <i class="fa-regular fa-clock input-icon"></i>
                                 <select name="shift_preference" id="shift_preference" class="floating-input" required>
                                     <option value=""></option>
                                     <option value="Day">Day Shift</option>
                                     <option value="Night">Night Shift</option>
                                     <option value="Rotational">Rotational</option>
                                 </select>
                                 <label class="floating-label">Shift Preference</label>
                             </div>
                         </div>
                    </div>

                    <div class="nav-buttons">
                        <button type="button" class="btn btn-prev" onclick="prevStep(2)"><i class="fa-solid fa-arrow-left"></i> Previous</button>
                        <button type="submit" class="btn btn-submit" id="finalSubmitBtn">Submit Doctor Request <i class="fa-solid fa-check-circle"></i></button>
                    </div>
                    
                    <p style="text-align: center; font-size: 0.8rem; color: #94a3b8; margin-top: 1rem;">
                        Your application will be reviewed within 24–48 hours.
                    </p>
                </div>

            </form>
        </div>

        <!-- Right Column: Live Preview -->
        <div class="preview-card-container">
            <div class="preview-card">
                <div class="preview-header">
                    <div class="profile-photo-preview">
                        <img id="previewPhoto" src="" alt="" style="display: none;">
                        <i class="fa-solid fa-user-doctor" id="previewPlaceholderIcon"></i>
                    </div>
                    <div class="preview-name" id="previewName">Your Name</div>
                    <div class="preview-role" id="previewRole">Role Not Selected</div>
                </div>
                
                <div class="preview-details">
                    <div class="detail-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span id="previewEmail">email@example.com</span>
                    </div>
                     <div class="detail-item">
                        <i class="fa-solid fa-phone"></i>
                        <span id="previewPhone">+91-XXXXXXXXXX</span>
                    </div>
                    <div class="detail-item">
                        <i class="fa-solid fa-briefcase"></i>
                        <span id="previewExp">0 Years Exp</span>
                    </div>
                    <div class="detail-item" id="previewDeptRow" style="display: none;">
                        <i class="fa-solid fa-hospital-user"></i>
                        <span id="previewDept">Department</span>
                    </div>
                </div>

                <div class="trust-badge">
                    <div style="margin-bottom: 0.5rem;"><i class="fa-solid fa-shield-halved" style="color: var(--hc-primary); font-size: 1.5rem;"></i></div>
                    <strong>Secure Submission</strong><br>
                    Verified by HealCare Admin<br>
                    Manual Document Review
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        let currentStep = 1;
        
        // --- Navigation ---
        function nextStep(step) {
            if (!validateStep(currentStep)) return;
            showStep(step);
        }

        function prevStep(step) {
            showStep(step);
        }

        function showStep(step) {
            document.querySelectorAll('.form-section').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            
            // Update Indicators
            for(let i=1; i<=3; i++) {
                const el = document.getElementById('stepIndicator' + i);
                if (i <= step) el.classList.add('active');
                if (i < step) {
                    el.classList.add('completed'); 
                    el.innerHTML = '<i class="fa-solid fa-check"></i>';
                } else {
                    el.classList.remove('completed');
                    el.innerHTML = i;
                }
            }
            currentStep = step;
        }

        // --- Live Preview Updates ---
        const inputs = {
            name: document.getElementById('fullname'),
            role: document.getElementById('role'),
            email: document.getElementById('email'),
            phone: document.getElementById('phone'),
            exp: document.getElementById('total_experience')
        };
        
        const preview = {
            name: document.getElementById('previewName'),
            role: document.getElementById('previewRole'),
            email: document.getElementById('previewEmail'),
            phone: document.getElementById('previewPhone'),
            exp: document.getElementById('previewExp'),
            photo: document.getElementById('previewPhoto'),
            icon: document.getElementById('previewPlaceholderIcon'),
            btn: document.getElementById('finalSubmitBtn')
        };

        inputs.name.addEventListener('input', (e) => preview.name.textContent = e.target.value || 'Your Name');
        inputs.email.addEventListener('input', (e) => preview.email.textContent = e.target.value || 'email@example.com');
        inputs.phone.addEventListener('input', (e) => preview.phone.textContent = e.target.value || '+91-XXXXXXXXXX');
        
        // Photo Preview
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.photo.src = e.target.result;
                    preview.photo.style.display = 'block';
                    preview.icon.style.display = 'none';
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // --- Role Handling ---
        function handleRoleChange(role) {
            const staffGroup = document.getElementById('staffTypeGroup');
            const rolePreview = document.getElementById('previewRole');
            
            if (role === 'doctor') {
                staffGroup.classList.add('hidden');
                document.getElementById('staff_type').required = false;
                rolePreview.textContent = 'Doctor Application';
                renderProfessionalFields('doctor');
                preview.btn.innerHTML = 'Submit Doctor Request <i class="fa-solid fa-check-circle"></i>';
            } else if (role === 'staff') {
                staffGroup.classList.remove('hidden');
                document.getElementById('staff_type').required = true;
                rolePreview.textContent = 'Staff Application';
                handleStaffTypeChange(); // To reset or set default
                preview.btn.innerHTML = 'Submit Staff Request <i class="fa-solid fa-check-circle"></i>';
            } else {
                rolePreview.textContent = 'Role Not Selected';
            }
        }

        function handleStaffTypeChange() {
            const type = document.getElementById('staff_type').value;
             const rolePreview = document.getElementById('previewRole');
            if(type) {
                 rolePreview.textContent = type.charAt(0).toUpperCase() + type.slice(1) + ' Application';
                 renderProfessionalFields(type);
            }
        }

        function renderProfessionalFields(type) {
             const dynamicContainer = document.getElementById('roleSpecificFields');
             // Common fields logic handled statically in Step 2 for simplicity, 
             // but specific fields go to Step 3 or specialized areas
             
             let html = '';
             
             if (type === 'doctor') {
                 html = `
                    <div class="grid-2">
                        <div class="input-group">
                            <i class="fa-solid fa-stethoscope input-icon"></i>
                            <input type="text" name="specialization" class="floating-input" placeholder=" " required oninput="updatePreviewDept(this.value)">
                            <label class="floating-label">Medical Specialization</label>
                        </div>
                        <div class="input-group">
                            <i class="fa-solid fa-user-md input-icon"></i>
                            <select name="designation" class="floating-input" required>
                                <option value=""></option>
                                <option value="Junior">Junior Doctor</option>
                                <option value="Senior">Senior Doctor</option>
                                <option value="Consultant">Consultant</option>
                            </select>
                            <label class="floating-label">Designation</label>
                        </div>
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-id-card input-icon"></i>
                        <input type="text" name="license_number" class="floating-input" placeholder=" " required>
                        <label class="floating-label">Medical Registration / License No.</label>
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-building-user input-icon"></i>
                         <select name="dept_preference" class="floating-input" required>
                            <option value=""></option>
                            <option value="General Medicine / Cardiovascular">General Medicine / Cardiovascular</option>
                            <option value="Gynecology">Gynecology</option>
                            <option value="Orthopedics">Orthopedics</option>
                            <option value="ENT">ENT</option>
                            <option value="Ophthalmology">Ophthalmology</option>
                            <option value="Dermatology">Dermatology</option>
                        </select>
                        <label class="floating-label">Department Preference</label>
                    </div>
                 `;
             } else if (type === 'receptionist') {
                 html = `
                    <div class="input-group">
                        <i class="fa-solid fa-language input-icon"></i>
                        <input type="text" name="languages_known" class="floating-input" placeholder=" " required>
                        <label class="floating-label">Languages Known</label>
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-headset input-icon"></i>
                        <input type="number" name="front_desk_exp" class="floating-input" placeholder=" " required>
                        <label class="floating-label">Front Desk Experience (Years)</label>
                    </div>
                 `;
             } else if (type === 'canteen_staff') {
                 html = `
                    <div class="input-group">
                         <i class="fa-solid fa-utensils input-icon"></i>
                        <select name="canteen_job_role" class="floating-input" required>
                            <option value=""></option>
                            <option value="Cook">Cook</option>
                            <option value="Helper">Helper</option>
                            <option value="Cashier">Cashier</option>
                        </select>
                        <label class="floating-label">Job Role</label>
                    </div>
                 `;
             } else if (type === 'lab_staff') {
                 html = `
                    <div class="input-group">
                        <i class="fa-solid fa-microscope input-icon"></i>
                        <select name="specialization" class="floating-input" required onchange="updatePreviewDept(this.value)">
                            <option value=""></option>
                            <option value="Blood / Pathology Lab">Blood / Pathology Lab (Gen Med, Cardiology, Gyneology, Dermatology)</option>
                            <option value="X-Ray / Imaging Lab">X-Ray / Imaging Lab (Orthopedics, ENT)</option>
                            <option value="Diagnostic Lab">Diagnostic Lab (ECG, Hearing, Eye tests)</option>
                            <option value="Ultrasound Lab">Ultrasound Lab</option>
                        </select>
                        <label class="floating-label">Lab Type / Specialization</label>
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-certificate input-icon"></i>
                        <input type="text" name="qualification_details" class="floating-input" placeholder=" " required>
                        <label class="floating-label">Technical Qualification Details</label>
                    </div>
                 `;
             } else {
                 // Generic for Nurse, Pharmacist
                 html = `
                     <div class="input-group">
                        <i class="fa-solid fa-certificate input-icon"></i>
                        <input type="text" name="qualification_details" class="floating-input" placeholder=" " required>
                        <label class="floating-label">Specific Qualification Details</label>
                    </div>
                 `;
             }
             
             dynamicContainer.innerHTML = html;
             
             // Re-attach float logic to new inputs if needed (CSS :placeholder-shown handles it mostly)
             // But validatio listeners need attach
             attachValidation(dynamicContainer);
        }

        function updatePreviewDept(val) {
            const row = document.getElementById('previewDeptRow');
            const text = document.getElementById('previewDept');
            if(val) {
                row.style.display = 'flex';
                text.textContent = val;
            } else {
                row.style.display = 'none';
            }
        }


        // --- Validation Logic ---

        function validateStep(step) {
            const section = document.getElementById('step' + step);
            const inputs = section.querySelectorAll('input[required], select[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!validateInput(input)) isValid = false;
            });

            return isValid;
        }

        function validateInput(input) {
            const group = input.closest('.input-group');
            const tooltip = group.querySelector('.validation-tooltip');
            let valid = true;
            let msg = "Required field.";

            if (!input.value.trim()) {
                valid = false;
            } else {
                // Email Check
                if (input.type === 'email') {
                    if (!input.value.toLowerCase().endsWith('@gmail.com')) {
                        valid = false;
                        msg = "No account registered with this email address is found"; // Keeping user's specific text logic
                        // In reality per previous turn, we want to check DB for non-existence.
                        // I will add the async check here.
                    }
                }
                // Phone Check
                if (input.name === 'phone') {
                    if (!/^\d{10}$/.test(input.value)) {
                        valid = false;
                        msg = "Must be 10 digits.";
                    }
                }
            }
            
            if (!valid) {
                 group.classList.add('error');
                 group.classList.remove('success');
                 if(tooltip) tooltip.textContent = msg;
            } else {
                 group.classList.remove('error');
                 group.classList.add('success');
            }
            return valid;
        }

        function attachValidation(scope = document) {
            scope.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('blur', () => validateInput(input));
                input.addEventListener('input', () => {
                     // Clear error on type, maybe wait for blur to show success?
                     // Or live validation for specific specific formats
                     if(input.closest('.input-group').classList.contains('error')) {
                         validateInput(input);
                     }
                     
                     // Helper for Exp preview
                     if(input.name === 'total_experience') {
                         document.getElementById('previewExp').textContent = input.value + ' Years Exp';
                     }
                });
            });
            
            // Special Email Async
            const emailInput = document.getElementById('email');
            if(emailInput) {
                let timer;
                emailInput.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        const val = emailInput.value;
                        if(val.toLowerCase().endsWith('@gmail.com')) {
                             // Check Server
                             fetch(`check_email_entry.php?email=${encodeURIComponent(val)}`)
                             .then(r => r.json())
                             .then(data => {
                                 const group = emailInput.closest('.input-group');
                                 const tooltip = group.querySelector('.validation-tooltip');
                                 if(!data.exists) {
                                      // Logic: Users said "If no google account is found, no account... found validation should be shown"
                                      // This implies they WANT the error if account NOT found.
                                      group.classList.add('error');
                                      group.classList.remove('success');
                                      tooltip.textContent = "No account registered with this email address is found";
                                 } else {
                                      group.classList.remove('error');
                                      group.classList.add('success');
                                      tooltip.textContent = "";
                                 }
                             });
                        }
                    }, 500);
                });
            }
        }
        
        attachValidation();

    </script>
</body>
</html>
