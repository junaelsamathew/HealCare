<?php
session_start();
include 'includes/db_connect.php';

// Check auth
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$message = "";
$msg_type = "";

// Handle Cancellation Logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and normalize Booking ID (remove BK- prefix if present)
    $raw_booking_no = $_POST['booking_no'];
    $booking_id_cleaned = str_ireplace('BK-', '', $raw_booking_no);
    $booking_no = mysqli_real_escape_string($conn, $booking_id_cleaned);
    
    // Check if appointment exists and belongs to user
    $check_sql = "SELECT * FROM appointments WHERE appointment_id = '$booking_no' AND patient_id = '$user_id'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        $appt = $result->fetch_assoc();
        if ($appt['status'] == 'Cancelled') {
            $message = "This appointment is already cancelled.";
            $msg_type = "error";
        } elseif ($appt['status'] == 'Completed') {
            $message = "Cannot cancel a completed appointment.";
            $msg_type = "error";
        } else {
            // Cancel it
            $update_sql = "UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = '$booking_no'";
            if ($conn->query($update_sql)) {
                $message = "Appointment #$booking_no has been successfully cancelled.";
                $msg_type = "success";
            } else {
                $message = "Error cancelling appointment: " . $conn->error;
                $msg_type = "error";
            }
        }
    } else {
        $message = "Invalid Booking Number or Appointment not found.";
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Booking - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .cancel-container {
            max-width: 600px;
            margin: 40px auto;
            background: rgba(15, 23, 42, 0.8); /* Dark Blue */
            padding: 50px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .page-title {
            text-align: center;
            font-size: 2rem;
            color: white; 
            margin-bottom: 20px;
            font-weight: 700;
        }
        .warning-text {
            color: #fda4af; /* Lighter red */
            font-size: 0.9rem;
            margin-bottom: 30px;
            display: block;
        }
        .form-group-inline {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #f8fafc; /* White text */
            font-size: 1rem;
        }
        .input-booking {
            padding: 12px 15px;
            border: 1px solid #475569;
            background: #1e293b;
            color: white;
            border-radius: 6px;
            width: 250px;
            outline: none;
            font-size: 1rem;
        }
        .input-booking:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn-proceed {
            background: #f97316;
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            box-shadow: 0 4px 6px rgba(249, 115, 22, 0.2);
        }
        .btn-proceed:hover {
            background: #ea580c;
        }
        
        .alert-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
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

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Cancel Booking</h1>
            </div>

            <?php if(!empty($message)): ?>
                <div class="alert-box <?php echo $msg_type == 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="cancel-container">
                <span class="warning-text">* Please Note, Cancellation allowed only before allowed time frame</span>
                
                <form method="POST" action="">
                    <div class="form-group-inline">
                        <label class="form-label">Booking number</label>
                        <input type="text" class="input-booking" name="booking_no" placeholder="Enter ID" required>
                    </div>
                    
                    <button type="submit" class="btn-proceed">Proceed</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
