<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - HealCare</title>
<<<<<<< HEAD
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
=======
    <link rel="stylesheet" href="styles/dashboard.css">
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
    <style>
        .record-card {
            background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);
            padding: 20px; border-radius: 12px; margin-bottom: 15px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .btn-download {
            background: rgba(59, 130, 246, 0.1); color: var(--primary-blue);
            padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;
        }
<<<<<<< HEAD
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
    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
        <a href="index.php" class="logo-main" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img src="images/healcare_logo.jpg" alt="HealCare" style="height: 50px;">
            <span class="animated-brand" style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">HEALCARE HOSPITAL</span>
        </a>
        <div style="display: flex; gap: 40px; align-items: center;">
             <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">EMERGENCY</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">(+91) 953 904 5609</span>
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

    <header class="secondary-header">
        <div style="flex: 1;"></div>
=======
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
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
        <div class="user-controls"><span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($username); ?></strong></span><a href="logout.php" class="btn-logout">Log Out</a></div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
<<<<<<< HEAD
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link active"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="patient_lab_results.php" class="nav-link"><i class="fas fa-flask"></i> Lab Reports</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="patient_feedback.php" class="nav-link"><i class="fas fa-comment-dots"></i> Patient Feedback</a>
=======
                <a href="patient_dashboard.php" class="nav-link">Dashboard</a>
                <a href="book_appointment.php" class="nav-link">Book Appointment</a>
                <a href="my_appointments.php" class="nav-link">My Appointments</a>
                <a href="medical_records.php" class="nav-link active"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header"><h1>Medical Records</h1><p>Access your diagnosis reports and lab results</p></div>

            <div class="content-section">
                <div class="section-head"><h3>Recent Diagnosis Reports</h3></div>
                
                <?php
                $records_sql = "
                    SELECT mr.*, r.name as doctor_name 
                    FROM medical_records mr
                    LEFT JOIN users u ON mr.doctor_id = u.user_id
                    LEFT JOIN registrations r ON u.registration_id = r.registration_id
                    WHERE mr.patient_id = $user_id
                    ORDER BY mr.created_at DESC
                ";
                $records_res = $conn->query($records_sql);

                if ($records_res && $records_res->num_rows > 0):
                    while ($row = $records_res->fetch_assoc()):
                ?>
                <div class="record-card">
                    <div>
                        <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($row['diagnosis']); ?></h4>
                        <p style="color: var(--text-gray); font-size: 13px;">
<<<<<<< HEAD
                            Consulted with <?php 
                                $mr_doc = $row['doctor_name'];
                                echo htmlspecialchars((stripos($mr_doc, 'Dr.') === 0) ? $mr_doc : 'Dr. ' . $mr_doc); 
                            ?> • 
=======
                            Consulted with <?php echo htmlspecialchars($row['doctor_name']); ?> • 
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                        </p>
                    </div>
                    <a href="generate_report_pdf.php?id=<?php echo $row['record_id']; ?>" class="btn-download"><i class="fas fa-download"></i> PDF</a>
                </div>
                <?php 
                    endwhile;
                else: 
                ?>
                    <div class="empty-state"><p>No medical records found yet.</p></div>
                <?php endif; ?>
            </div>

            <div class="content-section" style="margin-top: 30px;">
                <div class="section-head"><h3>Lab Reports & Results</h3></div>
                
                <?php
                $lab_sql = "
                    SELECT lt.*, r.name as doctor_name 
                    FROM lab_tests lt
                    LEFT JOIN users u ON lt.doctor_id = u.user_id
                    LEFT JOIN registrations r ON u.registration_id = r.registration_id
                    WHERE lt.patient_id = $user_id AND lt.status = 'Completed'
                    ORDER BY lt.updated_at DESC
                ";
                $lab_res = $conn->query($lab_sql);

                if ($lab_res && $lab_res->num_rows > 0):
                    while ($lab_row = $lab_res->fetch_assoc()):
                        // Determine type icon
                        $icon = 'fa-flask'; // generic
                        if(strpos($lab_row['test_type'], 'X-Ray') !== false) $icon = 'fa-x-ray';
                        elseif(strpos($lab_row['test_type'], 'Ultrasound') !== false) $icon = 'fa-wave-square';
                ?>
                <div class="record-card">
                    <div style="display:flex; align-items:center;">
                        <div style="width:40px; height:40px; background:rgba(59, 130, 246, 0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin-right:15px; color:var(--primary-blue);">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($lab_row['test_name']); ?></h4>
                            <p style="color: var(--text-gray); font-size: 13px;">
                                <span style="color: var(--primary-blue); font-weight:600;"><?php echo htmlspecialchars($lab_row['test_type']); ?></span> • 
<<<<<<< HEAD
                                <?php 
                                    $lr_doc = $lab_row['doctor_name'];
                                    echo htmlspecialchars((stripos($lr_doc, 'Dr.') === 0) ? $lr_doc : 'Dr. ' . $lr_doc);
                                ?> • 
=======
                                Dr. <?php echo htmlspecialchars($lab_row['doctor_name']); ?> • 
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
                                <?php echo date('M d, Y', strtotime($lab_row['updated_at'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php if(!empty($lab_row['report_path'])): ?>
                        <a href="<?php echo htmlspecialchars($lab_row['report_path']); ?>" target="_blank" class="btn-download"><i class="fas fa-file-pdf"></i> View Report</a>
                    <?php else: ?>
                        <span style="color:#64748b; font-size:12px;">Processing...</span>
                    <?php endif; ?>
                </div>
                <?php 
                    endwhile;
                else: 
                ?>
                    <div class="empty-state"><p>No lab reports available yet.</p></div>
                <?php endif; ?>
            </div>
        </main>
    </div>
<<<<<<< HEAD
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
=======
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"></body>
</html>
