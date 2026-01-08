<?php
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
        $date = $appt_data['appointment_date'];
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; text-align: center; padding: 50px; }
        .success-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-top: 5px solid #10b981;
        }
        .success-icon { color: #10b981; font-size: 4rem; margin-bottom: 20px; }
        .details-grid {
            text-align: left;
            margin: 30px 0;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
        }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .row:last-child { border-bottom: none; }
        .btn-print {
            background: #1e40af;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 20px;
        }
        @media print {
            body * { visibility: hidden; }
            .success-card, .success-card * { visibility: visible; }
            .success-card { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">✓</div>
        <h2>Appointment Booked Successfully!</h2>
        <p>Your booking has been confirmed.</p>
        
        <div class="details-grid">
            <div class="row"><strong>Booking ID:</strong> <span><?php echo $booking_id; ?></span></div>
            <div class="row"><strong>Token Number:</strong> <span style="font-size:1.2rem; font-weight:bold; color:#f26522;"><?php echo $token; ?></span></div>
            <div class="row"><strong>Doctor:</strong> <span><?php echo $doctor_name; ?></span></div>
            <div class="row"><strong>Date:</strong> <span><?php echo $date; ?></span></div>
            <div class="row"><strong>Time:</strong> <span><?php echo $time; ?></span></div>
            <div class="row"><strong>Patient Name:</strong> <span><?php echo $patient_name; ?></span></div>
            <div class="row"><strong>Consultation Fee:</strong> <span style="font-weight: bold; color: #0369a1;">₹<?php echo number_format($fee, 0); ?></span></div>
            <?php if(isset($_GET['paid'])): ?>
                <div class="row"><strong>Payment Status:</strong> <span style="color:#10b981; font-weight:bold;">PAID ✓</span></div>
                <div class="row"><strong>Transaction ID:</strong> <span><?php echo $_GET['txn']; ?></span></div>
            <?php else: ?>
                <div class="row"><strong>Payment Status:</strong> <span style="color:#f59e0b; font-weight:bold;">PENDING</span></div>
            <?php endif; ?>
        </div>

        <?php if(!isset($_GET['paid']) && isset($_GET['bill_id'])): ?>
            <div style="background: #fff9db; padding: 20px; border-radius: 8px; border: 1px dashed #fab005; margin-bottom: 25px;">
                <p style="margin: 0 0 15px; color: #856404; font-weight: 500;">Please complete your payment of ₹<?php echo number_format($fee, 0); ?> to confirm your slot.</p>
                <a href="payment_process.php?bill_id=<?php echo $_GET['bill_id']; ?>" class="btn-print" style="text-decoration: none; display: inline-block; background: #00aeef; color: white;">Proceed to Payment</a>
            </div>
        <?php else: ?>
            <button class="btn-print" onclick="window.print()">Print Receipt</button>
        <?php endif; ?>
        
        <br><br>
        <a href="index.php" style="color: #666; text-decoration: none;">Return to Home</a>
    </div>
</body>
</html>
