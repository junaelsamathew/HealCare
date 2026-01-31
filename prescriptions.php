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
    <title>Prescriptions - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .prescription-card {
            background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);
            padding: 25px; border-radius: 15px; margin-bottom: 20px;
        }
        .presc-header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .med-list { list-style: none; }
        .med-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed rgba(255,255,255,0.1); }
        .med-item:last-child { border-bottom: none; }
        .med-name { font-weight: 600; color: #4fc3f7; }
        .dosage { color: var(--text-gray); font-size: 13px; }
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
        <div class="user-controls"><span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($username); ?></strong></span><a href="logout.php" class="btn-logout">Log Out</a></div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="patient_lab_results.php" class="nav-link"><i class="fas fa-flask"></i> Lab Reports</a>
                <a href="prescriptions.php" class="nav-link active"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="patient_feedback.php" class="nav-link"><i class="fas fa-comment-dots"></i> Patient Feedback</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header"><h1>My Prescriptions</h1><p>Active and past medication lists issued by your doctors</p></div>

            <div class="content-section" style="background: transparent; border: none; padding: 0;">
                
                <?php
                $presc_sql = "
                    SELECT p.*, r.name as doctor_name, r.specialization
                    FROM prescriptions p
                    LEFT JOIN users u ON p.doctor_id = u.user_id
                    LEFT JOIN registrations r ON u.registration_id = r.registration_id
                    WHERE p.patient_id = $user_id
                    ORDER BY p.prescription_date DESC
                ";
                $presc_res = $conn->query($presc_sql);

                if ($presc_res && $presc_res->num_rows > 0):
                    while ($p_row = $presc_res->fetch_assoc()):
                ?>
                <div class="prescription-card">
                    <div class="presc-header">
                        <div>
                            <h4 style="font-size: 18px; color: #fff;">
                                <?php 
                                    $pr_doc = $p_row['doctor_name'];
                                    echo htmlspecialchars((stripos($pr_doc, 'Dr.') === 0) ? $pr_doc : 'Dr. ' . $pr_doc);
                                ?>
                            </h4>
                            <p style="color: var(--text-gray); font-size: 13px;">
                                <?php echo htmlspecialchars($p_row['specialization'] ?? 'Clinician'); ?> • 
                                <?php echo date('M d, Y', strtotime($p_row['prescription_date'])); ?>
                            </p>
                        </div>
                        <a href="javascript:window.print()" class="action-cancel" style="color: #4fc3f7; text-decoration: none;">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                        <strong style="display: block; font-size: 11px; text-transform: uppercase; color: #3b82f6; margin-bottom: 10px;">Medication & Instructions:</strong>
                        <p style="color: #cbd5e1; line-height: 1.6; font-size: 14px;">
                            <?php echo nl2br(htmlspecialchars($p_row['medicine_details'])); ?>
                        </p>
                        
                        <?php if(!empty($p_row['instructions'])): ?>
                            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed rgba(255,255,255,0.1);">
                                <small style="color: #94a3b8;">Additional Instructions:</small>
                                <p style="font-size: 13px; color: #94a3b8; font-style: italic;"><?php echo htmlspecialchars($p_row['instructions']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    endwhile;
                else: 
                ?>
                <div style="text-align: center; padding: 50px; background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border-color);">
                    <i class="fas fa-pills" style="font-size: 50px; color: #334155; margin-bottom: 20px;"></i>
                    <p style="color: #64748b;">No active prescriptions found in your record.</p>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"></body>
</html>
