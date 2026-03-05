<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
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

$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
if (stripos($doctor_name, 'Dr.') === false && stripos($doctor_name, 'Doctor') === false) {
    $doctor_name = "Dr. " . $doctor_name;
}

// Fetch Assigned Patients
$dropdown_patients = [];
$dp_query = "SELECT DISTINCT u.user_id, r.name, pp.patient_code FROM appointments a JOIN users u ON a.patient_id = u.user_id JOIN registrations r ON u.registration_id = r.registration_id LEFT JOIN patient_profiles pp ON u.user_id = pp.user_id WHERE a.doctor_id = ? ORDER BY r.name ASC";
$stmt_dp = $conn->prepare($dp_query);
$stmt_dp->bind_param("i", $user_id);
$stmt_dp->execute();
$res_dp = $stmt_dp->get_result();
while ($row = $res_dp->fetch_assoc()) { $dropdown_patients[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .form-container {
            background: rgba(30, 41, 59, 0.4);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 40px;
            margin-bottom: 40px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .form-group label {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 600;
        }
        .form-group input, .form-group select, .form-group textarea {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 15px;
            border-radius: 10px;
            color: white;
            font-size: 14px;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #4fc3f7;
        }
        .medicine-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 0.5fr;
            gap: 15px;
            margin-bottom: 15px;
            align-items: center;
        }
        .btn-add-medicine {
            background: rgba(79, 195, 247, 0.1);
            color: #4fc3f7;
            border: 1px dashed #4fc3f7;
            padding: 10px;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 25px;
        }
        .btn-submit {
            background: #10b981;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .prescription-history {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .prescription-history th { text-align: left; padding: 15px 20px; color: #94a3b8; font-size: 13px; }
        .prescription-row { background: rgba(255, 255, 255, 0.02); }
        .prescription-row td { padding: 20px; border-top: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-calendar-alt"></i></div>
                <div class="info-details"><span class="info-label">DATE</span><span class="info-value"><?php echo date('d M Y'); ?></span></div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-file-prescription"></i></div>
                <div class="info-details"><span class="info-label">ACTION</span><span class="info-value">New Rx Mode</span></div>
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
                <a href="doctor_prescriptions.php" class="nav-link active"><i class="fas fa-file-prescription"></i> Prescriptions</a>
                <a href="doctor_lab_orders.php" class="nav-link"><i class="fas fa-flask"></i> Lab Orders</a>
                <a href="doctor_leave.php" class="nav-link"><i class="fas fa-calendar-minus"></i> Apply Leave</a>
                <a href="doctor_settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Prescription Center</h1>
                <p>Create digital prescriptions and view patient medicine history.</p>
            </div>

            <div class="form-container">
                <h2 style="color: white; margin-bottom: 25px;">Create New Prescription</h2>
                <form id="prescriptionForm" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Patient</label>
                            <select name="patient_id" id="patient_id" required>
                                <option value="">-- Select Assigned Patient --</option>
                                <?php foreach($dropdown_patients as $p): ?>
                                    <option value="<?php echo $p['user_id']; ?>"><?php echo htmlspecialchars($p['name']) . ' (' . ($p['patient_code'] ?? 'ID: '.$p['user_id']) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Diagnosis / Condition</label>
                            <input type="text" name="diagnosis" id="diagnosis" placeholder="e.g. Acute Viral Fever" required>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;"><label style="font-size: 13px; color: #94a3b8; font-weight: 600;">Medicines & Dosage</label></div>
                    <div id="medicinesContainer">
                        <div class="medicine-row" data-index="0">
                            <input type="text" name="medicines[0][name]" placeholder="Medicine Name" class="med-input" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; color: white;" required>
                            <input type="text" name="medicines[0][dosage]" placeholder="Dosage (e.g. 500mg)" class="med-input" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; color: white;">
                            <select name="medicines[0][frequency]" class="med-input" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; color: white;">
                                <option value="1-0-1">1-0-1</option>
                                <option value="1-1-1">1-1-1</option>
                                <option value="0-0-1">0-0-1</option>
                                <option value="1-0-0">1-0-0</option>
                                <option value="0-1-0">0-1-0</option>
                            </select>
                            <input type="text" name="medicines[0][duration]" placeholder="Duration (5 Days)" class="med-input" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; color: white;">
                            <button type="button" onclick="removeMedicine(this)" style="background: none; border: none; color: #ef4444; font-size: 18px;"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>

                    <button type="button" class="btn-add-medicine" onclick="addMedicine()">+ Add Another Medicine</button>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Special Instructions / Notes</label>
                        <textarea name="instructions" id="instructions" rows="3" placeholder="e.g. Drink plenty of water, Avoid oily food."></textarea>
                    </div>

                    <button type="submit" id="submitBtn" class="btn-submit">Finalize & Issue Prescription</button>
                </form>
            </div>

            <div class="content-section">
                <h2 style="color: white; margin-bottom: 20px;">Issued Prescriptions</h2>
                <table class="prescription-history">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient Name</th>
                            <th>Diagnosis</th>
                            <th>Medicines</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="prescription-row">
                            <td>01 Jan 2026</td>
                            <td>Dileep Mathew</td>
                            <td>Hypertension</td>
                            <td>Amlodipine (5mg)...</td>
                            <td><a href="#" class="btn-view"><i class="fas fa-file-pdf"></i> View PDF</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        let medicineIndex = 1;
        
        function addMedicine() {
            const container = document.getElementById('medicinesContainer');
            const newRow = document.createElement('div');
            newRow.className = 'medicine-row';
            newRow.setAttribute('data-index', medicineIndex);
            newRow.innerHTML = `
                <input type="text" name="medicines[${medicineIndex}][name]" placeholder="Medicine Name" class="med-input" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; color: white;">
                <input type="text" name="medicines[${medicineIndex}][dosage]" placeholder="Dosage (e.g. 500mg)" class="med-input" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; color: white;">
                <select name="medicines[${medicineIndex}][frequency]" class="med-input" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; color: white;">
                    <option value="1-0-1">1-0-1</option>
                    <option value="1-1-1">1-1-1</option>
                    <option value="0-0-1">0-0-1</option>
                    <option value="1-0-0">1-0-0</option>
                    <option value="0-1-0">0-1-0</option>
                </select>
                <input type="text" name="medicines[${medicineIndex}][duration]" placeholder="Duration (5 Days)" class="med-input" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; color: white;">
                <button type="button" onclick="removeMedicine(this)" style="background: none; border: none; color: #ef4444; font-size: 18px;"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(newRow);
            medicineIndex++;
        }
        
        function removeMedicine(btn) {
            const rows = document.querySelectorAll('.medicine-row');
            if (rows.length > 1) {
                btn.closest('.medicine-row').remove();
            } else {
                alert('At least one medicine is required');
            }
        }
        
        // Form submission
        document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerText = 'Creating Prescription...';
            
            // Collect medicines as array
            const medicines = [];
            document.querySelectorAll('.medicine-row').forEach((row, index) => {
                const name = row.querySelector(`[name="medicines[${index}][name]"]`)?.value || '';
                const dosage = row.querySelector(`[name="medicines[${index}][dosage]"]`)?.value || '';
                const frequency = row.querySelector(`[name="medicines[${index}][frequency]"]`)?.value || '1-0-1';
                const duration = row.querySelector(`[name="medicines[${index}][duration]"]`)?.value || '';
                
                if (name) {
                    medicines.push({ name, dosage, frequency, duration });
                }
            });
            
            // Clear existing medicine fields and add as JSON
            formData.delete('medicines');
            formData.append('medicines', JSON.stringify(medicines));
            
            fetch('process_prescription.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                    document.getElementById('prescriptionForm').reset();
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
                submitBtn.innerText = 'Finalize & Issue Prescription';
            });
        });
    </script>
</body>
</html>
