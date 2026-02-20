<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$doctor_username = $_SESSION['username'];

if (!isset($_GET['id'])) {
    header("Location: doctor_patients.php");
    exit();
}

$patient_id = intval($_GET['id']);

// Fetch Patient Details
$query = "
    SELECT 
        u.user_id,
        r.name,
        r.email,
        r.phone,
        p.gender,
        p.date_of_birth,
        p.blood_group,
        p.address,
        p.medical_history,
        p.allergies,
        p.patient_code
    FROM users u
    JOIN registrations r ON u.registration_id = r.registration_id
    LEFT JOIN patient_profiles p ON u.user_id = p.user_id
    WHERE u.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    echo "Patient not found.";
    exit();
}

// Calculate Age
$age = 'N/A';
if (!empty($patient['date_of_birth'])) {
    $dob = new DateTime($patient['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y . ' Years';
}

// Fetch Latest Vitals
$vitals_query = "SELECT * FROM patient_vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1";
$stmt_v = $conn->prepare($vitals_query);
$stmt_v->bind_param("i", $patient_id);
$stmt_v->execute();
$vitals = $stmt_v->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .profile-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-item label {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .info-item span {
            color: #fff;
            font-size: 16px;
            font-weight: 500;
        }
        .section-title {
            color: #4fc3f7;
            font-size: 18px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }
        .metric-card {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }
        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }
        .metric-label {
            font-size: 12px;
            color: #94a3b8;
        }

        /* Brand Animation */
        .brand-letter {
            display: inline-block;
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .brand-letter.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
        <a href="index.php" style="text-decoration:none; display: flex; align-items: center; gap: 10px;">
            <img src="images/healcare_logo.jpg" alt="HealCare" style="height: 50px;">
            <span class="animated-brand" style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">HEALCARE HOSPITAL</span>
        </a>
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WHATSAPP</span>
                    <a href="https://wa.me/919539045609" target="_blank" style="font-size: 13px; color: #25d366; font-weight: 600; text-decoration: none;"><i class="fab fa-whatsapp"></i> +91 953 904 5609</a>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">LOCATION</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">Kanjirapally, Kottayam</span>
                </div>
            </div>
        </div>
    </div>

    <header class="secondary-header" style="display: flex; justify-content: flex-end; padding: 10px 40px; background: #0f172a; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div class="user-controls" style="display: flex; align-items: center; gap: 20px;">
            <span class="user-greeting" style="color: #cbd5e1; font-size: 14px;">Welcome, <strong style="color: #fff;"><?php echo $doctor_username; ?></strong></span>
            <a href="logout.php" class="btn-logout" style="padding: 6px 15px; background: transparent; border: 1px solid #3b82f6; color: #fff; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s;">Sign Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="doctor_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="doctor_patients.php" class="nav-link active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <a href="doctor_patients.php" style="color: #94a3b8; text-decoration: none; font-size: 13px;"><i class="fas fa-arrow-left"></i> Back to Patients</a>
                    <h1 style="margin-top: 10px;">Patient Profile</h1>
                </div>
            </div>

            <div class="profile-card">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                    <div style="width: 80px; height: 80px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; color: white;">
                        <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h2 style="color: white; margin: 0;"><?php echo htmlspecialchars($patient['name']); ?></h2>
                        <span style="color: #94a3b8; font-size: 14px;">ID: <?php echo htmlspecialchars($patient['patient_code'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="section-title">Personal Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Age</label>
                        <span><?php echo $age; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Gender</label>
                        <span><?php echo htmlspecialchars(ucfirst($patient['gender'] ?? 'N/A')); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Blood Group</label>
                        <span style="color: #ef4444; font-weight: 700;"><?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <span><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <span><?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <div class="section-title">Medical Overview</div>
                <div class="info-grid">
                    <div class="info-item" style="grid-column: span 2;">
                        <label>Medical History / Conditions</label>
                        <p style="color: #cbd5e1; line-height: 1.6; margin-top: 5px;">
                            <?php echo !empty($patient['medical_history']) ? nl2br(htmlspecialchars($patient['medical_history'])) : 'No recorded medical history.'; ?>
                        </p>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <label>Allergies</label>
                        <p style="color: #ef4444; line-height: 1.6; margin-top: 5px;">
                            <?php echo !empty($patient['allergies']) ? nl2br(htmlspecialchars($patient['allergies'])) : 'None reported.'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($vitals): ?>
            <div class="profile-card">
                <div class="section-title">Latest Vitals <small style="font-size: 12px; color: #94a3b8; font-weight: normal;">(Recorded on <?php echo date('d M Y, h:i A', strtotime($vitals['recorded_at'])); ?>)</small></div>
                <div class="info-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="metric-card">
                        <div class="metric-value" style="color: #3b82f6;"><?php echo $vitals['heart_rate'] ?? '--'; ?></div>
                        <div class="metric-label">Heart Rate (bpm)</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" style="color: #ef4444;"><?php echo ($vitals['blood_pressure_systolic'] && $vitals['blood_pressure_diastolic']) ? $vitals['blood_pressure_systolic'].'/'.$vitals['blood_pressure_diastolic'] : '--'; ?></div>
                        <div class="metric-label">Blood Pressure</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" style="color: #10b981;"><?php echo $vitals['temperature'] ?? '--'; ?></div>
                        <div class="metric-label">Temperature (°F)</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" style="color: #f59e0b;"><?php echo $vitals['oxygen_saturation'] ?? '--'; ?></div>
                        <div class="metric-label">SpO2 (%)</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    <script>
        // Brand Animation
        document.addEventListener('DOMContentLoaded', function() {
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
            
            initBrandAnimation();
        });
    </script>
    </div></body>
</html>
