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
    <link rel="stylesheet" href="styles/dashboard.css">
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
                <a href="book_appointment.php" class="nav-link">Book Appointment</a>
                <a href="my_appointments.php" class="nav-link">My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link active"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
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
                            <h4 style="font-size: 18px; color: #fff;"><?php echo htmlspecialchars($p_row['doctor_name']); ?></h4>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"></body>
</html>
