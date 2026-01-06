<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor & Staff Application - HealCare</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="styles/main.css">
    <style>
        :root {
            --hc-blue: #1e40af;
            --hc-light-blue: #3b82f6;
            --hc-navy: #0a192f;
            --hc-bg: #f8fafc;
            --hc-white: #ffffff;
            --hc-border: #e2e8f0;
            --hc-text: #1e293b;
            --hc-accent: #4fc3f7;
        }

        body {
            background-color: var(--hc-bg);
            color: var(--hc-text);
            margin: 0;
            overflow-x: hidden;
        }

        .apply-container {
            max-width: 1000px;
            margin: 50px auto;
            background: var(--hc-white);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .apply-header {
            background-color: var(--hc-navy);
            color: var(--hc-white);
            padding: 40px;
            text-align: center;
        }

        .apply-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .apply-header p {
            opacity: 0.8;
            margin-top: 10px;
            font-size: 1rem;
        }

        .form-body {
            padding: 40px;
        }

        .form-section {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--hc-border);
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--hc-blue);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title span {
            background: var(--hc-blue);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .grid-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .grid-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--hc-text);
        }

        .form-control {
            padding: 12px 15px;
            border: 1px solid var(--hc-border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--hc-light-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        textarea.form-control {
            height: 100px;
            resize: vertical;
        }

        .helper-text {
            font-size: 0.8rem;
            color: #64748b;
        }

        .btn-submit {
            background-color: var(--hc-blue);
            color: white;
            padding: 16px 40px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, background 0.2s;
        }

        .btn-submit:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
        }

        .error-msg {
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 4px;
            display: none;
        }

        .error-msg.visible {
            display: block;
        }

        .invalid {
            border-color: #ef4444;
        }

        /* Specialized Styling for Role Selection */
        .role-selector {
            background: #f1f5f9;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .role-selector h3 {
            margin-top: 0;
            font-size: 1.1rem;
            color: var(--hc-navy);
        }

        #dynamicRoleFields {
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .footer-note {
            text-align: center;
            padding: 20px;
            font-size: 0.9rem;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="apply-container">
        <header class="apply-header">
            <h1>Join HealCare Medical Team</h1>
            <p>Start your professional journey with a leading healthcare organization.</p>
            <div style="margin-top: 15px;">
                <a href="check_status.php" style="color: #3b82f6; font-weight: 700; text-decoration: underline; font-size: 1.1rem;">Check Status</a>
            </div>
        </header>

        <form id="applyForm" class="form-body" action="auth_handler.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="apply">

            <!-- INITIAL ROLE SELECTION -->
            <div class="role-selector">
                <h3>Select Your Primary Role</h3>
                <div class="grid-row" style="max-width: 600px; margin: 15px auto;">
                    <div class="form-group">
                        <select name="role" id="roleSelect" class="form-control" required style="text-align: center; text-align-last: center;">
                            <option value="">-- I am applying as --</option>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Hospital Staff</option>
                        </select>
                    </div>
                    <div id="staffTypeGroup" class="form-group" style="display: none;">
                        <select name="staff_type" id="staffTypeSelect" class="form-control" style="text-align: center; text-align-last: center;">
                            <option value="">-- Choose Staff Type --</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="nurse">Nurse</option>
                            <option value="pharmacist">Pharmacist</option>
                            <option value="lab_staff">Lab Staff</option>
                            <option value="canteen_staff">Canteen Staff</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="formContent" style="display: none;">
                
                <!-- SECTION 1: PERSONAL INFORMATION -->
                <div class="form-section">
                    <h2 class="section-title"><span>1</span> Personal Information</h2>
                    <div class="grid-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullname" id="fullname" class="form-control" placeholder="John Doe" required>
                            <div class="error-msg" id="fullnameError">Please enter your full name.</div>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="john@example.com" required>
                            <div class="error-msg" id="emailError">Invalid email format.</div>
                        </div>
                    </div>
                    <div class="grid-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" id="phone" class="form-control" placeholder="10-digit mobile" maxlength="10" required>
                            <div class="error-msg" id="phoneError">Must be 10 numeric digits.</div>
                        </div>
                        <div class="form-group">
                            <label>Profile Photo <span class="helper-text">(Optional)</span></label>
                            <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Current Address</label>
                        <textarea name="address" id="address" class="form-control" placeholder="Complete residential address" required></textarea>
                        <div class="error-msg" id="addressError">Address is required.</div>
                    </div>
                </div>

                <!-- SECTION 2: PROFESSIONAL INFORMATION -->
                <div class="form-section" id="professionalSection">
                    <h2 class="section-title"><span>2</span> Professional Information</h2>
                    <div class="grid-row" id="commonProfessionalFields">
                        <div class="form-group">
                            <label>Highest Qualification</label>
                            <input type="text" name="highest_qualification" id="highest_qualification" class="form-control" placeholder="e.g. MD, MBBS, BSc Nursing" required>
                            <div class="error-msg" id="highest_qualificationError">Qualification is required.</div>
                        </div>
                        <div class="form-group">
                            <label>Total Years of Experience</label>
                            <input type="number" name="total_experience" id="total_experience" class="form-control" placeholder="Years" min="0" required>
                            <div class="error-msg" id="total_experienceError">Experience is required.</div>
                        </div>
                    </div>
                    <div class="form-group" id="resumeField">
                        <label id="resumeLabel">Upload Resume (PDF) <span id="resumeRequiredStar" style="color:red; display:none;">*</span></label>
                        <input type="file" name="resume" id="resume" class="form-control" accept=".pdf">
                        <div class="error-msg" id="resumeError">Please upload your resume in PDF format. (Mandatory for this role)</div>
                        <small class="helper-text" id="resumeHelp">Optional for Doctors/Nurses/Lab. Mandatory for Receptionist.</small>
                    </div>
                </div>

                <!-- SECTION 3: ROLE-SPECIFIC DETAILS (DYNAMIC) -->
                <div id="dynamicRoleFields" class="form-section">
                    <!-- Loaded via JS -->
                </div>

                <!-- SECTION 4: AVAILABILITY & PREFERENCES -->
                <div class="form-section">
                    <h2 class="section-title"><span>4</span> Availability & Preferences</h2>
                    <div class="grid-row">
                        <div class="form-group">
                            <label>Shift Preference</label>
                            <select name="shift_preference" id="shift_preference_pref" class="form-control" required>
                                <option value="">-- Choose Preference --</option>
                                <option value="Day">Day Shift</option>
                                <option value="Night">Night Shift</option>
                                <option value="Rotational">Rotational</option>
                            </select>
                            <div class="error-msg" id="shift_preference_prefError">Please select a shift preference.</div>
                        </div>
                        <div class="form-group">
                            <label>Expected Date of Joining</label>
                            <input type="date" name="date_of_joining" id="date_of_joining" class="form-control" required>
                            <div class="error-msg" id="date_of_joiningError">Date of joining is required.</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-submit">Submit Application</button>
                    <p style="text-align: center; margin-top: 15px; font-size: 0.85rem; color: #64748b;">
                        By submitting, you agree to HealCare's recruitment terms.
                    </p>
                </div>
            </div>

            <div id="initialHelp" style="text-align: center; padding: 40px; color: #94a3b8;">
                Please select your role above to begin the application form.
            </div>

        </form>

        <div class="footer-note">
            Already registered? <a href="login.php" style="color: var(--hc-blue); font-weight: 700;">Login here</a>
        </div>
    </div>

    <script>
        const roleSelect = document.getElementById('roleSelect');
        const staffTypeGroup = document.getElementById('staffTypeGroup');
        const staffTypeSelect = document.getElementById('staffTypeSelect');
        const formContent = document.getElementById('formContent');
        const initialHelp = document.getElementById('initialHelp');
        const dynamicRoleFields = document.getElementById('dynamicRoleFields');
        const applyForm = document.getElementById('applyForm');
        const resumeInput = document.getElementById('resume');

        roleSelect.addEventListener('change', () => {
            if (roleSelect.value === 'doctor') {
                staffTypeGroup.style.display = 'none';
                staffTypeSelect.required = false;
                showForm();
            } else if (roleSelect.value === 'staff') {
                staffTypeGroup.style.display = 'block';
                staffTypeSelect.required = true;
                hideForm();
            } else {
                staffTypeGroup.style.display = 'none';
                hideForm();
            }
        });

        staffTypeSelect.addEventListener('change', () => {
            if (staffTypeSelect.value) {
                showForm();
            } else {
                hideForm();
            }
        });

        function showForm() {
            formContent.style.display = 'block';
            initialHelp.style.display = 'none';
            renderRoleFields();
        }

        function hideForm() {
            formContent.style.display = 'none';
            initialHelp.style.display = 'block';
        }

        function renderRoleFields() {
            const role = roleSelect.value;
            const staffType = staffTypeSelect.value;
            let html = '<h2 class="section-title"><span>3</span> Role-Specific Details</h2>';

            if (role === 'doctor') {
                html += `
                    <div class="grid-row">
                        <div class="form-group">
                            <label>Medical Specialization</label>
                            <input type="text" name="specialization" id="specialization" class="form-control" placeholder="e.g. Cardiology" required>
                            <div class="error-msg" id="specializationError">Specialization is required.</div>
                        </div>
                        <div class="form-group">
                            <label>Designation</label>
                            <select name="designation" id="designation" class="form-control" required>
                                <option value="Junior">Junior Doctor</option>
                                <option value="Senior">Senior Doctor</option>
                                <option value="Consultant">Consultant</option>
                            </select>
                            <div class="error-msg" id="designationError">Please select a designation.</div>
                        </div>
                    </div>
                    <div class="grid-row">
                        <div class="form-group">
                            <label>Department</label>
                            <select name="dept_preference" id="dept_preference" class="form-control" required>
                                <option value="">-- Select Department --</option>
                                <option value="General Medicine / Cardiovascular">General Medicine / Cardiovascular</option>
                                <option value="Gynecology">Gynecology</option>
                                <option value="Orthopedics">Orthopedics (Bones)</option>
                                <option value="ENT">ENT</option>
                                <option value="Ophthalmology">Ophthalmology</option>
                                <option value="Dermatology">Dermatology</option>
                            </select>
                            <div class="error-msg" id="dept_preferenceError">Please select a department.</div>
                        </div>
                        <div class="form-group">
                            <label>Medical Registration Number</label>
                            <input type="text" name="license_number" id="license_number" class="form-control" placeholder="MCI Registration No." required>
                            <div class="error-msg" id="license_numberError">License number is required.</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Years of Clinical Experience</label>
                        <input type="number" name="relevant_experience" id="relevant_experience" class="form-control" placeholder="Number of years in clinic" required min="0">
                        <div class="error-msg" id="relevant_experienceError">Please enter valid experience in years.</div>
                    </div>
                `;
            } else {
                switch(staffType) {
                    case 'nurse':
                        html += `
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Nursing Qualification</label>
                                    <select name="qualification_details" id="qualification_details" class="form-control" required>
                                        <option value="GNM">GNM</option>
                                        <option value="BSc Nursing">BSc Nursing</option>
                                    </select>
                                    <div class="error-msg" id="qualification_detailsError">Qualification is required.</div>
                                </div>
                                <div class="form-group">
                                    <label>Preferred Department</label>
                                    <input type="text" name="dept_preference" id="dept_preference" class="form-control" placeholder="e.g. ICU / Surgery" required>
                                    <div class="error-msg" id="dept_preferenceError">Department is required.</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Clinical Experience (Years)</label>
                                <input type="number" name="relevant_experience" id="relevant_experience" class="form-control" required min="0">
                                <div class="error-msg" id="relevant_experienceError">Experience is required.</div>
                            </div>
                        `;
                        break;
                    case 'pharmacist':
                        html += `
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Pharmacy Qualification</label>
                                    <select name="qualification_details" id="qualification_details" class="form-control" required>
                                        <option value="D.Pharm">D.Pharm</option>
                                        <option value="B.Pharm">B.Pharm</option>
                                    </select>
                                    <div class="error-msg" id="qualification_detailsError">Qualification is required.</div>
                                </div>
                                <div class="form-group">
                                    <label>Hospital Pharmacy Experience (Years)</label>
                                    <input type="number" name="relevant_experience" id="relevant_experience" class="form-control" required min="0">
                                    <div class="error-msg" id="relevant_experienceError">Experience is required.</div>
                                </div>
                            </div>
                        `;
                        break;
                    case 'lab_staff':
                        html += `
                            <div class="form-group">
                                <label>Lab Type</label>
                                <select name="specialization" id="specialization" class="form-control" required>
                                    <option value="Blood">Blood Bank</option>
                                    <option value="X-Ray">X-Ray / Radiology</option>
                                    <option value="Pathology">Pathology</option>
                                </select>
                                <div class="error-msg" id="specializationError">Please select lab type.</div>
                            </div>
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Qualification</label>
                                    <input type="text" name="qualification_details" id="qualification_details" class="form-control" placeholder="e.g. MLT" required>
                                    <div class="error-msg" id="qualification_detailsError">Qualification is required.</div>
                                </div>
                                <div class="form-group">
                                    <label>Laboratory Experience (Years)</label>
                                    <input type="number" name="relevant_experience" id="relevant_experience" class="form-control" required min="0">
                                    <div class="error-msg" id="relevant_experienceError">Experience is required.</div>
                                </div>
                            </div>
                        `;
                        break;
                    case 'receptionist':
                        html += `
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Educational Qualification</label>
                                    <input type="text" name="qualification_details" id="qualification_details" class="form-control" required>
                                    <div class="error-msg" id="qualification_detailsError">Qualification is required.</div>
                                </div>
                                <div class="form-group">
                                    <label>Languages Known</label>
                                    <input type="text" name="languages_known" id="languages_known" class="form-control" placeholder="e.g. English, Hindi" required>
                                    <div class="error-msg" id="languages_knownError">Languages known is required.</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Front Desk Experience (Years)</label>
                                <input type="number" name="relevant_experience" id="relevant_experience" class="form-control" required min="0">
                                <div class="error-msg" id="relevant_experienceError">Experience is required.</div>
                            </div>
                        `;
                        break;
                    case 'canteen_staff':
                        html += `
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Job Role</label>
                                    <select name="canteen_job_role" id="canteen_job_role" class="form-control" required>
                                        <option value="Cook">Cook</option>
                                        <option value="Helper">Helper</option>
                                        <option value="Cashier">Cashier</option>
                                    </select>
                                    <div class="error-msg" id="canteen_job_roleError">Please select a job role.</div>
                                </div>
                                <div class="form-group">
                                    <label>Basic Education</label>
                                    <select name="qualification_details" id="qualification_details" class="form-control">
                                        <option value="No formal education">No formal education</option>
                                        <option value="Primary School">Primary School</option>
                                        <option value="10th Pass (SSLC)">10th Pass (SSLC)</option>
                                        <option value="12th Pass">12th Pass</option>
                                        <option value="Basic Hotel Management / Catering Course">Basic Hotel Management / Catering Course</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Work Experience (If Any)</label>
                                    <input type="text" name="relevant_experience" id="relevant_experience" class="form-control" placeholder="e.g. 2 years in hotel kitchen">
                                </div>
                                <div class="form-group">
                                    <label>Cooking Experience <span class="helper-text">(For Cook role)</span></label>
                                    <input type="text" name="cooking_exp" id="cooking_exp" class="form-control" placeholder="e.g. Indian, Chinese, Continental">
                                </div>
                            </div>
                        `;
                        break;
                }
            }

            dynamicRoleFields.innerHTML = html;
            
            // Handle conditional display of professional fields for canteen staff
            const professionalSection = document.getElementById('professionalSection');
            const commonProfFields = document.getElementById('commonProfessionalFields');
            const resumeField = document.getElementById('resumeField');
            const highestQual = document.getElementById('highest_qualification');
            const totalExp = document.getElementById('total_experience');
            
            if (staffType === 'canteen_staff') {
                // Hide entire professional section for canteen staff
                professionalSection.style.display = 'none';
                highestQual.required = false;
                totalExp.required = false;
            } else {
                // Show fields for other roles
                professionalSection.style.display = 'block';
                commonProfFields.style.display = 'grid';
                resumeField.style.display = 'block';
                highestQual.required = true;
                totalExp.required = true;
            }
            
            // Handle conditional resume requirement
            const resumeInput = document.getElementById('resume');
            const resumeStar = document.getElementById('resumeRequiredStar');
            const resumeHelp = document.getElementById('resumeHelp');
            
            if (staffType === 'receptionist') {
                resumeInput.required = true;
                resumeStar.style.display = 'inline';
                resumeHelp.textContent = "Resume is MANDATORY for this role.";
            } else if (staffType === 'canteen_staff') {
                resumeInput.required = false;
                resumeStar.style.display = 'none';
            } else {
                resumeInput.required = false;
                resumeStar.style.display = 'none';
                resumeHelp.textContent = "Recommended but optional for this role.";
            }

            attachValidation();
        }

        // Validation Logic
        function validateField(input) {
            const id = input.id;
            const val = input.value.trim();
            const error = document.getElementById(id + 'Error');
            
            if(!error) return true;

            let isValid = true;
            let errorMessage = "This field is required.";

            // Basic Required Check
            if (input.hasAttribute('required') && !val) {
                isValid = false;
            } 
            // Specific Field Validations
            else if (id === 'fullname') {
                // Strictly no digits or special chars
                if (/[^a-zA-Z\s]/.test(val)) {
                    isValid = false;
                    errorMessage = "Name must not contain numbers or special characters.";
                } else if (val.length > 0 && val.length < 3) {
                     isValid = false;
                     errorMessage = "Name must be at least 3 characters.";
                }
            }
            else if (id === 'email') {
                // Robust email regex
                const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                if (val.length > 0 && !emailPattern.test(val)) {
                    isValid = false;
                    errorMessage = "Please enter a valid email address.";
                }
            } 
            else if (id === 'phone') {
                if (val.length > 0 && !/^\d{10}$/.test(val)) {
                    isValid = false;
                    errorMessage = "Phone number must be exactly 10 digits.";
                }
            }
            else if (id === 'total_experience' || id === 'relevant_experience') {
                if (val < 0) {
                    isValid = false;
                    errorMessage = "Experience cannot be negative.";
                }
            }
            else if (id === 'resume' && input.files[0]) {
                if (input.files[0].type !== 'application/pdf') {
                    isValid = false;
                    errorMessage = "Only PDF files are allowed.";
                }
            }

            if(!isValid) {
                input.classList.add('invalid');
                error.textContent = errorMessage;
                error.classList.add('visible');
            } else {
                input.classList.remove('invalid');
                error.classList.remove('visible');
            }
            return isValid;
        }

        function attachValidation() {
            const fields = applyForm.querySelectorAll('input, select, textarea');
            fields.forEach(f => {
                f.addEventListener('blur', () => validateField(f));
                // Live validation on input
                f.addEventListener('input', () => validateField(f));
            });
        }

        applyForm.addEventListener('submit', (e) => {
            let formValid = true;
            applyForm.querySelectorAll('[required]').forEach(f => {
                if (!validateField(f)) formValid = false;
            });

            if (!formValid) {
                e.preventDefault();
                alert('Please check the form for mistakes before submitting.');
            }
        });

    </script>
</body>
</html>
