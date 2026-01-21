<?php
session_start();
include 'includes/db_connect.php';
// Support both POST (direct) and GET (redirect)
$token = $_REQUEST['token'] ?? null;
$doctor_input = $_REQUEST['doctor'] ?? null;
$date = $_REQUEST['date'] ?? null;
$time = $_REQUEST['time'] ?? null;
$patient_name = $_REQUEST['patient'] ?? null;
$fee = $_REQUEST['fee'] ?? null;

$booking_id = $_REQUEST['booking_id'] ?? '';
$appt_id = str_replace('BK-', '', $booking_id);

if ((empty($token) || $token == '00') && is_numeric($appt_id)) {
    // If details are missing, fetch them from DB using Appointment ID
    $stmt = $conn->prepare("SELECT a.*, d.consultation_fee, u_doc.username as doc_username, 
                            r_doc.name as doc_realname, r_pat.name as pat_realname 
                            FROM appointments a 
                            LEFT JOIN users u_doc ON a.doctor_id = u_doc.user_id 
                            LEFT JOIN registrations r_doc ON u_doc.registration_id = r_doc.registration_id
                            LEFT JOIN users u_pat ON a.patient_id = u_pat.user_id 
                            LEFT JOIN registrations r_pat ON u_pat.registration_id = r_pat.registration_id
                            LEFT JOIN doctors d ON a.doctor_id = d.user_id
                            WHERE a.appointment_id = ?");
    $stmt->bind_param("i", $appt_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $res->num_rows > 0) {
        $appt_data = $res->fetch_assoc();
        
        $token = $appt_data['queue_number'] ?? $appt_data['token_no'] ?? '00';
        $doctor_name = $appt_data['doc_realname'] ?? $appt_data['doc_username'] ?? 'Doctor';
        $date = date('Y-m-d', strtotime($appt_data['appointment_date']));
        $time = date('h:i A', strtotime($appt_data['appointment_time'])); // Format time
        $patient_name = $appt_data['pat_realname'] ?? 'Valued Patient';
        $fee = $appt_data['consultation_fee'] ?? 200;
        
    } else {
        $doctor_name = "Unknown";
        $token = "00";
    }
} else {
    // Legacy/Direct handling
    $token = $token ?? '00';
    $time = $time ?? '00:00';
    
    if (is_numeric($doctor_input)) {
        // Fetch doctor name if ID passed
        $doc_q = $conn->query("SELECT r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.user_id = '$doctor_input'");
        if ($doc_q && $doc_q->num_rows > 0) {
            $doctor_name = $doc_q->fetch_assoc()['name'];
        } else {
            $doctor_name = "Doctor";
        }
    } else {
        $doctor_name = $doctor_input ?: "Doctor";
    }
    
    if(!$patient_name) $patient_name = 'Valued Patient';
    if(!$fee) $fee = 200;
}

$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($_GET['paid']) || (isset($_GET['from']) && $_GET['from'] == 'payment')) ? 'Payment Confirmed' : 'Booking Confirmed'; ?> - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .success-card {
            background: #0f172a;
            padding: 40px;
            border-radius: 12px;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            text-align: center;
        }
        .success-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #059669);
        }
        .success-icon { 
            color: #10b981; 
            font-size: 3.5rem; 
            margin-bottom: 20px; 
            background: rgba(16, 185, 129, 0.1);
            width: 80px; height: 80px;
            display: inline-flex;
            align-items: center; justify-content: center;
            border-radius: 50%;
        }
        h2 { color: white; margin-bottom: 10px; font-weight: 600; }
        p { color: var(--text-gray); margin-bottom: 30px; }

        .details-grid {
            text-align: left;
            margin: 30px 0;
            background: rgba(255,255,255,0.03);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .row { 
            display: flex; 
            justify-content: space-between; 
            padding: 12px 0; 
            border-bottom: 1px solid var(--border-color); 
            color: var(--text-light);
            font-size: 0.95rem;
        }
        .row:last-child { border-bottom: none; }
        .row strong { color: var(--text-gray); font-weight: 500; }
        
        .btn-print {
            background: var(--primary-blue);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }
        .btn-print:hover { background: #2563eb; }

        .payment-box {
            background: rgba(245, 158, 11, 0.1);
            padding: 20px;
            border-radius: 8px;
            border: 1px dashed rgba(245, 158, 11, 0.4);
            margin-bottom: 25px;
        }

        @media print {
            body { background: white; color: black; }
            .sidebar, .top-header, .secondary-header { display: none; }
            .dashboard-layout { padding: 0; height: auto; }
            .main-content { overflow: visible; }
            .success-card { 
                box-shadow: none; 
                border: 1px solid #ddd; 
                background: white; 
                color: black;
                width: 100%;
                max-width: 100%;
            }
            .success-card::before { display: none; }
            .details-grid { background: #fff; border: 1px solid #eee; color: black; }
            .row { border-color: #eee; color: black; }
            .row strong { color: #555; }
            h2, p { color: black; }
            .btn-print, .payment-box a { display: none; }
        }
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

        <main class="main-content" style="display: flex; align-items: center; justify-content: center;">
            <div class="success-card">
                <div class="success-icon"><i class="fas fa-check"></i></div>
                <?php if(isset($_GET['paid']) || (isset($_GET['from']) && $_GET['from'] == 'payment')): ?>
                    <h2>Payment Confirmed!</h2>
                    <p>Your medical services payment has been successfully processed.</p>
                <?php else: ?>
                    <h2>Appointment Confirmed!</h2>
                    <p>Your request has been successfully submitted.</p>
                <?php endif; ?>
                
                <div class="details-grid">
                    <div class="row"><strong>Booking ID:</strong> <span style="font-family:monospace;"><?php echo $booking_id; ?></span></div>
                    <div class="row"><strong>Token Number:</strong> <span style="font-size:1.1rem; font-weight:700; color:#f59e0b;"><?php echo $token; ?></span></div>
                    <div class="row"><strong>Doctor:</strong> <span><?php echo $doctor_name; ?></span></div>
                    <div class="row"><strong>Date / Time:</strong> <span><?php echo date('M d, Y', strtotime($date)); ?> &bull; <?php echo $time; ?></span></div>
                    <div class="row"><strong>Patient:</strong> <span><?php echo htmlspecialchars($patient_name); ?></span></div>
                    <div class="row"><strong>Medical Services Bill:</strong> <span>₹<?php echo number_format($fee, 0); ?></span></div>
                    <?php if(isset($_GET['paid'])): ?>
                        <div class="row"><strong>Status:</strong> <span style="color:#10b981; font-weight:bold;">PAID</span></div>
                    <?php else: ?>
                        <div class="row"><strong>Status:</strong> <span style="color:#f59e0b; font-weight:bold;">PENDING</span></div>
                    <?php endif; ?>
                </div>

                <?php if(!isset($_GET['paid']) && isset($_GET['bill_id'])): ?>
                    <div class="payment-box">
                        <p style="margin: 0 0 15px; color: #f59e0b; font-weight: 500; font-size:0.95rem;">Please complete your payment to finalize the slot.</p>
                        <a href="payment_gateway.php?bill_id=<?php echo $_GET['bill_id']; ?>" class="btn-print">Proceed to Payment</a>
                    </div>
                <?php else: ?>
                    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Details</button>
                    <div style="margin-top: 20px;">
                        <a href="my_appointments.php" style="color: var(--text-gray); font-size: 0.9rem;">View My Appointments</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
