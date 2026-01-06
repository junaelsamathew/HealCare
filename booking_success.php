<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: book_appointment.php");
    exit();
}
// Capture Data
$token = $_POST['token'] ?? '00';
$doctor = $_POST['doctor_name'] ?? 'Unknown';
$date = $_POST['date'] ?? date('Y-m-d');
$time = $_POST['time_slot'] ?? '00:00';
$name = isset($_POST['first_name']) ? $_POST['first_name'] . ' ' . $_POST['last_name'] : 'Registered Patient';
$booking_id = "BK-" . rand(100000, 999999);
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
            <div class="row"><strong>Doctor:</strong> <span><?php echo $doctor; ?></span></div>
            <div class="row"><strong>Date:</strong> <span><?php echo $date; ?></span></div>
            <div class="row"><strong>Time:</strong> <span><?php echo $time; ?></span></div>
            <div class="row"><strong>Patient Name:</strong> <span><?php echo $name; ?></span></div>
        </div>

        <button class="btn-print" onclick="window.print()">Print Receipt</button>
        <br><br>
        <a href="index.php" style="color: #666; text-decoration: none;">Return to Home</a>
    </div>
</body>
</html>
