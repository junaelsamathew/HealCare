<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$doctor_username = $_SESSION['username'];

if (!isset($_GET['id'])) {
    header("Location: doctor_patients.php");
    exit();
}

$patient_id = intval($_GET['id']);

// Fetch Patient Name
$stmt = $conn->prepare("SELECT name FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE u.user_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    echo "Patient not found.";
    exit();
}
$patient_name = $res->fetch_assoc()['name'];

// Fetch Appointment History
$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        r.name as doc_name,
        d.department,
        mr.diagnosis,
        mr.treatment,
        p.medicine_details as prescription
    FROM appointments a
    JOIN users u_doc ON a.doctor_id = u_doc.user_id
    JOIN registrations r ON u_doc.registration_id = r.registration_id
    LEFT JOIN doctors d ON u_doc.user_id = d.user_id
    LEFT JOIN medical_records mr ON a.appointment_id = mr.appointment_id
    LEFT JOIN prescriptions p ON mr.prescription_id = p.prescription_id
    WHERE a.patient_id = ? AND a.status IN ('Completed', 'Checked', 'Confirmed', 'Scheduled', 'Cancelled')
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$history = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient History - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: rgba(255, 255, 255, 0.1);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
        }
        .timeline-dot {
            position: absolute;
            left: -36px;
            top: 20px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #3b82f6;
            border: 3px solid #0f172a;
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 10px;
        }
        .date-badge {
            background: rgba(37, 99, 235, 0.2);
            color: #60a5fa;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .dept-badge {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .diagnosis-box {
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid #10b981;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .status-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .status-Completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .status-Scheduled { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="doctor_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="doctor_patients.php" class="nav-link active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <a href="doctor_patients.php" style="color: #94a3b8; text-decoration: none; font-size: 13px;"><i class="fas fa-arrow-left"></i> Back to Patients</a>
                    <h1 style="margin-top: 10px;">Medical History</h1>
                    <p>Timeline for <strong><?php echo htmlspecialchars($patient_name); ?></strong></p>
                </div>
            </div>

            <div class="timeline">
                <?php 
                if ($history->num_rows > 0):
                    while ($rec = $history->fetch_assoc()):
                        // Color coding dot based on status
                        $dot_color = '#3b82f6';
                        if ($rec['status'] == 'Cancelled') $dot_color = '#ef4444';
                        if ($rec['status'] == 'Completed') $dot_color = '#10b981';
                ?>
                <div class="timeline-item">
                    <div class="timeline-dot" style="background: <?php echo $dot_color; ?>;"></div>
                    <div class="item-header">
                        <div>
                            <span class="date-badge"><?php echo date('d M Y', strtotime($rec['appointment_date'])); ?></span>
                            <span style="color: #fff; font-weight: 500; margin-left: 10px;"><?php echo htmlspecialchars($rec['doc_name']); ?></span>
                            <span class="dept-badge"> • <?php echo htmlspecialchars($rec['department']); ?></span>
                        </div>
                        <span class="status-badge status-<?php echo $rec['status']; ?>"><?php echo $rec['status']; ?></span>
                    </div>

                    <?php if (!empty($rec['diagnosis'])): ?>
                    <div class="diagnosis-box">
                        <strong style="color: #10b981; font-size: 12px; display: block; margin-bottom: 3px;">DIAGNOSIS</strong>
                        <span style="color: #cbd5e1;"><?php echo htmlspecialchars($rec['diagnosis']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($rec['prescription'])): ?>
                    <div style="margin-top: 10px;">
                        <strong style="color: #60a5fa; font-size: 12px;"><i class="fas fa-pills"></i> Prescription:</strong>
                        <p style="color: #94a3b8; font-size: 13px; margin-top: 5px;"><?php echo nl2br(htmlspecialchars($rec['prescription'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($rec['diagnosis']) && $rec['status'] == 'Completed'): ?>
                        <p style="color: #64748b; font-style: italic; font-size: 12px;">No clinical notes recorded for this visit.</p>
                    <?php endif; ?>
                </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                <div style="padding: 40px; text-align: center; color: #94a3b8; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px;">
                    <i class="fas fa-history" style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;"></i><br>
                    No appointment history found for this patient.
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div></body>
</html>
