<?php
session_start();
include 'includes/db_connect.php';
include 'includes/email_config.php';

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

                // --- SEND CANCELLATION EMAIL ---
                try {
                    // Fetch patient and appointment details for email
                    $stmt_details = $conn->prepare("SELECT a.*, d.user_id as doctor_user_id, r_doc.name as doctor_name, u_pat.email as patient_email, pp.name as patient_name, pp.phone as patient_phone 
                                                 FROM appointments a 
                                                 JOIN users u_pat ON a.patient_id = u_pat.user_id
                                                 JOIN patient_profiles pp ON u_pat.user_id = pp.user_id
                                                 JOIN doctors d ON a.doctor_id = d.user_id
                                                 JOIN users u_doc ON d.user_id = u_doc.user_id
                                                 JOIN registrations r_doc ON u_doc.registration_id = r_doc.registration_id
                                                 WHERE a.appointment_id = ?");
                    $stmt_details->bind_param("i", $booking_no);
                    $stmt_details->execute();
                    $details_res = $stmt_details->get_result();
                    
                    if ($details_res && $row = $details_res->fetch_assoc()) {
                        $mail = new PHPMailer(true);
                        configureDefaultMail($mail);
                        $mail->addAddress($row['patient_email'], $row['patient_name']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Appointment Cancelled - HealCare Hospital';
                        
                        $mail->Body = '
                        <div style="font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #eee; border-radius: 10px; color: #333;">
                            <h2 style="color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px;">Appointment Cancellation</h2>
                            <p>Dear <strong>' . htmlspecialchars($row['patient_name']) . '</strong>,</p>
                            <p>This is to confirm that your appointment has been <strong>cancelled</strong> as per your request.</p>
                            
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ef4444;">
                                <p style="margin: 5px 0;"><strong>Booking ID:</strong> BK-' . $booking_no . '</p>
                                <p style="margin: 5px 0;"><strong>Doctor:</strong> ' . htmlspecialchars($row['doctor_name']) . '</p>
                                <p style="margin: 5px 0;"><strong>Scheduled Date:</strong> ' . date('d M Y', strtotime($row['appointment_date'])) . '</p>
                                <p style="margin: 5px 0;"><strong>Scheduled Time:</strong> ' . date('h:i A', strtotime($row['appointment_time'])) . '</p>
                            </div>
                            
                            <p>If you wish to reschedule, please visit our portal again.</p>
                            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                            <p style="font-size: 0.8em; color: #777; text-align: center;">Sent by HealCare Hospital System</p>
                        </div>';
                        
                        $mail->send();

                    }
                } catch (Exception $e) {
                    // Log error or ignore - we don't want to break the success message if email fails
                }
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
