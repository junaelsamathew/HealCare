<?php
session_start();
include 'includes/db_connect.php';

// Check auth
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    $redirect = urlencode(basename($_SERVER['PHP_SELF']));
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= urlencode('?' . $_SERVER['QUERY_STRING']);
    }
    header("Location: login.php?redirect=$redirect");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Mock Doctors Data (Consistent with other pages)
// Fetch Doctors from DB
$doctors = [];
$dept_filter = isset($_GET['dept']) ? trim($_GET['dept']) : '';

// Base Query
$sql = "SELECT d.user_id as id, r.name, d.department as dept, d.experience as exp, d.qualification as qual, r.profile_photo as img, d.consultation_fee 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        JOIN registrations r ON u.registration_id = r.registration_id";

// Apply Filter
if (!empty($dept_filter) && $dept_filter !== 'Select Department') {
    $safe_dept = mysqli_real_escape_string($conn, $dept_filter);
    $sql .= " WHERE TRIM(d.department) = TRIM('$safe_dept')";
}

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if(empty($row['img'])) {
            $row['img'] = 'images/doctor-' . (rand(1, 10)) . '.jpg'; 
        }
        $doctors[] = $row;
    }
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
$is_logged_in = true; // Always true here
if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
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
    <title>Booking Details - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        /* Specific Styles for Form Elements matching Dashboard Theme */
        .token-alert {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-size: 1rem;
            display: none;
        }
        .token-number { font-size: 1.4rem; font-weight: 700; color: #fff; }

        .booking-wrapper {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            padding: 30px;
            border-radius: 12px;
        }

        .top-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 8px;
        }
        .filter-group label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; color: var(--text-gray); }
        .filter-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            background: #0f172a;
            color: white;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .filter-control:focus { outline: none; border-color: var(--primary-blue); }

        .doctor-display {
            display: flex;
            gap: 20px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .doc-profile-left { display: flex; align-items: center; gap: 20px; flex: 1; min-width: 300px; }
        .doc-img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; }
        .doc-details h3 { margin: 0 0 5px; color: white; font-size: 1.1rem; }
        .doc-qual { color: var(--text-gray); font-size: 0.85rem; margin-bottom: 5px; }

        .consult-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-top: 10px; }
        .consult-table th { background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 8px; font-weight: 600; text-align: center; color: var(--text-gray); }
        .consult-table td { border: 1px solid var(--border-color); padding: 8px; text-align: center; color: white; }

        .btn-time-slot {
            background: var(--primary-blue);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            display: block;
            width: 100%;
            max-width: 200px;
            transition: 0.3s;
        }
        .btn-time-slot:hover { background: #2563eb; }

        .time-slots-panel { display: none; margin-top: 20px; padding: 20px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-color); border-radius: 8px; }
        .session-title { font-weight: 600; color: var(--text-gray); margin: 15px 0 10px; display: block; font-size: 0.9rem; }
        
        .slot-chip {
            display: inline-block;
            padding: 8px 15px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin: 5px;
            cursor: pointer;
            color: var(--text-light);
            transition: 0.3s;
            font-size: 0.85rem;
        }
        .slot-chip:hover { border-color: var(--primary-blue); }
        .slot-chip.selected { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }

        .reg-form-container { background: rgba(0,0,0,0.2); padding: 30px; border-radius: 12px; margin-top: 20px; border: 1px solid var(--border-color); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        
        .form-control-input { 
            width: 100%; padding: 10px; 
            border: 1px solid var(--border-color); 
            border-radius: 6px; 
            background: #0f172a; 
            color: white; 
        }
        
        .btn-continue {
            background: #10b981;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: 0.3s;
        }
        .btn-continue:hover { background: #059669; }

        .captcha-box {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            display: inline-block;
            margin-top: 20px;
        }
        .captcha-img {
            font-family: monospace;
            font-weight: bold;
            font-size: 1.2rem;
            letter-spacing: 3px;
            background: #fff;
            color: #333;
            padding: 5px 15px;
            border-radius: 4px;
            margin-right: 15px;
            text-decoration: line-through;
        }

        .hidden { display: none; }
        .required { color: #ef4444; }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-phone-alt"></i></div>
                <div class="info-details"><span class="info-label">EMERGENCY</span><span class="info-value">(+254) 717 783 146</span></div>
            </div>
        </div>
    </header>

    <header class="secondary-header">
        <div class="brand-section"><div class="brand-icon">+</div><div class="brand-name">HealCare</div></div>
        <div class="user-controls"><span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($username); ?></strong></span><a href="logout.php" class="btn-logout">Log Out</a></div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link">Dashboard</a>
                <a href="book_appointment.php" class="nav-link active">Book Appointment</a>
                <a href="my_appointments.php" class="nav-link">My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Appointment Form</h1>
                <p>Select a doctor and schedule your visit</p>
            </div>

            <div id="tokenMsg" class="token-alert">
                <i class="fas fa-ticket-alt"></i> Your token number is <span class="token-number"><?php echo $token_number; ?></span><br>
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
                                    <option value="Pediatrics" <?php if($pre_dept == 'Pediatrics') echo 'selected'; ?>>Pediatrics</option>
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
                                    <option value="" disabled>No doctors for this department</option>
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
                                <div style="color:var(--primary-blue); font-weight:600; text-transform:uppercase; margin-bottom: 5px;"><?php echo $selected_doc['dept']; ?></div>
                                <div style="background: rgba(59, 130, 246, 0.1); color: var(--primary-blue); padding: 5px 12px; border-radius: 15px; display: inline-block; font-size: 0.85rem; font-weight: 700;">
                                    Fee: ₹<?php echo number_format($selected_doc['consultation_fee'], 0); ?>
                                </div>
                            </div>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600; margin-bottom:10px; font-size:0.9rem; color:white;">Consulting Days</div>
                            <table class="consult-table">
                                <thead>
                                    <tr>
                                        <th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>10 - 12</td><td>10 - 12</td><td>10 - 12</td><td>10 - 12</td><td>10 - 12</td><td>16 - 17</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <button type="button" class="btn-time-slot" onclick="toggleSlots()">Select Time Slot</button>

                    <div class="time-slots-panel" id="slotsPanel">
                        <span class="session-title"><i class="fas fa-sun" style="color:#f59e0b;"></i> Morning Session</span>
                        <div class="slots-container">
                            <div class="slot-chip" onclick="selectSlot(this)">09:00 AM</div>
                            <div class="slot-chip" onclick="selectSlot(this)">09:30 AM</div>
                            <div class="slot-chip" onclick="selectSlot(this)">10:00 AM</div>
                            <div class="slot-chip" onclick="selectSlot(this)">10:30 AM</div>
                            <div class="slot-chip" onclick="selectSlot(this)">11:00 AM</div>
                        </div>
                        
                        <span class="session-title"><i class="fas fa-moon" style="color:#8b5cf6;"></i> Evening Session</span>
                        <div class="slots-container">
                            <div class="slot-chip" onclick="selectSlot(this)">04:00 PM</div>
                            <div class="slot-chip" onclick="selectSlot(this)">04:30 PM</div>
                            <div class="slot-chip" onclick="selectSlot(this)">05:00 PM</div>
                            <div class="slot-chip" onclick="selectSlot(this)">05:30 PM</div>
                        </div>
                        <input type="hidden" name="time_slot" id="selectedTimeSlot" required>
                    </div>

                    <!-- Patient Details Area -->
                    <div id="patientDetailsArea" style="display:none;">
                        <input type="hidden" name="reg_status" value="yes" id="reg_yes">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin: 40px 0 20px;">
                            <h3 style="margin: 0; color: white; font-size: 1.2rem;">Patient Details</h3>
                        </div>

                        <div class="reg-form-container">
                            <div class="info-text" style="color:var(--primary-blue); margin-bottom:15px;">
                                <i class="fas fa-user-check"></i> <b>Verified Patient: <?php echo htmlspecialchars($user_data['name'] ?? ''); ?></b>
                            </div>
                            
                            <div class="form-grid">
                                <div>
                                    <label>OP Number (Verified)</label>
                                    <input type="text" name="op_number" class="form-control-input" value="<?php echo $user_data['patient_code'] ?? ''; ?>" readonly style="opacity:0.6;">
                                </div>
                                <div>
                                    <label>Mobile Number (Verified)</label>
                                    <input type="tel" name="reg_mobile" class="form-control-input" value="<?php echo $user_data['phone'] ?? ''; ?>" readonly style="opacity:0.6;">
                                </div>
                            </div>
                            <div>
                                <label>Email (Verified)</label>
                                <input type="email" name="reg_email" class="form-control-input" value="<?php echo $user_data['email'] ?? ''; ?>" readonly style="opacity:0.6;">
                            </div>
                            
                            <!-- Captcha -->
                            <div style="margin-bottom:20px;">
                                <label style="display:block; margin-bottom:8px; font-weight:600; color:var(--text-light);">Symptoms / Reason for Visit <span class="required">*</span></label>
                                <textarea name="reason" rows="3" class="form-control-input" placeholder="Describe your symptoms briefly (e.g., fever for 3 days, chest pain, headache)" required minlength="10" style="resize:vertical;"></textarea>
                                <div style="font-size:0.8rem; color:var(--text-gray); margin-top:5px;">Minimum 10 characters required.</div>
                            </div>

                            <div class="captcha-box">
                                <label style="display:block; margin-bottom:5px; color:var(--text-gray); font-size:0.85rem;">Security Check <span class="required">*</span></label>
                                <span class="captcha-img">5692</span>
                                <input type="text" id="captchaInput" placeholder="Code" style="padding:10px; width:100px; border:1px solid var(--border-color); background:#0f172a; color:white; border-radius:4px;">
                            </div>

                            <div style="margin-top:20px; color:var(--text-gray); font-size:0.9rem;">
                                <input type="checkbox" required id="terms" checked> <label for="terms">I agree to the Hospital Terms & Conditions.</label>
                            </div>

                            <button type="submit" class="btn-continue">Confirm Booking</button>
                        </div>
                    </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:50px; color:var(--text-gray); font-style:italic;">Please select a doctor to proceed with booking.</div>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSlots() {
            document.getElementById('slotsPanel').style.display = 'block';
        }
        function selectSlot(el) {
            let slots = document.querySelectorAll('.slot-chip');
            slots.forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('selectedTimeSlot').value = el.innerText;
            
            // Generate a random token number to satisfy non-chronological requirement
            // Range 10 to 150 to simulate a busy queue
            let token = Math.floor(Math.random() * 140) + 10;

            // Update UI
            document.querySelector('.token-number').innerText = token;
            document.querySelector('input[name="token"]').value = token;
            
            // Show Token and Details
            document.getElementById('tokenMsg').style.display = 'block';
            document.getElementById('patientDetailsArea').style.display = 'block';
            
            // Smooth Scroll
            document.getElementById('tokenMsg').scrollIntoView({behavior: 'smooth'});
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
