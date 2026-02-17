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
    <title>My Health Packages - HealCare</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .pkg-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .pkg-item {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .pkg-item:hover {
            transform: translateY(-5px);
            border-color: #10b981;
        }
        .pkg-status {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .pkg-icon {
            width: 50px;
            height: 50px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #10b981;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .pkg-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 10px;
        }
        .pkg-date {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        .pkg-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .view-link {
            color: #3b82f6;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: #1e293b;
            border-radius: 20px;
            border: 2px dashed rgba(255,255,255,0.05);
        }
        .empty-state i {
            font-size: 50px;
            color: #334155;
            margin-bottom: 20px;
        }
        .empty-state h3 { color: #fff; margin-bottom: 10px; }
        .empty-state p { color: #94a3b8; margin-bottom: 25px; }
        .btn-browse {
            display: inline-block;
            background: #3b82f6;
            color: #fff;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-browse:hover { background: #2563eb; }
    </style>
</head>
<body>



    <!-- Top Bar -->
    <div class="top-bar" style="background-color: #ffffff; padding: 15px 0; border-bottom: 1px solid #e0e0e0; font-family: 'Poppins', sans-serif;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="top-bar-content" style="display: flex; justify-content: space-between; align-items: center;">
                <a href="index.php" class="logo" style="text-decoration: none; display: flex; align-items: center; gap: 5px;">
                    <img src="images/healcare_logo.jpg" alt="HealCare Logo" class="logo-img" style="height: 65px; width: auto; object-fit: contain;"> 
                    <style>
                        @keyframes revealLetter {
                            0% { opacity: 0; transform: translateY(10px); }
                            100% { opacity: 1; transform: translateY(0); }
                        }
                    </style>
                    <span class="animated-brand" style="font-size: 28px; font-weight: 700; color: #0a1f44; font-family: 'Poppins', sans-serif;">
                        <?php 
                        $text = "HEALCARE HOSPITAL";
                        $chars = str_split($text);
                        foreach ($chars as $index => $char) {
                            $delay = $index * 0.1;
                            if ($char === ' ') {
                                echo "&nbsp;";
                            } else {
                                echo "<span style='display:inline-block; opacity:0; animation: revealLetter 0.5s forwards {$delay}s;'>$char</span>";
                            }
                        }
                        ?>
                    </span>
                </a>
                <div class="top-info" style="display: flex; gap: 40px;">
                    <div class="info-item" style="display: flex; align-items: center; gap: 10px;">
                        <div class="info-icon" style="width: 40px; height: 40px; border: 2px solid #0a1f44; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0a1f44;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div class="info-text" style="display: flex; flex-direction: column;">
                            <span class="info-label" style="font-size: 11px; font-weight: 600; color: #0a1f44; letter-spacing: 0.5px; font-family: 'Poppins', sans-serif;">WHATSAPP</span>
                            <a href="https://wa.me/919539045609" target="_blank" class="info-value" style="text-decoration: none; color: #25d366; font-size: 13px; font-weight: 500; font-family: 'Poppins', sans-serif;"><i class="fab fa-whatsapp"></i> (+91) 953 904 5609</a>
                        </div>
                    </div>

                    <div class="info-item" style="display: flex; align-items: center; gap: 10px;">
                        <div class="info-icon" style="width: 40px; height: 40px; border: 2px solid #0a1f44; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0a1f44;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div class="info-text" style="display: flex; flex-direction: column;">
                            <span class="info-label" style="font-size: 11px; font-weight: 600; color: #0a1f44; letter-spacing: 0.5px; font-family: 'Poppins', sans-serif;">LOCATION</span>
                            <span class="info-value" style="font-size: 13px; color: #1e90ff; font-weight: 500; font-family: 'Poppins', sans-serif;">Kanjirapally, Kottayam</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="my_packages.php" class="nav-link active"><i class="fas fa-box-medical"></i> My Health Packages</a>
                <a href="patient_lab_results.php" class="nav-link"><i class="fas fa-flask"></i> Lab Reports</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="patient_feedback.php" class="nav-link"><i class="fas fa-comment-dots"></i> Patient Feedback</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header" style="margin-bottom: 30px;">
                <h1 style="color: #fff; font-size: 28px; font-weight: 700; margin: 0;">My Purchased Packages</h1>
                <p style="color: #94a3b8; font-size: 14px; margin-top: 5px;">View and manage your active health checkup plans.</p>
            </div>

            <?php
            $sql = "SELECT * FROM billing WHERE patient_id = $user_id AND bill_type = 'Health Package' AND payment_status = 'Paid' ORDER BY bill_date DESC";
            $res = $conn->query($sql);

            if ($res && $res->num_rows > 0):
            ?>
                <div class="pkg-grid">
                    <?php 
                    while ($pkg = $res->fetch_assoc()): 
                        $desc = $pkg['description'];
                        $parts = explode(" for ", $desc);
                        $p_name = str_replace("Health Package Booking: ", "", $parts[0]);
                        $p_date = $parts[1] ?? $pkg['bill_date'];
                    ?>
                        <div class="pkg-item">
                            <span class="pkg-status">ACTIVE</span>
                            <div class="pkg-icon">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h3 class="pkg-name"><?php echo htmlspecialchars($p_name); ?></h3>
                            <div class="pkg-date">
                                <i class="far fa-calendar-alt" style="margin-right: 8px;"></i>
                                Scheduled: <?php echo date('M d, Y', strtotime($p_date)); ?>
                            </div>
                            <div style="font-size: 12px; color: #64748b;">
                                Booking Date: <?php echo date('M d, Y', strtotime($pkg['bill_date'])); ?>
                            </div>
                            <div class="pkg-footer">
                                <span style="font-weight: 700; color: #10b981; font-size: 1.1rem;">₹<?php echo number_format($pkg['total_amount']); ?></span>
                                <a href="print_receipt.php?bill_id=<?php echo $pkg['bill_id']; ?>" target="_blank" class="view-link">
                                    <i class="fas fa-file-invoice"></i> View Receipt
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No packages found</h3>
                    <p>You haven't purchased any health packages yet. Early detection is clinical protection.</p>
                    <a href="health_packages.php" class="btn-browse">Browse Health Packages</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>

    <style>
        .chatbot-toggler { bottom: 30px !important; }
        .chatbot { bottom: 110px !important; }
    </style>
</body>
</html>
