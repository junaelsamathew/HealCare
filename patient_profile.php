<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

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

// Determine Display Name for Header
$display_name = $profile['name'] ?? $_SESSION['full_name'] ?? $username;

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

    // Handle Profile Picture Upload
    $profile_photo_path = $profile['profile_photo']; // Default to existing
    
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = $_FILES['profile_photo']['type'];
        $filesize = $_FILES['profile_photo']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if ($filesize < 5000000) { // 5MB limit
                // Create uploads directory if not exists
                if (!file_exists('uploads/profiles')) {
                    mkdir('uploads/profiles', 0777, true);
                }
                
                $new_filename = "profile_" . $user_id . "_" . time() . "." . $ext;
                $upload_path = "uploads/profiles/" . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $profile_photo_path = $upload_path;
                } else {
                    $error = "Failed to upload image. ";
                }
            } else {
                $error = "File too large. Max 5MB. ";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF allowd. ";
        }
    }

    // Update Registration Table (Name, Phone, Address, Photo)
    // Note: We are strictly updating phone, address, and potentially photo. 
    // Usually name isn't editable here easily without more checks, but user didn't ask for name edit.
    $up1 = $conn->prepare("UPDATE registrations SET phone = ?, address = ?, profile_photo = ? WHERE registration_id = (SELECT registration_id FROM users WHERE user_id = ?)");
    $up1->bind_param("sssi", $phone, $address, $profile_photo_path, $user_id);
    $up1->execute();

    // Update/Insert Patient Profile Table (DOB, Gender, BloodGroup, History)
    $check = $conn->query("SELECT patient_id FROM patient_profiles WHERE user_id = $user_id");
    if($check->num_rows > 0) {
        $up2 = $conn->prepare("UPDATE patient_profiles SET date_of_birth = ?, gender = ?, blood_group = ?, allergies = ?, medical_history = ? WHERE user_id = ?");
        $up2->bind_param("sssssi", $dob, $gender, $blood_group, $allergies, $med_history, $user_id);
    } else {
        // Generate Code
        $p_code = "HC-P-" . date("Y") . "-" . rand(1000, 9999);
        $up2 = $conn->prepare("INSERT INTO patient_profiles (user_id, patient_code, date_of_birth, gender, blood_group, allergies, medical_history) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $up2->bind_param("issssss", $user_id, $p_code, $dob, $gender, $blood_group, $allergies, $med_history);
    }
    
    if ($up2->execute() && empty($error)) {
        $msg = "Profile updated successfully!";
        // Refresh data
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
    } else {
        $error .= "Failed to update profile details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HealCare</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    
    <style>
        /* Specific Profile Styles */
        .profile-header-card {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .profile-img-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .profile-img-display {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            object-fit: cover;
            background: #fff;
        }

        .profile-info h1 { margin: 0; font-size: 2rem; font-weight: 700; }
        .profile-info p { margin: 5px 0 0; opacity: 0.9; display: flex; align-items: center; gap: 15px; }

        .form-section {
            background: #1e293b;
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
            font-weight: 600;
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
            background: #0f172a;
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
        .form-control[readonly] { opacity: 0.7; cursor: not-allowed; }
        
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
            width: 100%; /* Full width on mobile, auto on desk */
            max-width: 250px;
            display: block;
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

        /* File Input Styling */
        .file-input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        .file-input-wrapper input[type="file"] {
            display: none;
        }
        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: 0.3s;
        }
        .file-input-label:hover {
            background: rgba(255,255,255,0.2);
        }
        .file-input-label i { margin-right: 8px; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .profile-header-card { flex-direction: column; text-align: center; padding: 30px; }
            .profile-info p { justify-content: center; flex-wrap: wrap; }
        }

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
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">EMERGENCY</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">(+91) 953 904 5609</span>
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

    <!-- Secondary Navy Header -->
    <header class="secondary-header">
        <div style="flex: 1;"></div>
        <div class="user-controls">
            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($display_name); ?></strong></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="patient_lab_results.php" class="nav-link"><i class="fas fa-flask"></i> Lab Reports</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="patient_feedback.php" class="nav-link"><i class="fas fa-comment-dots"></i> Patient Feedback</a>
                <a href="settings.php" class="nav-link active"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            
            <?php if($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="profile-header-card">
                <div class="profile-img-wrapper">
                    <img src="<?php echo !empty($profile['profile_photo']) ? $profile['profile_photo'] : 'assets/images/default_user.png'; ?>" class="profile-img-display" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($profile['name']); ?>&background=random'">
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($profile['name']); ?></h1>
                    <p>
                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['email']); ?></span>
                        <span><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($profile['patient_code'] ?? 'PENDING'); ?></span>
                    </p>
                </div>
            </div>

            <form method="POST" class="form-section" enctype="multipart/form-data">
                <h3 class="section-title">Personal Details</h3>
                
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Profile Picture</label>
                        <div class="file-input-wrapper">
                            <label for="profile_upload" class="file-input-label">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                            <span id="file-name-display" style="margin-left: 10px; color: #94a3b8; font-size: 13px;"></span>
                            <input type="file" id="profile_upload" name="profile_photo" accept="image/*" onchange="updateFileName(this)">
                        </div>
                    </div>

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
    </div>

    <script>
        function updateFileName(input) {
            const display = document.getElementById('file-name-display');
            if (input.files && input.files.length > 0) {
                display.textContent = input.files[0].name;
            } else {
                display.textContent = '';
            }
        }

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
</body>
</html>
