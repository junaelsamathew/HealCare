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
    <title>Ambulance Service - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

        /* Ambulance Grid & Cards */
        .ambulance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .ambulance-card {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
        }

        .ambulance-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary-blue);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(59, 130, 246, 0.1);
        }

        .ambulance-img-wrapper {
            width: 100%;
            height: 180px;
            overflow: hidden;
            position: relative;
        }

        .ambulance-branding {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(transparent, rgba(0,0,0,0.95));
            padding: 25px 15px 12px;
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            z-index: 5;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ambulance-branding::before {
            content: '+';
            color: #ff3333;
            font-size: 24px;
            font-family: serif;
            font-weight: bold;
        }

        .ambulance-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .ambulance-card:hover .ambulance-img-wrapper img {
            transform: scale(1.1);
        }

        .ambulance-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            z-index: 10;
            backdrop-filter: blur(5px);
        }

        .badge-avail { background: rgba(16, 185, 129, 0.8); color: white; }
        .badge-duty { background: rgba(245, 158, 11, 0.8); color: white; }
        .badge-off { background: rgba(239, 68, 68, 0.8); color: white; }

        .ambulance-content {
            padding: 20px;
        }

        .ambulance-title {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .ambulance-type {
            font-size: 12px;
            color: var(--primary-blue);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .ambulance-info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #94a3b8;
            font-size: 13px;
        }

        .ambulance-info-row i {
            width: 20px;
            color: #64748b;
        }

        .btn-call {
            width: 100%;
            padding: 12px;
            background: #25d366;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            margin-top: 15px;
            transition: 0.3s;
        }

        .btn-call:hover {
            background: #128c7e;
            transform: scale(1.02);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            overflow-y: auto;
            padding: 40px 0;
        }

        .modal-content {
            background: #0f172a;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.1);
            width: 500px;
            border-radius: 20px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            color: #94a3b8;
            font-size: 24px;
            cursor: pointer;
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
                    <a href="https://wa.me/918075454467" target="_blank" style="font-size: 13px; color: #25d366; font-weight: 600; text-decoration: none;"><i class="fab fa-whatsapp"></i> (+91) 807 545 4467</a>
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

    <!-- Secondary Header -->
    <header class="secondary-header">
        <div style="flex: 1;"></div>
        <div class="user-controls">
            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($username); ?></strong></span>
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
                <a href="patient_lab_results.php" class="nav-link"><i class="fas fa-flask"></i> Lab Reports</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="patient_ambulance.php" class="nav-link active"><i class="fas fa-ambulance"></i> Ambulance Service</a>
                <a href="patient_feedback.php" class="nav-link"><i class="fas fa-comment-dots"></i> Patient Feedback</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Ambulance Emergency Service</h1>
                <p>24/7 emergency contact numbers and ambulance availability</p>
            </div>

            <div class="content-section" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                <div class="section-head" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; margin-bottom: 25px;">
                    <h3 style="display: flex; align-items: center; gap: 12px; color: #fff;">
                        <i class="fas fa- ambulance" style="color: #ff3333;"></i> Ready for Emergency
                    </h3>
                </div>

                <div class="ambulance-grid">
                    <?php
                    $sql = "SELECT * FROM ambulance_contacts ORDER BY created_at DESC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0):
                        while($amb = $result->fetch_assoc()):
                            $status_class = 'badge-avail';
                            if ($amb['availability'] == 'On Duty') $status_class = 'badge-duty';
                            if ($amb['availability'] == 'Off Duty') $status_class = 'badge-off';

                            $img_url = !empty($amb['image_url']) ? $amb['image_url'] : 'https://upload.wikimedia.org/wikipedia/commons/6/6d/Ambulance_in_London.jpg';
                    ?>
                        <div class="ambulance-card">
                            <div onclick='viewAmbulance(<?php echo json_encode($amb); ?>)'>
                                <div class="ambulance-img-wrapper">
                                    <?php 
                                        $final_src = $img_url;
                                        if (strpos($final_src, '?') !== false) {
                                            $final_src .= '&v=' . time();
                                        } else {
                                            $final_src .= '?v=' . time();
                                        }
                                    ?>
                                    <img src="<?php echo $final_src; ?>" alt="Ambulance" referrerpolicy="no-referrer" onerror="this.onerror=null; this.src='https://upload.wikimedia.org/wikipedia/commons/6/6d/Ambulance_in_London.jpg';">
                                    <div class="ambulance-branding">HEALCARE HOSPITAL</div>
                                </div>
                                <div class="ambulance-content" style="padding-bottom: 5px;">
                                    <div class="ambulance-type">
                                        <i class="fas fa-microchip"></i> <?php echo htmlspecialchars($amb['vehicle_type']); ?>
                                    </div>
                                    <h4 class="ambulance-title"><?php echo htmlspecialchars($amb['driver_name']); ?></h4>
                                    <div class="ambulance-info-row">
                                        <i class="fas fa-hashtag"></i> Reg: <?php echo htmlspecialchars($amb['vehicle_number']); ?>
                                    </div>
                                    <div class="ambulance-info-row">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($amb['location']); ?>
                                    </div>
                                    <div class="ambulance-info-row" style="color: #4fc3f7; font-weight: 600; font-size: 15px;">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($amb['phone_number']); ?>
                                    </div>
                                </div>
                            </div>
                            <div style="padding: 0 20px 20px;">
                                <a href="https://wa.me/918075454467" target="_blank" class="btn-call">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
                            <i class="fas fa-info-circle" style="font-size: 40px; color: #1e293b; margin-bottom: 20px;"></i>
                            <p style="color: #94a3b8;">No registered ambulance units found. For emergency, call hospital hotline.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Details Modal -->
    <div id="ambDetailModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close-modal" onclick="closeModal('ambDetailModal')">&times;</span>
            <div id="amb_details_render">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <img id="det_img" src="" style="width: 100%; height: 350px; object-fit: cover; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                    </div>
                    <div style="display: flex; flex-direction: column; justify-content: center;">
                        <h2 id="det_driver" style="margin: 0 0 10px 0; font-size: 28px;">Driver Name</h2>
                        <p id="det_type" style="color: #4fc3f7; font-weight: 600; margin-bottom: 25px;">Ambulance Type</p>
                        
                        <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 15px; display: flex; flex-direction: column; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(79, 195, 247, 0.1); display: flex; align-items: center; justify-content: center; color: #4fc3f7;">
                                    <i class="fas fa-car"></i>
                                </div>
                                <div>
                                    <small style="color: #64748b; text-transform: uppercase; font-size: 10px; font-weight: 800;">Vehicle Registration</small>
                                    <p id="det_vno" style="margin: 0; font-weight: 600;"></p>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: #10b981;">
                                    <i class="fas fa-map-marked-alt"></i>
                                </div>
                                <div>
                                    <small style="color: #64748b; text-transform: uppercase; font-size: 10px; font-weight: 800;">Base Location</small>
                                    <p id="det_loc" style="margin: 0; font-weight: 600;"></p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; color: #3b82f6;">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <small style="color: #64748b; text-transform: uppercase; font-size: 10px; font-weight: 800;">Direct Contact</small>
                                    <p id="det_phone" style="margin: 0; font-weight: 700; color: #3b82f6; font-size: 18px;"></p>
                                </div>
                            </div>
                        </div>

                        <a id="det_call_btn" href="" class="btn-call" style="background: #3b82f6; margin-top: 25px; height: 50px;">
                            <i class="fas fa-phone-alt"></i> CALL DRIVER NOW
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function viewAmbulance(amb) {
            document.getElementById('det_driver').innerText = amb.driver_name;
            document.getElementById('det_type').innerText = amb.vehicle_type;
            document.getElementById('det_vno').innerText = amb.vehicle_number;
            document.getElementById('det_loc').innerText = amb.location;
            document.getElementById('det_phone').innerText = amb.phone_number;
            document.getElementById('det_call_btn').href = 'tel:' + amb.phone_number.replace(/[^\d+]/g, '');
            
            const imgUrl = amb.image_url ? amb.image_url : 'https://upload.wikimedia.org/wikipedia/commons/6/6d/Ambulance_in_London.jpg';
            const sep = imgUrl.includes('?') ? '&' : '?';
            document.getElementById('det_img').src = imgUrl + sep + 'v=<?php echo time(); ?>';
            
            openModal('ambDetailModal');
        }

        // Brand Animation
        document.addEventListener('DOMContentLoaded', function() {
            function initBrandAnimation() {
                const brandElement = document.querySelector('.animated-brand');
                if (!brandElement) return;
                const text = brandElement.textContent.trim();
                brandElement.textContent = '';
                const letters = [];
                for (let char of text) {
                    const span = document.createElement('span');
                    span.textContent = char;
                    span.classList.add('brand-letter');
                    if (char === ' ') {
                        span.style.width = '0.3em';
                        span.style.display = 'inline-block';
                    }
                    brandElement.appendChild(span);
                    letters.push(span);
                }
                function animate() {
                    letters.forEach((letter, index) => {
                        setTimeout(() => { letter.classList.add('visible'); }, index * 100);
                    });
                    const totalTime = (letters.length * 100) + 2000;
                    setTimeout(() => {
                        letters.forEach((letter, index) => {
                            setTimeout(() => { letter.classList.remove('visible'); }, index * 50);
                        });
                    }, totalTime);
                    const cycleTime = totalTime + (letters.length * 50) + 500;
                    setTimeout(animate, cycleTime);
                }
                animate();
            }
            initBrandAnimation();
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
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
