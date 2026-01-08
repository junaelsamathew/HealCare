<?php
session_start();
include 'includes/db_connect.php';

// Mock Doctors Data (Consistent with other pages)
// Fetch Doctors from DB
$doctors = [];
$dept_filter = isset($_GET['dept']) ? trim(urldecode($_GET['dept'])) : '';

// Base Query
$sql = "SELECT d.user_id as id, r.name, d.department as dept, d.experience as exp, d.qualification as qual, r.profile_photo as img, d.consultation_fee 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        JOIN registrations r ON u.registration_id = r.registration_id";

// Apply Filter
if (!empty($dept_filter) && $dept_filter !== 'Select Department') {
    $safe_dept = mysqli_real_escape_string($conn, $dept_filter);
    $sql .= " WHERE d.department = '$safe_dept'";
}

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if(empty($row['img'])) {
            $row['img'] = 'images/doctor-' . (rand(1, 10)) . '.jpg'; 
        }
        $doctors[] = $row;
    }
} else {
    // Debug info in case of no results
    // echo "<!-- Query: $sql -->";
}

// Handle Query Params
$pre_doc_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$pre_dept = isset($_GET['dept']) ? $_GET['dept'] : '';

$selected_doc = null;
if ($pre_doc_id) {
    foreach ($doctors as $d) {
        if ($d['id'] == $pre_doc_id) {
            $selected_doc = $d;
            break;
        }
    }
}

// Fixed Token Number for Demo (Or Random)
$token_number = rand(10, 50);

// Fetch Logged-in User Data
$user_data = [];
$is_logged_in = false;
if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $is_logged_in = true;
    // Fetch patient code and details
    $q = $conn->query("SELECT p.patient_code, r.phone, r.email, r.name 
                       FROM users u 
                       JOIN registrations r ON u.registration_id = r.registration_id 
                       LEFT JOIN patient_profiles p ON u.user_id = p.user_id 
                       WHERE u.user_id = $uid");
    if($q->num_rows > 0) {
        $user_data = $q->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Appointment - HealCare</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-orange: #f26522;
            --primary-cyan: #00aeef;
            --bg-light: #f0f8ff;
            --text-dark: #333;
        }
        
        body { margin: 0; font-family: 'Poppins', sans-serif; background: #f9f9f9; }
        
        header { background: #fff; padding: 15px 0; border-bottom: 1px solid #eee; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* Token Alert */
        .token-alert {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 1000px;
            text-align: center;
            font-size: 1.1rem;
            display: none; /* Shown after slot selection */
        }
        .token-number { font-size: 1.5rem; font-weight: 700; color: #2c3e50; }

        .booking-wrapper {
            background: #fff;
            max-width: 1000px;
            margin: 20px auto 50px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        /* Top Filters */
        .top-filters {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .filter-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: #555; }
        .filter-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.95rem;
            background: #fff;
        }

        /* Doctor Display */
        .doctor-display {
            display: flex;
            gap: 20px;
            background: #fff;
            border: 1px solid #eee;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .doc-profile-left { display: flex; align-items: center; gap: 20px; flex: 0 0 350px; }
        .doc-img { width: 100px; height: 100px; object-fit: cover; border-radius: 6px; }
        .doc-details h3 { margin: 0 0 5px; color: #2c3e50; font-size: 1.2rem; }
        .doc-qual { color: #666; font-size: 0.9rem; margin-bottom: 5px; }

        /* Consulting Table */
        .consult-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .consult-table th { background: #f8f9fa; border: 1px solid #eee; padding: 8px; font-weight: 600; text-align: center; }
        .consult-table td { border: 1px solid #eee; padding: 8px; text-align: center; color: #555; }

        /* Time Slots */
        .btn-time-slot {
            background: var(--primary-orange);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
        .time-slots-panel { display: none; margin-top: 20px; padding: 20px; background: #fffbf8; border: 1px solid #ffeocb; border-radius: 8px; }
        .session-title { font-weight: 600; color: #555; margin-bottom: 10px; display: block; font-size: 0.9rem; margin-top: 10px;}
        .slot-chip {
            display: inline-block;
            padding: 8px 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px;
            cursor: pointer;
        }
        .slot-chip.selected { background: var(--primary-orange); color: white; border-color: var(--primary-orange); }

        /* Patient Form */
        .reg-toggle { margin-bottom: 25px; }
        .reg-toggle label { margin-right: 20px; font-weight: 500; cursor: pointer; }
        .reg-form-container { background: #e6f7ff; padding: 30px; border-radius: 10px; margin-top: 20px;}
        .info-text { color: #00aeef; font-weight: 500; margin-bottom: 20px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-control-input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; }

        /* Captcha */
        .captcha-box {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            display: inline-block;
            margin-top: 20px;
        }
        .captcha-img {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.5rem;
            letter-spacing: 5px;
            background: #eee;
            padding: 10px 20px;
            margin-right: 15px;
            text-decoration: line-through;
        }
        
        .btn-continue {
            background: var(--primary-orange);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            box-shadow: 0 4px 10px rgba(242, 101, 34, 0.3);
        }

        .hidden { display: none; }
        .required { color: red; }
    </style>
</head>
<body>

    <header>
        <div class="container" style="display:flex; justify-content:space-between; align-items:center;">
            <a href="index.php" style="font-size:1.5rem; font-weight:800; color:#1e40af; text-decoration:none;">HEALCARE</a>
            <a href="book_appointment.php" style="color:#666; text-decoration:none;"><i class="fas fa-times"></i> Cancel</a>
        </div>
    </header>

    <div id="tokenMsg" class="token-alert">
        Your token number is <span class="token-number"><?php echo $token_number; ?></span><br>
        Please fill in the details below to complete the booking.
    </div>

    <div class="booking-wrapper">
        <form action="process_booking.php" method="POST" onsubmit="return validateCaptcha()">
            <input type="hidden" name="token" value="<?php echo $token_number; ?>">
            <input type="hidden" name="doctor_name" value="<?php echo $selected_doc ? htmlspecialchars($selected_doc['name']) : ''; ?>">
            
            <!-- Filters -->
            <div class="top-filters">
                <div class="filter-group">
                    <label>Department</label>
                    <select class="filter-control" name="dept" onchange="window.location.href='?dept='+this.value">
                            <option value="">Select Department</option>
                            <option value="General Medicine / Cardiovascular" <?php if($pre_dept == 'General Medicine / Cardiovascular') echo 'selected'; ?>>General Medicine / Cardiovascular</option>
                            <option value="Gynecology" <?php if($pre_dept == 'Gynecology') echo 'selected'; ?>>Gynecology</option>
                            <option value="Orthopedics (Bones)" <?php if($pre_dept == 'Orthopedics (Bones)') echo 'selected'; ?>>Orthopedics (Bones)</option>
                            <option value="ENT" <?php if($pre_dept == 'ENT') echo 'selected'; ?>>ENT</option>
                            <option value="Ophthalmology" <?php if($pre_dept == 'Ophthalmology') echo 'selected'; ?>>Ophthalmology</option>
                            <option value="Dermatology" <?php if($pre_dept == 'Dermatology') echo 'selected'; ?>>Dermatology</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Doctor</label>
                    <select class="filter-control" name="doctor_id" onchange="window.location.href='?doctor_id='+this.value+'&dept=<?php echo urlencode($dept_filter); ?>'">
                        <option value="">Select Doctor</option>
                        <?php if (!empty($doctors)): ?>
                            <?php foreach($doctors as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($pre_doc_id == $d['id']) ? 'selected' : ''; ?>>
                                    <?php echo $d['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No doctors found in this department</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" class="filter-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <?php if($selected_doc): ?>
            <!-- Doctor Info -->
            <div class="doctor-display">
                <div class="doc-profile-left">
                    <img src="<?php echo $selected_doc['img']; ?>" class="doc-img" onerror="this.src='images/doctor-1.jpg'">
                    <div class="doc-details">
                        <h3><?php echo $selected_doc['name']; ?></h3>
                        <div class="doc-qual"><?php echo $selected_doc['qual']; ?></div>
                        <div style="color:var(--primary-cyan); font-weight:600; text-transform:uppercase; margin-bottom: 5px;"><?php echo $selected_doc['dept']; ?></div>
                        <div style="background: #e0f2fe; color: #0369a1; padding: 5px 12px; border-radius: 15px; display: inline-block; font-size: 0.9rem; font-weight: 700;">
                            <i class="fas fa-money-bill-wave" style="margin-right: 5px;"></i> Consultation Fee: ₹<?php echo number_format($selected_doc['consultation_fee'], 0); ?>
                        </div>
                    </div>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600; margin-bottom:10px; font-size:1rem; color:#2c3e50;">Consulting Days</div>
                    <table class="consult-table">
                        <thead>
                            <tr>
                                <th>MONDAY</th>
                                <th>TUESDAY</th>
                                <th>WEDNESDAY</th>
                                <th>THURSDAY</th>
                                <th>FRIDAY</th>
                                <th>SATURDAY</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>10:00 TO 12:30</td>
                                <td>10:00 TO 12:30</td>
                                <td>10:00 TO 12:30</td>
                                <td>10:00 TO 12:30</td>
                                <td>10:00 TO 12:30</td>
                                <td>16:00 TO 17:00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="button" class="btn-time-slot" onclick="toggleSlots()">Select Time Slot</button>

            <div class="time-slots-panel" id="slotsPanel">
                <span class="session-title"><i class="fas fa-sun" style="color:#ffa500;"></i> Morning Session</span>
                <div class="slots-container">
                    <div class="slot-chip" onclick="selectSlot(this)">09:00 AM</div>
                    <div class="slot-chip" onclick="selectSlot(this)">09:30 AM</div>
                    <div class="slot-chip" onclick="selectSlot(this)">10:00 AM</div>
                    <div class="slot-chip" onclick="selectSlot(this)">10:30 AM</div>
                    <div class="slot-chip" onclick="selectSlot(this)">11:00 AM</div>
                </div>
                
                <span class="session-title"><i class="fas fa-moon" style="color:#6d28d9;"></i> Evening Session</span>
                <div class="slots-container">
                    <div class="slot-chip" onclick="selectSlot(this)">04:00 PM</div>
                    <div class="slot-chip" onclick="selectSlot(this)">04:30 PM</div>
                    <div class="slot-chip" onclick="selectSlot(this)">05:00 PM</div>
                    <div class="slot-chip" onclick="selectSlot(this)">05:30 PM</div>
                </div>
                <input type="hidden" name="time_slot" id="selectedTimeSlot" required>
            </div>

            <!-- Patient Details Area (Hidden until slot selected) -->
            <div id="patientDetailsArea" style="display:none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 40px 0 20px;">
                    <h3 style="margin: 0; color: #2c3e50;">Patient Details</h3>
                    <button type="button" onclick="autofillDemo()" style="background:#f0f7ff; border:1px solid #00aeef; color:#00aeef; padding:8px 15px; border-radius:20px; font-size:0.85rem; font-weight:600; cursor:pointer; transition: all 0.3s;">
                        <i class="fas fa-magic" style="margin-right: 5px;"></i> Autofill for Demo
                    </button>
                </div>

                <div class="reg-toggle" <?php if($is_logged_in) echo 'style="display:none;"'; ?>>
                    <label>Already registered patient<span class="required">*</span></label>
                    <input type="radio" name="reg_status" value="yes" id="reg_yes" onchange="toggleRegForm()" checked>
                    <label for="reg_yes">Yes</label>
                    <input type="radio" name="reg_status" value="no" id="reg_no" onchange="toggleRegForm()">
                    <label for="reg_no">No</label>
                </div>

                <div class="reg-form-container">
                    <!-- Registered -->
                    <div id="form-registered">
                        <div class="info-text">
                            <?php if($is_logged_in): ?>
                                <b>Welcome back, <?php echo htmlspecialchars($user_data['name'] ?? 'Patient'); ?>!</b><br>
                                Your details have been auto-filled.
                            <?php else: ?>
                                Enter your Registered Mobile Number. OP Number is optional.
                            <?php endif; ?>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label>OP Number (Optional)</label>
                                <input type="text" name="op_number" class="form-control-input" value="<?php echo $user_data['patient_code'] ?? ''; ?>" <?php if($is_logged_in) echo 'readonly style="background:#f0f0f0;"'; ?>>
                            </div>
                            <div>
                                <label>Mobile Number <span class="required">*</span></label>
                                <input type="tel" name="reg_mobile" class="form-control-input" value="<?php echo $user_data['phone'] ?? ''; ?>" <?php if($is_logged_in) echo 'readonly style="background:#f0f0f0;"'; ?>>
                            </div>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="reg_email" class="form-control-input" value="<?php echo $user_data['email'] ?? ''; ?>" <?php if($is_logged_in) echo 'readonly style="background:#f0f0f0;"'; ?>>
                        </div>
                    </div>

                    <!-- New -->
                    <div id="form-new" class="hidden">
                        <div class="form-grid">
                            <div><label>First Name <span class="required">*</span></label><input type="text" name="first_name" class="form-control-input"></div>
                            <div><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" class="form-control-input"></div>
                        </div>
                        <div class="form-grid">
                            <div><label>Email Address <span class="required">*</span></label><input type="email" name="email" class="form-control-input"></div>
                            <div><label>Phone Number <span class="required">*</span></label><input type="tel" name="phone" class="form-control-input"></div>
                        </div>
                        <div class="form-grid">
                            <div><label>Address <span class="required">*</span></label><input type="text" name="address" class="form-control-input"></div>
                            <div><label>Locality</label><input type="text" name="locality" class="form-control-input"></div>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label>Gender <span class="required">*</span></label><br>
                                <input type="radio" name="gender" value="Male"> Male
                                <input type="radio" name="gender" value="Female"> Female
                            </div>
                            <div><label>Age <span class="required">*</span></label><input type="number" name="age" class="form-control-input"></div>
                        </div>
                    </div>
                    
                    <!-- Captcha -->
                    <div class="captcha-box">
                        <label style="display:block; margin-bottom:5px;">Security Check <span class="required">*</span></label>
                        <span class="captcha-img">5692</span>
                        <input type="text" id="captchaInput" placeholder="Enter code" style="padding:10px; width:100px; border:1px solid #ccc; border-radius:4px;">
                        <small style="display:block; color:#666; margin-top:5px;">Please enter the numeric code above.</small>
                    </div>

                    <div style="margin-top:20px;">
                        <input type="checkbox" required id="terms"> <label for="terms">I agree to the Terms and Conditions.</label>
                    </div>

                    <button type="submit" class="btn-continue">Continue</button>
                </div>
            </div>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:#aaa;">Please select a doctor to proceed.</div>
            <?php endif; ?>
        </form>
    </div>

    <script>
        function toggleSlots() {
            document.getElementById('slotsPanel').style.display = 'block';
        }
        function selectSlot(el) {
            document.querySelectorAll('.slot-chip').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('selectedTimeSlot').value = el.innerText;
            
            // Show Token and Details
            document.getElementById('tokenMsg').style.display = 'block';
            document.getElementById('patientDetailsArea').style.display = 'block';
            
            // Smooth Scroll
            document.getElementById('tokenMsg').scrollIntoView({behavior: 'smooth'});
        }
        function toggleRegForm() {
            if(document.getElementById('reg_yes').checked) {
                document.getElementById('form-registered').classList.remove('hidden');
                document.getElementById('form-new').classList.add('hidden');
            } else {
                document.getElementById('form-registered').classList.add('hidden');
                document.getElementById('form-new').classList.remove('hidden');
            }
        }
        function autofillDemo() {
            // Switch to 'No' (New Patient)
            document.getElementById('reg_no').click();
            
            // Fill Fields
            const data = {
                'first_name': 'John',
                'last_name': 'Doe',
                'email': 'john.doe@example.com',
                'phone': '9876543210',
                'address': '123 Health Ave',
                'locality': 'HealCare Heights',
                'age': '30',
                'captchaInput': '5692'
            };
            
            for (let name in data) {
                let el = document.getElementsByName(name)[0] || document.getElementById(name);
                if (el) el.value = data[name];
            }
            
            // Gender & Terms
            document.querySelector('input[name="gender"][value="Male"]').checked = true;
            document.getElementById('terms').checked = true;
            
            // Visual feedback
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Filled!';
            btn.style.background = '#e6fffa';
            btn.style.borderColor = '#38b2ac';
            btn.style.color = '#38b2ac';
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '#f0f7ff';
                btn.style.borderColor = '#00aeef';
                btn.style.color = '#00aeef';
            }, 2000);
        }

        function validateCaptcha() {
            var val = document.getElementById('captchaInput').value;
            if(val !== '5692') {
                alert('Invalid Captcha Code!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
