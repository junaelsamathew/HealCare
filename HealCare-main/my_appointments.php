<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle Reason Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reason'])) {
    $aid = intval($_POST['appointment_id']);
    $new_reason = trim($_POST['reason']);
    
    // Validate ownership and status
    $check = $conn->prepare("SELECT status FROM appointments WHERE appointment_id = ? AND patient_id = ?");
    $check->bind_param("ii", $aid, $user_id);
    $check->execute();
    $res = $check->get_result();
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        // Allow edit only if not completed/cancelled/in-progress
        $locked_statuses = ['Completed', 'Cancelled', 'Checked-In', 'In Progress', 'Lab Completed'];
        if (!in_array($row['status'], $locked_statuses)) {
             if (strlen($new_reason) >= 10) {
                 $upd = $conn->prepare("UPDATE appointments SET reason = ? WHERE appointment_id = ?");
                 $upd->bind_param("si", $new_reason, $aid);
                 if($upd->execute()) {
                    echo "<script>alert('Symptoms updated successfully.'); window.location.href='my_appointments.php';</script>";
                 }
             } else {
                 echo "<script>alert('Description must be at least 10 characters.');</script>";
             }
        } else {
             echo "<script>alert('Cannot edit symptoms for this appointment status.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - HealCare</title>
    <link rel="stylesheet" href="styles/dashboard.css">
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

    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-Pending, .status-Requested { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-Scheduled, .status-Approved, .status-Confirmed { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-Completed, .status-Checked { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* Flow Steps CSS */
        .flow-steps {
            display: flex;
            align-items: center;
            margin-top: 15px;
            width: 100%;
            max-width: 400px;
        }
        .flow-step {
            display: flex;
            align-items: center;
            position: relative;
        }
        .step-circle {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: var(--text-gray);
            z-index: 2;
        }
        .step-label {
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            white-space: nowrap;
            color: var(--text-gray);
        }
        .step-line {
            flex: 1;
            height: 2px;
            background: rgba(255,255,255,0.1);
            margin: 0 5px;
            min-width: 50px;
        }
        
        .flow-step.active .step-circle {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
        }
        .flow-step.active .step-label { color: var(--primary-blue); font-weight: 600; }
        .flow-step.completed .step-circle {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        .flow-step.completed .step-label { color: #10b981; }
        .step-line.completed { background: #10b981; }
        .step-line.active { background: var(--primary-blue); opacity: 0.7; }
        
        /* Cancelled State */
        .flow-step.cancelled .step-circle { background: #ef4444; border-color: #ef4444; color: white; }
        .flow-step.cancelled .step-label { color: #ef4444; }
    </style>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link">Dashboard</a>
                <a href="book_appointment.php" class="nav-link">Book Appointment</a>
                <a href="my_appointments.php" class="nav-link active">My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header"><h1>My Appointments</h1><p>View and manage your scheduled hospital visits</p></div>

            <div class="content-section">
                <div class="section-head"><h3>Scheduled Appointments</h3></div>
                <div class="appointment-list">
                    <?php
                    $upcoming_sql = "SELECT a.*, r.name as doc_name, d.specialization 
                                    FROM appointments a 
                                    LEFT JOIN users u ON a.doctor_id = u.user_id 
                                    LEFT JOIN doctors d ON u.user_id = d.user_id 
                                    LEFT JOIN registrations r ON u.registration_id = r.registration_id
                                    WHERE a.patient_id = $user_id AND a.status IN ('Requested', 'Approved', 'Scheduled', 'Pending', 'Confirmed', 'Pending Lab')
                                    ORDER BY a.appointment_date ASC";
                    $upcoming_res = $conn->query($upcoming_sql);

                    if ($upcoming_res && $upcoming_res->num_rows > 0):
                        while($appt = $upcoming_res->fetch_assoc()):
                            $appt_time = date('M d, Y \a\t h:i A', strtotime($appt['appointment_date']));
                            $status = $appt['status'];
                            
                            // Determine Steps State
                            // 1. Requested
                            // 2. Approved
                            // 3. Completed
                            $s1 = 'completed'; 
                            $s2 = ''; 
                            $s3 = '';
                            $l1 = ''; $l2 = '';

                            if ($status == 'Pending' || $status == 'Requested') {
                                $s1 = 'active'; 
                            } elseif ($status == 'Approved' || $status == 'Scheduled' || $status == 'Confirmed') {
                                $s1 = 'completed'; $l1 = 'completed'; $s2 = 'active';
                            } elseif ($status == 'Pending Lab') {
                                $s1 = 'completed'; $l1 = 'completed'; $s2 = 'completed'; $l2 = 'active';
                            } elseif ($status == 'Completed') {
                                $s1 = 'completed'; $l1 = 'completed'; $s2 = 'completed'; $l2 = 'completed'; $s3 = 'completed';
                            }
                    ?>
                        <div class="appointment-item" style="display:block;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div class="doc-info">
                                    <h4><?php echo htmlspecialchars($appt['doc_name'] ?? 'Doctor'); ?></h4>
                                    <p><?php echo htmlspecialchars($appt['specialization'] ?? 'General'); ?> • <?php echo $appt_time; ?></p>
                                    <p style="font-size:0.85rem; color:#94a3b8; margin-top:4px;">Booking ID: <strong style="color:#e2e8f0; font-family:monospace;">BK-<?php echo $appt['appointment_id']; ?></strong></p>
                                    
                                    <?php if(!empty($appt['reason'])): ?>
                                        <div style="margin-top: 8px; font-size: 13px; color: #cbd5e1; background: rgba(255,255,255,0.05); padding: 8px 12px; border-radius: 6px;">
                                            <span style="color:#94a3b8; font-weight:600;">Symptoms:</span> <?php echo htmlspecialchars($appt['reason']); ?>
                                            <?php 
                                            $locked_statuses = ['Completed', 'Cancelled', 'Checked-In', 'Lab Completed'];
                                            if(!in_array($appt['status'], $locked_statuses)): 
                                            ?>
                                                <button onclick="openEditModal(<?php echo $appt['appointment_id']; ?>, `<?php echo addslashes($appt['reason']); ?>`)" style="background:none; border:none; color:var(--primary-blue); cursor:pointer; margin-left:10px; padding:0; font-size:12px; text-decoration:underline;">
                                                    Edit
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="status-badge status-<?php echo $appt['status']; ?>"><?php echo $appt['status']; ?></span>
                            </div>
                            
                            <!-- Flow Steps -->
                            <div class="flow-steps">
                                <div class="flow-step <?php echo $s1; ?>">
                                    <div class="step-circle"><i class="fas fa-paper-plane"></i></div>
                                    <span class="step-label">Requested</span>
                                </div>
                                <div class="step-line <?php echo $l1; ?>"></div>
                                <div class="flow-step <?php echo $s2; ?>">
                                    <div class="step-circle"><i class="fas fa-check"></i></div>
                                    <span class="step-label">Approved</span>
                                </div>
                                <div class="step-line <?php echo $l2; ?>"></div>
                                <div class="flow-step <?php echo $s3; ?>">
                                    <div class="step-circle"><i class="fas fa-flag-checkered"></i></div>
                                    <span class="step-label">Completed</span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="empty-state"><p>No upcoming appointments found. <a href="appointment_form.php">Book Now</a></p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-section" style="margin-top: 30px;">
                <div class="section-head"><h3>Past History</h3></div>
                <div class="appointment-list">
                    <?php
                    $past_sql = "SELECT a.*, r.name as doc_name, d.specialization 
                                FROM appointments a 
                                LEFT JOIN users u ON a.doctor_id = u.user_id 
                                LEFT JOIN doctors d ON u.user_id = d.user_id 
                                LEFT JOIN registrations r ON u.registration_id = r.registration_id
                                WHERE a.patient_id = $user_id AND a.status IN ('Completed', 'Cancelled', 'Checked')
                                ORDER BY a.appointment_date DESC";
                    $past_res = $conn->query($past_sql);

                    if ($past_res && $past_res->num_rows > 0):
                        while($appt = $past_res->fetch_assoc()):
                            $appt_time = date('M d, Y \a\t h:i A', strtotime($appt['appointment_date']));
                            
                            // For Past, usually Completed or Cancelled
                            // If Cancelled, show differnet flow?
                            $is_cancelled = ($appt['status'] == 'Cancelled');
                    ?>
                        <div class="appointment-item" style="display:block;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div class="doc-info">
                                    <h4><?php echo htmlspecialchars($appt['doc_name'] ?? 'Doctor'); ?></h4>
                                    <p><?php echo htmlspecialchars($appt['specialization'] ?? 'General'); ?> • <?php echo $appt_time; ?></p>
                                    <p style="font-size:0.85rem; color:#94a3b8; margin-top:4px;">Booking ID: <strong style="color:#e2e8f0; font-family:monospace;">BK-<?php echo $appt['appointment_id']; ?></strong></p>
                                </div>
                                <span class="status-badge status-<?php echo $appt['status']; ?>"><?php echo $appt['status']; ?></span>
                            </div>

                             <!-- Flow Steps (Static Completed for Past for now) -->
                             <?php if (!$is_cancelled): ?>
                             <div class="flow-steps">
                                <div class="flow-step completed">
                                    <div class="step-circle"><i class="fas fa-paper-plane"></i></div>
                                    <span class="step-label">Requested</span>
                                </div>
                                <div class="step-line completed"></div>
                                <div class="flow-step completed">
                                    <div class="step-circle"><i class="fas fa-check"></i></div>
                                    <span class="step-label">Approved</span>
                                </div>
                                <div class="step-line completed"></div>
                                <div class="flow-step completed">
                                    <div class="step-circle"><i class="fas fa-flag-checkered"></i></div>
                                    <span class="step-label">Completed</span>
                                </div>
                            </div>
                            <?php else: ?>
                                <div style="margin-top:15px; color:#ef4444; font-size:13px;"><i class="fas fa-times-circle"></i> This appointment was cancelled.</div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="empty-state"><p>No past appointments found.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Reason Modal -->
    <div id="editReasonModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#1e293b; padding:25px; border-radius:12px; width:90%; max-width:500px; border:1px solid rgba(255,255,255,0.1);">
            <h3 style="color:white; margin-top:0;">Update Symptoms</h3>
            <form method="POST">
                <input type="hidden" name="update_reason" value="1">
                <input type="hidden" name="appointment_id" id="modal_appt_id">
                <div style="margin-bottom:15px;">
                    <label style="display:block; color:#94a3b8; margin-bottom:5px;">Symptoms / Reason for Visit</label>
                    <textarea name="reason" id="modal_reason" rows="4" style="width:100%; padding:10px; background:#0f172a; border:1px solid #334155; color:white; border-radius:6px;" required minlength="10"></textarea>
                    <div style="font-size:12px; color:#64748b; margin-top:5px;">Minimum 10 characters.</div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="document.getElementById('editReasonModal').style.display='none'" style="padding:8px 16px; background:transparent; border:1px solid #475569; color:white; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:8px 16px; background:var(--primary-blue); border:none; color:white; border-radius:6px; cursor:pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, reason) {
            document.getElementById('modal_appt_id').value = id;
            document.getElementById('modal_reason').value = reason;
            document.getElementById('editReasonModal').style.display = 'flex';
        }
        
        // Close modal when clicking outside
        document.getElementById('editReasonModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"></body>
</html>
