<?php
session_start();
include 'includes/db_connect.php';

// Mock Doctors Data (Consistent with other pages)
$doctors = [
    ['id' => 1, 'name' => 'Dr. Abraham Mohan', 'dept' => 'General Surgery', 'exp' => '15 Years', 'img' => 'images/doctor-1.jpg', 'qual' => 'MBBS, MS'],
    ['id' => 2, 'name' => 'Dr. Suresh Kumar', 'dept' => 'Orthopedics', 'exp' => '12 Years', 'img' => 'images/doctor-2.jpg', 'qual' => 'MBBS, D.Ortho'],
    ['id' => 3, 'name' => 'Dr. Arjun Reddy', 'dept' => 'Cardiology', 'exp' => '10 Years', 'img' => 'images/doctor-3.jpg', 'qual' => 'MBBS, MD (Cardio)'],
    ['id' => 4, 'name' => 'Dr. Lakshmi Devi', 'dept' => 'Ophthalmology', 'exp' => '8 Years', 'img' => 'images/doctor-4.jpg', 'qual' => 'MBBS, DOMS'],
    ['id' => 5, 'name' => 'Dr. Vikram Singh', 'dept' => 'Dermatology', 'exp' => '9 Years', 'img' => 'images/doctor-5.jpg', 'qual' => 'MBBS, MD (Derma)'],
    ['id' => 6, 'name' => 'Dr. Rajesh Khanna', 'dept' => 'ENT', 'exp' => '14 Years', 'img' => 'images/doctor-6.jpg', 'qual' => 'MBBS, MS (ENT)'],
    ['id' => 7, 'name' => 'Dr. Meera Krishnan', 'dept' => 'Neurology', 'exp' => '11 Years', 'img' => 'images/doctor-7.jpg', 'qual' => 'MBBS, DM (Neuro)'],
    ['id' => 8, 'name' => 'Dr. Akshay Kumar', 'dept' => 'Nephrology', 'exp' => '7 Years', 'img' => 'images/doctor-8.jpg', 'qual' => 'MBBS, MD'],
    ['id' => 9, 'name' => 'Dr. Ananya Iyer', 'dept' => 'Pediatrics', 'exp' => '6 Years', 'img' => 'images/doctor-9.jpg', 'qual' => 'MBBS, MD (Ped)'],
    ['id' => 10, 'name' => 'Dr. Sneha Gupta', 'dept' => 'Gynecology', 'exp' => '13 Years', 'img' => 'images/doctor-10.jpg', 'qual' => 'MBBS, DGO']
];

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
        <form action="booking_success.php" method="POST" onsubmit="return validateCaptcha()">
            <input type="hidden" name="token" value="<?php echo $token_number; ?>">
            <input type="hidden" name="doctor_name" value="<?php echo $selected_doc ? htmlspecialchars($selected_doc['name']) : ''; ?>">
            
            <!-- Filters -->
            <div class="top-filters">
                <div class="filter-group">
                    <label>Department</label>
                    <select class="filter-control" name="dept" onchange="window.location.href='?dept='+this.value">
                        <option value="">Select Department</option>
                        <option value="Cardiology" <?php echo ($pre_dept == 'Cardiology') ? 'selected' : ''; ?>>Cardiology</option>
                        <option value="Neurology" <?php echo ($pre_dept == 'Neurology') ? 'selected' : ''; ?>>Neurology</option>
                        <option value="Orthopedics" <?php echo ($pre_dept == 'Orthopedics') ? 'selected' : ''; ?>>Orthopedics</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Doctor</label>
                    <select class="filter-control" name="doctor_id" onchange="window.location.href='?doctor_id='+this.value+'&dept=<?php echo $pre_dept; ?>'">
                        <option value="">Select Doctor</option>
                        <?php foreach($doctors as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo ($pre_doc_id == $d['id']) ? 'selected' : ''; ?>>
                                <?php echo $d['name']; ?>
                            </option>
                        <?php endforeach; ?>
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
                        <div style="color:var(--primary-cyan); font-weight:600; text-transform:uppercase;"><?php echo $selected_doc['dept']; ?></div>
                    </div>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600; margin-bottom:10px; font-size:0.9rem;">Consulting Days</div>
                    <table class="consult-table">
                        <thead><tr><th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th></tr></thead>
                        <tbody><tr><td>10-12</td><td>10-12</td><td>10-12</td><td>10-12</td><td>10-12</td><td>16-17</td></tr></tbody>
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
                <h3 style="margin: 40px 0 20px; color: #2c3e50;">Patient Details</h3>

                <div class="reg-toggle">
                    <label>Already registered patient<span class="required">*</span></label>
                    <input type="radio" name="reg_status" value="yes" id="reg_yes" onchange="toggleRegForm()" checked>
                    <label for="reg_yes">Yes</label>
                    <input type="radio" name="reg_status" value="no" id="reg_no" onchange="toggleRegForm()">
                    <label for="reg_no">No</label>
                </div>

                <div class="reg-form-container">
                    <!-- Registered -->
                    <div id="form-registered">
                        <div class="info-text">Enter OP number and registered phone number to proceed</div>
                        <div class="form-grid">
                            <div><label>OP Number <span class="required">*</span></label><input type="text" name="op_number" class="form-control-input"></div>
                            <div><label>Mobile Number <span class="required">*</span></label><input type="tel" name="reg_mobile" class="form-control-input"></div>
                        </div>
                        <div><label>Email</label><input type="email" name="reg_email" class="form-control-input"></div>
                    </div>

                    <!-- New -->
                    <div id="form-new" class="hidden">
                        <div class="form-grid">
                            <div><label>First Name <span class="required">*</span></label><input type="text" name="first_name" class="form-control-input"></div>
                            <div><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" class="form-control-input"></div>
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
