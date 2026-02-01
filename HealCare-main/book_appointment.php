<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    $redirect = urlencode(basename($_SERVER['PHP_SELF']));
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= urlencode('?' . $_SERVER['QUERY_STRING']);
    }
    header("Location: login.php?redirect=$redirect");
    exit();
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - HealCare</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cards-wrapper {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 40px;
        }
        .option-card {
            background: rgba(255,255,255,0.03);
            padding: 40px;
            border-radius: 16px;
            width: 280px;
            text-decoration: none;
            color: var(--text-light);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .option-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.05);
            border-color: var(--primary-blue);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 2.5rem;
        }
        .icon-booking {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-blue);
        }
        .icon-cancel {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .card-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-light);
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
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Appointments</h1>
                <p>Schedule a new visit or cancel an existing one</p>
            </div>
        
            <div class="cards-wrapper">
                <!-- Booking Card -->
                <a href="appointment_form.php" class="option-card">
                    <div class="icon-box icon-booking">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <span class="card-label">Book New Appointment</span>
                    <span style="font-size: 0.85rem; color: var(--text-gray); margin-top: 5px;">Schedule a consultation with a doctor</span>
                </a>

                <!-- Cancel Card -->
                <a href="cancel_booking.php" class="option-card">
                    <div class="icon-box icon-cancel">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <span class="card-label">Cancel Booking</span>
                    <span style="font-size: 0.85rem; color: var(--text-gray); margin-top: 5px;">Cancel an existing scheduled appointment</span>
                </a>
            </div>
        </main>
    </div></body>
</html>
