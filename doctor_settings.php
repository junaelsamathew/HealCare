<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch actual doctor professional info
$stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $doctor = $res->fetch_assoc();
    $specialization = $doctor['specialization'];
    $department = $doctor['department'];
    $designation = $doctor['designation'];
    $qualification = $doctor['qualification'];
    $experience = $doctor['experience'];
} else {
    $specialization = "General Healthcare";
    $department = "General Medicine";
    $designation = "Professional Consultant";
    $qualification = "MBBS";
    $experience = "5";
}

// Fetch Personal Details from Registrations
$stmt_reg = $conn->prepare("SELECT r.phone, r.address, r.email, r.profile_photo FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE u.user_id = ?");
$stmt_reg->bind_param("i", $user_id);
$stmt_reg->execute();
$res_reg = $stmt_reg->get_result();
$reg_data = $res_reg->fetch_assoc();

$doctor_email = $reg_data['email'] ?? 'doctor@healcare.com';
$doctor_phone = $reg_data['phone'] ?? '';
$doctor_address = $reg_data['address'] ?? '';

$doctor_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);
if (stripos($doctor_name, 'Dr.') === false && stripos($doctor_name, 'Doctor') === false) {
    $doctor_name = "Dr. " . $doctor_name;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
        }
        .settings-nav {
            background: rgba(30, 41, 59, 0.4);
            border-radius: 20px;
            padding: 20px;
            height: fit-content;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .settings-nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        .settings-nav-link.active {
            background: rgba(79, 195, 247, 0.1);
            color: #4fc3f7;
        }
        .settings-nav-link:hover:not(.active) {
            background: rgba(255,255,255,0.03);
            color: #fff;
        }
        .settings-section {
            background: rgba(30, 41, 59, 0.4);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 30px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; color: #94a3b8; font-weight: 600; }
        .form-input { 
            width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); 
            padding: 12px; border-radius: 10px; color: #fff; outline: none;
        }
        .btn-save {
            background: #10b981; color: #fff; padding: 12px 30px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-user-cog"></i></div>
                <div class="info-details"><span class="info-label">PROFILE</span><span class="info-value">Management</span></div>
            </div>
        </div>
    </header>

    <header class="secondary-header">
        <div class="brand-section">
            <div class="brand-icon">+</div>
            <div class="brand-name">HealCare</div>
        </div>
        <div class="user-controls">
            <span class="user-greeting">Welcome, <strong><?php echo $doctor_name; ?></strong></span>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="doctor_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="doctor_patients.php" class="nav-link"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_prescriptions.php" class="nav-link"><i class="fas fa-file-prescription"></i> Prescriptions</a>
                <a href="doctor_lab_orders.php" class="nav-link"><i class="fas fa-flask"></i> Lab Orders</a>
                <a href="doctor_leave.php" class="nav-link"><i class="fas fa-calendar-minus"></i> Apply Leave</a>
                <a href="doctor_settings.php" class="nav-link active"><i class="fas fa-cog"></i> Profile Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Profile Settings</h1>
                <p>Manage your account, professional profile, and security preferences.</p>
            </div>

            <div class="settings-grid">
                <div class="settings-nav">
                    <a href="#personal" class="settings-nav-link active"><i class="fas fa-user"></i> Personal Details</a>
                    <a href="#professional" class="settings-nav-link"><i class="fas fa-briefcase"></i> Professional Details</a>
                    <a href="#schedule" class="settings-nav-link"><i class="fas fa-clock"></i> Availability</a>
                    <a href="#account" class="settings-nav-link"><i class="fas fa-shield-alt"></i> Account Security</a>
                </div>

                <div class="settings-content">
                    <!-- Personal Details -->
                    <div id="personal" class="settings-section">
                        <form action="profile_handler.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            <h3 style="color: white; margin-bottom: 25px;">Personal Information</h3>
                            
                            <!-- Profile Photo Section -->
                            <div class="form-group" style="margin-bottom: 25px; display: flex; align-items: center; gap: 20px;">
                                <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; background: #eee; border: 2px solid rgba(255,255,255,0.2);">
                                    <?php 
                                        $photo_path = $reg_data['profile_photo'] ?? 'assets/images/default_doctor.png';
                                        if (empty($photo_path) || !file_exists($photo_path)) $photo_path = 'assets/images/default_doctor.png';
                                    ?>
                                    <img src="<?php echo $photo_path; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="flex: 1;">
                                    <label>Profile Photo</label>
                                    <input type="file" name="profile_photo" class="form-input" style="padding: 10px;" accept="image/*">
                                    <small style="color: #94a3b8;">Max size 2MB. JPG, PNG only.</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" class="form-input" value="<?php echo $_SESSION['username']; ?>" readonly style="opacity: 0.6;">
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-input" value="<?php echo $doctor_email; ?>" readonly style="opacity: 0.6;">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone" class="form-input" value="<?php echo $doctor_phone; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <input type="text" name="address" class="form-input" value="<?php echo $doctor_address; ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn-save">Update Personal Details</button>
                        </form>
                    </div>

                    <!-- Professional Details (Editable) -->
                    <div id="professional" class="settings-section">
                        <form action="profile_handler.php" method="POST">
                            <input type="hidden" name="action" value="update_professional">
                            <h3 style="color: white; margin-bottom: 25px;">Professional Profile</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Department</label>
                                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($department); ?>" readonly style="opacity: 0.6; cursor: not-allowed;">
                                </div>
                                <div class="form-group">
                                    <label>Designation</label>
                                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($designation); ?>" readonly style="opacity: 0.6; cursor: not-allowed;">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Specialization</label>
                                    <input type="text" name="specialization" class="form-input" value="<?php echo htmlspecialchars($specialization); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Qualification</label>
                                    <input type="text" name="qualification" class="form-input" value="<?php echo htmlspecialchars($qualification); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Experience (Years)</label>
                                    <input type="number" name="experience" class="form-input" value="<?php echo htmlspecialchars($experience); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Consultation Fee (₹)</label>
                                    <input type="number" name="consultation_fee" class="form-input" value="<?php echo htmlspecialchars($doctor['consultation_fee'] ?? '500'); ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 25px;">
                                <label>Professional Bio / About Me</label>
                                <textarea name="bio" class="form-input" rows="4" style="resize: vertical;"><?php echo htmlspecialchars($doctor['bio'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn-save">Save Professional Details</button>
                        </form>
                    </div>

                    <!-- Availability -->
                    <div id="schedule" class="settings-section">
                        <h3 style="color: white; margin-bottom: 25px;">Availability & Schedule</h3>
                        <div style="margin-bottom: 20px;">
                            <label class="form-label">Available Days</label>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <label style="background: rgba(16,185,129,0.15); color: #10b981; padding: 5px 12px; border-radius: 6px; font-size: 12px;">Mon</label>
                                <label style="background: rgba(16,185,129,0.15); color: #10b981; padding: 5px 12px; border-radius: 6px; font-size: 12px;">Tue</label>
                                <label style="background: rgba(16,185,129,0.15); color: #10b981; padding: 5px 12px; border-radius: 6px; font-size: 12px;">Wed</label>
                                <label style="background: rgba(16,185,129,0.15); color: #10b981; padding: 5px 12px; border-radius: 6px; font-size: 12px;">Thu</label>
                                <label style="background: rgba(16,185,129,0.15); color: #10b981; padding: 5px 12px; border-radius: 6px; font-size: 12px;">Fri</label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Consultation Start Time</label>
                                <input type="time" class="form-input" value="09:00">
                            </div>
                            <div class="form-group">
                                <label>Consultation End Time</label>
                                <input type="time" class="form-input" value="20:00">
                            </div>
                        </div>
                    </div>

                    <!-- Account Security (Change Password) -->
                    <div id="account" class="settings-section">
                        <h3 style="color: white; margin-bottom: 25px;">Account Security</h3>
                        <form action="profile_handler.php" method="POST">
                            <input type="hidden" name="action" value="update_password">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" class="form-input" placeholder="Enter current password" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-input" placeholder="Enter new password" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm new password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn-save" style="background: #3b82f6;">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
