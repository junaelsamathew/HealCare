<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch Registration Info
$reg_query = $conn->query("SELECT r.* FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE u.user_id = $user_id");
$reg_data = $reg_query->fetch_assoc();

// Check if profile already exists
$profile_query = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $user_id");
$profile_exists = ($profile_query->num_rows > 0);
$profile = $profile_exists ? $profile_query->fetch_assoc() : null;

// Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $name = $reg_data['name']; // Forced from registration

    if ($profile_exists) {
        // Update
        $sql = "UPDATE patient_profiles SET gender='$gender', date_of_birth='$dob', blood_group='$blood_group', phone='$phone', address='$address' WHERE user_id=$user_id";
        if ($conn->query($sql)) {
            $message = "Profile updated successfully!";
            // Refresh profile data
            $profile_query = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $user_id");
            $profile = $profile_query->fetch_assoc();
        } else {
            $message = "Error: " . $conn->error;
        }
    } else {
        // INSERT (Fallback - though profile is now created during signup)
        $patient_code = $_SESSION['username']; // Use the code they logged in with
        $sql = "INSERT INTO patient_profiles (user_id, patient_code, name, gender, date_of_birth, blood_group, phone, address, status) 
                VALUES ($user_id, '$patient_code', '$name', '$gender', '$dob', '$blood_group', '$phone', '$address', 'Active')";
        if ($conn->query($sql)) {
            $message = "Profile updated successfully!";
            $profile_exists = true;
            $profile_query = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $user_id");
            $profile = $profile_query->fetch_assoc();
        } else {
            $message = "Error: " . $conn->error;
        }
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

    <!-- Styles -->
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        .profile-form-container {
            max-width: 900px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-top: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-gray);
            font-size: 13px;
            font-weight: 500;
        }
        .form-input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: white;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: rgba(255, 255, 255, 0.1);
        }
        .form-input[readonly] {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-gray);
            cursor: not-allowed;
        }
        .btn-save {
            background: var(--primary-blue);
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            transition: all 0.3s;
        }
        .btn-save:hover {
            background: var(--dark-blue);
            transform: translateY(-2px);
        }
        .alert-box {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            color: #10b981;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Top White Header -->
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-phone-alt"></i></div>
                <div class="info-details">
                    <span class="info-label">EMERGENCY</span>
                    <span class="info-value">(+254) 717 783 146</span>
                </div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-clock"></i></div>
                <div class="info-details">
                    <span class="info-label">WORK HOUR</span>
                    <span class="info-value">09:00 - 20:00 Everyday</span>
                </div>
            </div>
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-details">
                    <span class="info-label">LOCATION</span>
                    <span class="info-value">Kanjirapally, Kottayam</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Secondary Navy Header -->
    <header class="secondary-header">
        <div class="brand-section">
            <div class="brand-icon">+</div>
            <div class="brand-name">HealCare</div>
        </div>
        <div class="user-controls">
            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($username); ?></strong></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link">Dashboard</a>
                <a href="book_appointment.php" class="nav-link">Book Appointment</a>
                <a href="my_appointments.php" class="nav-link">My Appointments</a>
                <a href="medical_records.php" class="nav-link">Medical Records</a>
                <a href="prescriptions.php" class="nav-link">Prescriptions</a>
                <a href="settings.php" class="nav-link active">Settings</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>My Profile</h1>
                <p>Update your personal and medical information</p>
            </div>

            <?php if ($message): ?>
                <div class="alert-box"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="content-section profile-form-container">
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Hospital Patient Code</label>
                            <input type="text" class="form-input" value="<?php echo $profile['patient_code'] ?? 'Assigned after first save'; ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Full Name (Verified)</label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($reg_data['name']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="tel" name="phone" class="form-input" value="<?php echo $profile['phone'] ?? $reg_data['phone']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-input" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($profile['gender']??'') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($profile['gender']??'') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($profile['gender']??'') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" class="form-input" value="<?php echo $profile['date_of_birth'] ?? ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Blood Group</label>
                            <select name="blood_group" class="form-input" required>
                                <option value="">Select Group</option>
                                <?php $bgs = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; 
                                foreach($bgs as $bg) {
                                    $sel = ($profile['blood_group']??'') == $bg ? 'selected' : '';
                                    echo "<option value='$bg' $sel>$bg</option>";
                                } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Registration Date</label>
                            <input type="text" class="form-input" value="<?php echo $profile['registered_date'] ?? date('Y-m-d'); ?>" readonly>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label>Residential Address</label>
                            <textarea name="address" class="form-input" rows="4" placeholder="Enter your full address" required><?php echo $profile['address'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn-save">
                        <?php echo $profile_exists ? 'Save Changes' : 'Create Hospital Profile'; ?>
                    </button>
                </form>
            </div>
        </main>
    </div>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
