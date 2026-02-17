<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Profile Name
$res = $conn->query("SELECT name FROM patient_profiles WHERE user_id = $user_id");
$display_name = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['name'] : $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Reports - HealCare</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Styles -->
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.3s;
        }
        .report-card:hover {
            background: rgba(255,255,255,0.04);
            border-color: rgba(79, 195, 247, 0.3);
            transform: translateX(5px);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-Completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-Pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-Processing { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #64748b;
        }
        .empty-state i {
            display: block;
            margin-bottom: 20px;
            opacity: 0.3;
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
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WHATSAPP</span>
                    <a href="https://wa.me/919539045609" target="_blank" style="font-size: 13px; color: #25d366; font-weight: 600; text-decoration: none;"><i class="fab fa-whatsapp"></i> (+91) 953 904 5609</a>
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
        <div class="user-controls">
            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($display_name); ?></strong></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="my_packages.php" class="nav-link"><i class="fas fa-box-medical"></i> My Health Packages</a>
                <a href="patient_lab_results.php" class="nav-link active"><i class="fas fa-flask"></i> Lab Reports</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="patient_ambulance.php" class="nav-link"><i class="fas fa-ambulance"></i> Ambulance Service</a>
                <a href="patient_feedback.php" class="nav-link"><i class="fas fa-comment-dots"></i> Patient Feedback</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>My Lab Reports</h1>
                <p>View and download your laboratory test results.</p>
            </div>

            <div class="content-section">
                <?php
                $sql = "
                    SELECT lt.*, d.name as doctor_name 
                    FROM lab_tests lt
                    LEFT JOIN users u ON lt.doctor_id = u.user_id
                    LEFT JOIN registrations d ON u.registration_id = d.registration_id
                    WHERE lt.patient_id = $user_id 
                    ORDER BY lt.created_at DESC
                ";
                $res = $conn->query($sql);

                if ($res && $res->num_rows > 0):
                    while ($row = $res->fetch_assoc()):
                        $statusClass = 'status-' . $row['status'];
                ?>
                    <div class="report-card">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <h3 style="font-size: 16px; font-weight: 600; color: #fff; margin: 0;"><?php echo htmlspecialchars($row['test_name']); ?></h3>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                            </div>
                            <p style="font-size: 13px; color: #94a3b8; margin: 0;">
                                Requested by Dr. <?php echo htmlspecialchars($row['doctor_name']); ?> • 
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?> 
                                • <?php echo htmlspecialchars($row['test_type']); ?>
                            </p>
                            <?php if(!empty($row['instructions'])): ?>
                                <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Note: <?php echo htmlspecialchars($row['instructions']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($row['status'] == 'Completed' && !empty($row['report_path'])): ?>
                                <a href="<?php echo htmlspecialchars($row['report_path']); ?>" target="_blank" class="btn-consult" style="background: #3b82f6; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-file-pdf"></i> Download Report
                                </a>
                            <?php elseif ($row['status'] == 'Completed'): ?>
                                <span style="font-size: 12px; color: #10b981;">Report Finalized (No File)</span>
                            <?php elseif (($row['status'] == 'Pending' || $row['status'] == 'Requested') && ($row['payment_status'] ?? 'Pending') == 'Paid'): ?>
                                <button onclick="showAuthQR(<?php echo $row['labtest_id']; ?>, '<?php echo htmlspecialchars($row['test_name']); ?>')" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; padding: 8px 15px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-qrcode"></i> View Authorization Code
                                </button>
                            <?php elseif (($row['status'] == 'Pending' || $row['status'] == 'Requested') && ($row['payment_status'] ?? 'Pending') != 'Paid'): ?>
                                <span style="font-size: 12px; color: #f59e0b; background: rgba(245, 158, 11, 0.1); padding: 8px 15px; border-radius: 20px;">
                                    <i class="fas fa-exclamation-triangle"></i> Payment Pending
                                </span>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #64748b; background: rgba(255,255,255,0.05); padding: 8px 15px; border-radius: 20px;">
                                    <i class="fas fa-clock"></i> In Progress
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else: 
                ?>
                    <div class="empty-state">
                        <i class="fas fa-microscope" style="font-size: 48px;"></i>
                        <h3>No Lab Tests Found</h3>
                        <p>Any lab tests prescribed by your doctor will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
    <!-- Auth QR Modal -->
    <div id="qrModal" style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: #1e293b; padding: 30px; border-radius: 20px; text-align: center; max-width: 300px; width: 90%; border: 1px solid rgba(255,255,255,0.1);">
            <h3 style="color: #fff; margin-bottom: 5px;">Test Authorization</h3>
            <p id="qrTestName" style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;"></p>
            
            <div style="background: white; padding: 10px; border-radius: 10px; display: inline-block; margin-bottom: 20px;">
                <img id="qrImage" src="" alt="Auth QR" style="width: 180px; height: 180px;">
            </div>
            
            <p style="color: #10b981; font-size: 12px; font-weight: 600; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> Payment Verified
            </p>
            
            <button onclick="document.getElementById('qrModal').style.display='none'" style="background: rgba(255,255,255,0.1); color: white; border: none; padding: 10px 30px; border-radius: 8px; cursor: pointer;">Close</button>
        </div>
    </div>

    <script>
        function showAuthQR(id, name) {
            document.getElementById('qrTestName').textContent = name;
            document.getElementById('qrImage').src = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=HealCare_AUTH_" + id;
            document.getElementById('qrModal').style.display = 'flex';
        }

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
    <style>
        /* Override chatbot position for patient pages - position at bottom */
        .chatbot-toggler {
            bottom: 30px !important;
        }
        
        .chatbot {
            bottom: 110px !important;
        }
        
        @media (max-width: 490px) {
            .chatbot-toggler {
                bottom: 20px !important;
            }
        }
    </style>
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>
