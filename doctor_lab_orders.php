<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch actual doctor professional info
$stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $doctor = $res->fetch_assoc();
    $specialization = $doctor['specialization'];
    $department = $doctor['department'];
    $designation = $doctor['designation'];
} else {
    $specialization = "General Healthcare";
    $department = "General Medicine";
    $designation = "Professional Consultant";
}

$doctor_name = "Dr. " . htmlspecialchars($_SESSION['username']); // Fallback, will be overwritten by logic below if needed.
$doctor_full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
if (stripos($doctor_full_name, 'Dr.') === false && stripos($doctor_full_name, 'Doctor') === false) {
    $doctor_name = "Dr. " . $doctor_full_name;
} else {
    $doctor_name = $doctor_full_name;
}

// Fetch Assigned Patients for Dropdown
$dropdown_patients = [];
$dp_query = "
    SELECT DISTINCT u.user_id, r.name, pp.patient_code
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    JOIN registrations r ON u.registration_id = r.registration_id
    LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id
    WHERE a.doctor_id = ?
    ORDER BY r.name ASC
";
$stmt_dp = $conn->prepare($dp_query);
$stmt_dp->bind_param("i", $user_id);
$stmt_dp->execute();
$res_dp = $stmt_dp->get_result();
while ($row = $res_dp->fetch_assoc()) {
    $dropdown_patients[] = $row;
}

// Fetch Available Lab Tests (from Packages + History + Defaults)
$available_tests = [];

// 1. From Health Packages
$pkg_q = $conn->query("SELECT included_tests FROM health_packages");
while($pkg = $pkg_q->fetch_assoc()) {
    if(!empty($pkg['included_tests'])) {
        $parts = explode(',', $pkg['included_tests']);
        foreach($parts as $p) {
            $t = trim($p);
            if(!empty($t) && !in_array($t, $available_tests)) {
                $available_tests[] = $t;
            }
        }
    }
}

// 2. From Past Lab Tests (History)
$hist_q = $conn->query("SELECT DISTINCT test_name FROM lab_tests");
while($hist = $hist_q->fetch_assoc()) {
    $t = trim($hist['test_name']);
    if(!empty($t) && !in_array($t, $available_tests)) {
        $available_tests[] = $t;
    }
}

// 3. Robust Defaults (aligned with Lab Staff types)
$defaults = [
    'Blood Test (CBC)', 'Lipid Profile', 'Thyroid Profile', 'Blood Sugar (Fasting)', 'Blood Sugar (PP)', 
    'Urine Culture', 'Kidney Function Test', 'Liver Function Test', 
    'X-Ray (Chest)', 'X-Ray (Fracture)', 'MRI Scan', 'CT Scan', 
    'Ultrasound (Abdominal)', 'Doppler Scan', 
    'ECG', 'Hearing Test', 'Eye Test'
];
foreach($defaults as $d) {
    if(!in_array($d, $available_tests)) {
        $available_tests[] = $d;
    }
}
sort($available_tests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Orders - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .order-form {
            background: rgba(30, 41, 59, 0.4);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .order-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .form-label { display: block; margin-bottom: 8px; font-size: 13px; color: #94a3b8; font-weight: 600; }
        .form-input { 
            width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); 
            padding: 12px; border-radius: 10px; color: #fff; outline: none;
        }
        .form-input option {
            background: #1e293b;
            color: #fff;
        }
        .order-card {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .priority-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .urgent { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .normal { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        
        /* Validation Styles */
        .validation-msg { font-size: 11px; margin-top: 4px; display: none; transition: 0.3s; }
        .validation-msg.error { color: #ef4444; display: block; }
        .form-input.invalid { border-color: #ef4444 !important; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.1); }
        .form-input.valid { border-color: #10b981 !important; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1); }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-flask"></i></div>
                <div class="info-details"><span class="info-label">DATE</span><span class="info-value"><?php echo date('d M Y'); ?></span></div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-file-medical"></i></div>
                <div class="info-details"><span class="info-label">LAB STATUS</span><span class="info-value">2 Done / 1 Pending</span></div>
            </div>
        </div>
    </header>

    <header class="secondary-header">
        <div class="brand-section">
            <div class="brand-icon">+</div>
            <div class="brand-name">HealCare</div>
            <div style="margin-left: 20px; padding: 4px 12px; background: rgba(79, 195, 247, 0.15); border: 1px solid #4fc3f7; border-radius: 20px; color: #4fc3f7; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                <?php echo $department; ?> DEPT
            </div>
        </div>
        <div class="user-controls">
            <span class="user-greeting">Welcome, <strong><?php echo $doctor_name; ?></strong></span>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="doctor_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="doctor_patients.php" class="nav-link"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_prescriptions.php" class="nav-link"><i class="fas fa-file-prescription"></i> Prescriptions</a>

                <a href="doctor_lab_orders.php" class="nav-link active"><i class="fas fa-flask"></i> Lab Orders</a>
                <a href="doctor_leave.php" class="nav-link"><i class="fas fa-calendar-minus"></i> Apply Leave</a>
                <a href="doctor_settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Lab Test Requests</h1>
                <p>Order pathological or radiological tests and track their status.</p>
            </div>

            <div class="order-form">
                <h2 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Create Lab Test Request</h2>
                <form id="labRequestForm" method="POST" novalidate>
                    <div class="order-grid">
                        <div>
                            <label class="form-label">Select Patient</label>
                            <select class="form-input" name="patient_id" id="patient_id" required>
                                <option value="">-- Select Patient --</option>
                                <?php foreach($dropdown_patients as $p): ?>
                                    <option value="<?php echo $p['user_id']; ?>">
                                        <?php echo htmlspecialchars($p['name']) . ' (' . ($p['patient_code'] ?? 'ID: '.$p['user_id']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="pat_msg" class="validation-msg"></div>
                        </div>
                        <div>
                            <label class="form-label">Test Type</label>
                            <select class="form-input" name="test_type" id="test_type" required>
                                <option value="">-- Select Test --</option>
                                <?php foreach($available_tests as $test): ?>
                                    <option value="<?php echo htmlspecialchars($test); ?>"><?php echo htmlspecialchars($test); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="test_msg" class="validation-msg"></div>
                        </div>
                        <div>
                            <label class="form-label">Priority</label>
                            <select class="form-input" name="priority" id="priority">
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent / STAT</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label class="form-label">Reason / Clinical Details</label>
                        <textarea class="form-input" name="reason" id="reason" rows="2" placeholder="e.g. For pre-surgical screening..."></textarea>
                        <div id="reason_msg" class="validation-msg"></div>
                    </div>
                    <button type="submit" id="submitBtn" style="background: #1e40af; color: #fff; width: 100%; padding: 12px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Send Request to Lab</button>
                </form>
            </div>

            <div class="content-section">
                <h2 style="color: white; margin-bottom: 20px;">Recent Lab Orders</h2>
                <?php
                // Fetch Recent Lab Orders
                $query_orders = "
                    SELECT l.labtest_id, l.patient_id, l.test_name, l.status, l.priority, l.report_path, l.result, l.created_at, r.name as patient_name 
                    FROM lab_tests l 
                    JOIN users u ON l.patient_id = u.user_id 
                    JOIN registrations r ON u.registration_id = r.registration_id 
                    WHERE l.doctor_id = ? 
                    ORDER BY l.labtest_id DESC LIMIT 10
                ";
                
                $stmt_ord = $conn->prepare($query_orders);
                $stmt_ord->bind_param("i", $user_id);
                $stmt_ord->execute();
                $res_ord = $stmt_ord->get_result();

                if ($res_ord->num_rows > 0) {
                    while ($ord = $res_ord->fetch_assoc()) {
                        $test_name = htmlspecialchars($ord['test_name']);
                        $pat_name = htmlspecialchars($ord['patient_name']);
                        $status = ucfirst($ord['status']);
                        $priority = ucfirst($ord['priority'] ?? 'Normal');
                        $result_text = htmlspecialchars($ord['result'] ?? '');
                        
                        // Time Formatting
                        $created_val = $ord['created_at'] ?? null;
                        $time_display = 'Recently';
                        if($created_val) {
                            $ts = strtotime($created_val);
                            if(date('Y-m-d') == date('Y-m-d', $ts)) {
                                $time_display = 'Today, ' . date('h:i A', $ts);
                            } elseif(date('Y-m-d', strtotime('-1 day')) == date('Y-m-d', $ts)) {
                                $time_display = 'Yesterday';
                            } else {
                                $time_display = date('d M Y, h:i A', $ts);
                            }
                        }

                        // Styling Logic
                        $p_class = (strtolower($priority) == 'urgent' || strtolower($priority) == 'stat') ? 'urgent' : 'normal';
                        
                        $s_color = '#fbbf24'; // Pending default (Yellow)
                        if ($status == 'Completed') $s_color = '#10b981'; // Green
                        elseif ($status == 'Processing') $s_color = '#f59e0b'; // Orange
                        elseif ($status == 'Cancelled') $s_color = '#ef4444'; // Red

                        $has_pdf = !empty($ord['report_path']);
                        $pdf_link = $has_pdf ? htmlspecialchars($ord['report_path']) : '';
                        
                        $has_result = !empty($result_text) || $has_pdf;

                        // Safe JS Variables
                        $js_test = htmlspecialchars(json_encode($test_name), ENT_QUOTES, 'UTF-8');
                        $js_pat = htmlspecialchars(json_encode($pat_name), ENT_QUOTES, 'UTF-8');
                        $js_res = htmlspecialchars(json_encode($result_text), ENT_QUOTES, 'UTF-8');
                        $js_pdf = htmlspecialchars(json_encode($pdf_link), ENT_QUOTES, 'UTF-8');

                        // Check for matching Manual Reports if official one is missing
                        if (!$has_pdf) {
                            $p_id_cur = $ord['patient_id'] ?? null;
                            $search_pat_name = mysqli_real_escape_string($conn, $ord['patient_name']);
                            $search_test_name = mysqli_real_escape_string($conn, $ord['test_name']);
                            
                            // Query: Match by PID OR by Patient Name in Title
                            $m_query = "SELECT file_path, report_title FROM manual_reports WHERE ";
                            $m_query .= "( (patient_id = $p_id_cur) OR (report_title LIKE '%$search_pat_name%') ) ";
                            
                            // Narrow down by test name or recently uploaded
                            $m_query .= "AND (report_title LIKE '%$search_test_name%' OR report_type LIKE '%$search_test_name%' OR created_at >= NOW() - INTERVAL 48 HOUR) ";
                            $m_query .= "ORDER BY (CASE WHEN report_title LIKE '%$search_test_name%' THEN 1 ELSE 2 END) ASC, created_at DESC LIMIT 1";
                            
                            $m_check = $conn->query($m_query);
                            if ($m_check && $m_row = $m_check->fetch_assoc()) {
                                $pdf_link = htmlspecialchars($m_row['file_path']);
                                $js_pdf = htmlspecialchars(json_encode($pdf_link), ENT_QUOTES, 'UTF-8');
                                $has_result = true;
                                $has_pdf = true;
                                $status = "Report Uploaded"; 
                                $s_color = "#4fc3f7";
                            }
                        }

                        echo '
                        <div class="order-card">
                            <div>
                                <h4 style="color: white; margin-bottom: 5px;">' . $test_name . '</h4>
                                <p style="font-size: 13px; color: #94a3b8;">Patient: ' . $pat_name . ' • Requested: ' . $time_display . '</p>
                            </div>
                            <div style="text-align: right; display: flex; align-items: center; gap: 20px;">
                                <div style="text-align: right;">
                                    <span class="priority-badge ' . $p_class . '">' . $priority . '</span><br>
                                    <span style="font-size: 11px; color: ' . $s_color . '; font-weight: 600;">Status: ' . $status . '</span>
                                </div>
                                ' . ($has_result ? '
                                <button onclick="viewResult(' . $js_test . ', ' . $js_pat . ', ' . $js_res . ', ' . $js_pdf . ')" class="btn-view" style="background:transparent; color:#4fc3f7; border:1px solid rgba(79,195,247,0.3); padding:6px 12px; border-radius:6px; cursor:pointer; transition:0.3s; display:flex; align-items:center; gap:5px;">
                                    <i class="fas fa-eye"></i> View
                                </button>' : '') . '
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p style="color: #94a3b8; text-align: center; padding: 20px;">No lab orders found.</p>';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Result View Modal -->
    <div id="resultModal" style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div style="background:#1e293b; padding:30px; border-radius:16px; width:500px; max-width:90%; position:relative; border:1px solid rgba(255,255,255,0.1);">
            <button onclick="document.getElementById('resultModal').style.display='none'" style="position:absolute; top:20px; right:20px; background:none; border:none; color:#94a3b8; cursor:pointer; font-size:20px;">&times;</button>
            
            <h3 id="resTestName" style="color:#fff; margin-bottom:5px;">Test Results</h3>
            <p id="resPatName" style="color:#94a3b8; font-size:13px; margin-bottom:20px;">Patient Name</p>
            
            <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:8px; margin-bottom:20px;">
                <label style="color:#4fc3f7; font-size:11px; text-transform:uppercase; font-weight:bold; display:block; margin-bottom:5px;">Technician Notes / Findings</label>
                <p id="resText" style="color:#e2e8f0; font-size:14px; line-height:1.5; white-space: pre-wrap;">No text result available.</p>
            </div>

            <div id="resPdfArea" style="display:none;">
                <a id="resPdfLink" href="#" target="_blank" style="display:block; background:#3b82f6; color:#fff; text-align:center; padding:12px; border-radius:8px; text-decoration:none; font-weight:600;">
                    <i class="fas fa-file-pdf"></i> Download Official Report (PDF)
                </a>
            </div>
        </div>
    </div>

    <script>
        function viewResult(test, patient, text, pdf) {
            document.getElementById('resTestName').innerText = test;
            document.getElementById('resPatName').innerText = "Patient: " + patient;
            document.getElementById('resText').innerText = text ? text : "No textual findings provided.";
            
            const pdfArea = document.getElementById('resPdfArea');
            const pdfLink = document.getElementById('resPdfLink');
            
            if (pdf && pdf !== '') {
                pdfArea.style.display = 'block';
                pdfLink.href = pdf;
            } else {
                pdfArea.style.display = 'none';
            }
            
            document.getElementById('resultModal').style.display = 'flex';
        }

        // Live Validation Logic
        const isGibberish = (str) => {
            if (!str) return false;
            const cleanStr = str.toLowerCase().replace(/[^a-z]/g, '');
            if (cleanStr.length === 0) return false;
            const words = str.toLowerCase().split(/\s+/);
            const vowelPattern = /[aeiouy]/;
            for (let word of words) {
                const cleanWord = word.replace(/[^a-z]/g, '');
                if (cleanWord.length > 3 && !vowelPattern.test(cleanWord)) return true;
            }
            if (/(.)\1{4,}/.test(cleanStr)) return true;
            const patterns = ['qwerty', 'asdfgh', 'zxcvbn', 'qazwsx', 'edcrfv', '123456'];
            for (let p of patterns) { if (cleanStr.includes(p)) return true; }
            const uniqueChars = new Set(cleanStr).size;
            const ratio = uniqueChars / cleanStr.length;
            if (cleanStr.length > 8 && ratio > 0.7 && !str.includes(' ')) return true;
            return false;
        };

        function setValidationUI(el, msgEl, isValid, message) {
            if (!el || !msgEl) return;
            if (isValid) {
                el.classList.remove('invalid');
                el.classList.add('valid');
                msgEl.innerText = "";
                msgEl.classList.remove('error');
            } else {
                el.classList.remove('valid');
                el.classList.add('invalid');
                msgEl.innerText = message;
                msgEl.classList.add('error');
            }
        }

        document.getElementById('patient_id').addEventListener('change', function() {
            setValidationUI(this, document.getElementById('pat_msg'), !!this.value, "Please select a patient.");
        });

        document.getElementById('test_type').addEventListener('change', function() {
            setValidationUI(this, document.getElementById('test_msg'), !!this.value, "Please select a test type.");
        });

        document.getElementById('reason').addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length === 0) {
                this.classList.remove('invalid', 'valid');
                document.getElementById('reason_msg').innerText = "";
                return;
            }
            if (val.length < 5) {
                setValidationUI(this, document.getElementById('reason_msg'), false, "Reason is too short.");
            } else if (isGibberish(val)) {
                setValidationUI(this, document.getElementById('reason_msg'), false, "Reason contains invalid text.");
            } else {
                setValidationUI(this, document.getElementById('reason_msg'), true, "");
            }
        });

        // Lab Request Form Submission
        document.getElementById('labRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic Validation
            const patientIdEl = document.getElementById('patient_id');
            const testTypeEl = document.getElementById('test_type');
            const reasonEl = document.getElementById('reason');
            
            const patientId = patientIdEl.value;
            const testType = testTypeEl.value;
            const reason = reasonEl.value.trim();
            
            // Reset borders
            patientIdEl.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            testTypeEl.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            reasonEl.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            
            if (!patientId) {
                alert('Please select a patient.');
                patientIdEl.style.borderColor = '#ef4444';
                patientIdEl.focus();
                return;
            }
            if (!testType) {
                alert('Please select a test type.');
                testTypeEl.style.borderColor = '#ef4444';
                testTypeEl.focus();
                return;
            }
            if (!reason) {
                alert('Please provide reason or clinical details for the test.');
                reasonEl.style.borderColor = '#ef4444';
                reasonEl.focus();
                return;
            }

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerText = 'Sending...';
            
            fetch('process_lab_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message + '\n\nCategory: ' + data.category + '\nThis request will appear in the ' + data.category + ' dashboard.');
                    document.getElementById('labRequestForm').reset();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error: ' + error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Send Request to Lab';
            });
        });
    </script></body>
</html>
