<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email_display = $_SESSION['email'];

// Fetch detailed profile
$stmt = $conn->prepare("SELECT p.*, r.name, r.email, r.phone, r.address, r.profile_photo, u.username 
                        FROM users u 
                        JOIN registrations r ON u.registration_id = r.registration_id 
                        LEFT JOIN patient_profiles p ON u.user_id = p.user_id 
                        WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res->fetch_assoc();

// Handle profile update
$msg = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $allergies = $_POST['allergies'];
    $med_history = $_POST['medical_history'];

    // Update Registration Table
    $up1 = $conn->prepare("UPDATE registrations SET phone = ?, address = ? WHERE registration_id = (SELECT registration_id FROM users WHERE user_id = ?)");
    $up1->bind_param("ssi", $phone, $address, $user_id);
    $up1->execute();

    // Update/Insert Patient Profile Table
    // Check if exists
    $check = $conn->query("SELECT profile_id FROM patient_profiles WHERE user_id = $user_id");
    if($check->num_rows > 0) {
        $up2 = $conn->prepare("UPDATE patient_profiles SET date_of_birth = ?, gender = ?, blood_group = ?, allergies = ?, medical_history = ? WHERE user_id = ?");
        $up2->bind_param("sssssi", $dob, $gender, $blood_group, $allergies, $med_history, $user_id);
    } else {
        // Generate Code
        $p_code = "HC-P-" . date("Y") . "-" . rand(1000, 9999);
        $up2 = $conn->prepare("INSERT INTO patient_profiles (user_id, patient_code, date_of_birth, gender, blood_group, allergies, medical_history) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $up2->bind_param("issssss", $user_id, $p_code, $dob, $gender, $blood_group, $allergies, $med_history);
    }
    
    if ($up2->execute()) {
        $msg = "Profile updated successfully!";
        // Refresh data
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Failed to update profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }
        .profile-img-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            overflow: hidden;
            background: #fff;
        }
        .profile-img { width: 100%; height: 100%; object-fit: cover; }
        .profile-info h1 { margin: 0; font-size: 2rem; font-weight: 700; }
        .profile-info p { margin: 5px 0 0; opacity: 0.9; }
        
        .form-section {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 35px;
        }
        
        .section-title {
            color: #fff;
            font-size: 1.2rem;
            margin-top: 0;
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 15px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .form-group label {
            display: block;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 12px 15px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }
        .form-control:focus {
            border-color: #3b82f6;
            background: rgba(255,255,255,0.05);
        }
        .form-control[readonly] { opacity: 0.6; cursor: not-allowed; }
        
        .btn-update {
            background: #10b981;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            margin-top: 30px;
            transition: 0.3s;
        }
        .btn-update:hover { background: #059669; }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .alert-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" style="width: 280px; background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column;">
            <div class="logo-container" style="padding: 30px;">
                <h2 style="color: #fff; margin: 0;">HEALCARE</h2>
            </div>
            <nav style="flex: 1; padding: 0 15px;">
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical"></i> Medical Records</a>
                <a href="patient_lab_results.php" class="nav-link"><i class="fas fa-flask"></i> Lab Results</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="patient_profile.php" class="nav-link active" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-user"></i> My Profile</a>
                <a href="logout.php" class="nav-link" style="color: #ef4444; margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content" style="flex: 1; padding: 40px; background: #020617; overflow-y: auto;">
            
            <?php if($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="profile-img-container">
                    <img src="<?php echo !empty($profile['profile_photo']) ? $profile['profile_photo'] : 'assets/images/default_user.png'; ?>" class="profile-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($profile['name']); ?>&background=random'">
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($profile['name']); ?></h1>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['email']); ?> &nbsp;•&nbsp; <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($profile['patient_code'] ?? 'PENDING'); ?></p>
                </div>
            </div>

            <form method="POST" class="form-section">
                <h3 class="section-title">Personal Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($profile['name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo $profile['date_of_birth']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="Male" <?php if(($profile['gender']??'') == 'Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if(($profile['gender']??'') == 'Female') echo 'selected'; ?>>Female</option>
                            <option value="Other" <?php if(($profile['gender']??'') == 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">-- Select --</option>
                            <?php 
                            $bgs = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                            foreach($bgs as $bg) {
                                echo '<option value="'.$bg.'" '.(($profile['blood_group']??'')==$bg?'selected':'').'>'.$bg.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($profile['address']); ?>">
                    </div>
                </div>

                <h3 class="section-title" style="margin-top: 30px;">Medical Information</h3>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Known Allergies</label>
                    <textarea name="allergies" class="form-control" rows="2" placeholder="List any known allergies..."><?php echo htmlspecialchars($profile['allergies'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Past Medical History</label>
                    <textarea name="medical_history" class="form-control" rows="4" placeholder="Brief history of past conditions, surgeries, or chronic illnesses..."><?php echo htmlspecialchars($profile['medical_history'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn-update">Save Changes</button>
            </form>

        </main>
    </div></body>
</html>
