<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch Personal Details from Registrations
$stmt_reg = $conn->prepare("SELECT r.phone, r.address, r.email, r.staff_type, r.highest_qualification, r.total_experience, r.designation, r.name as fullname FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE u.user_id = ?");
$stmt_reg->bind_param("i", $user_id);
$stmt_reg->execute();
$res_reg = $stmt_reg->get_result();
$reg_data = $res_reg->fetch_assoc();

$staff_email = $reg_data['email'] ?? 'staff@healcare.com';
$staff_phone = $reg_data['phone'] ?? '';
$staff_address = $reg_data['address'] ?? '';
$staff_type = ucfirst(str_replace('_', ' ', $reg_data['staff_type'] ?? 'Staff'));
$staff_qual = $reg_data['highest_qualification'] ?? '';
$staff_exp = $reg_data['total_experience'] ?? '';
$staff_desig = $reg_data['designation'] ?? $staff_type;
$staff_name = $reg_data['fullname'] ?? $_SESSION['username'];

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
    
    <!-- Shared Styles from Doctor Dashboard -->
    <style>
        body { background-color: #020617; color: #fff; margin: 0; font-family: 'Poppins', sans-serif; }
        .settings-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
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
        .top-header { display:flex; justify-content:space-between; padding: 20px 40px; background: rgba(15, 23, 42, 0.8); border-bottom: 1px solid rgba(255,255,255,0.05); align-items: center; }
        .logo-main { color: #fff; font-weight: 800; text-decoration: none; font-size: 24px; }
        .back-btn { color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .back-btn:hover { color: #fff; }
    </style>
</head>
<body>

    <header class="top-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <span style="color:rgba(255,255,255,0.2)">|</span>
            <a href="#" class="logo-main">HEALCARE</a>
        </div>
        <div>
            <span style="color: #94a3b8; font-size: 14px;">Logged in as <strong><?php echo htmlspecialchars($staff_name); ?></strong></span>
        </div>
    </header>

    <div class="settings-grid">
        <aside class="settings-nav">
            <a href="#personal" class="settings-nav-link active"><i class="fas fa-user"></i> Personal Details</a>
            <a href="#professional" class="settings-nav-link"><i class="fas fa-briefcase"></i> Job Details</a>
            <a href="#account" class="settings-nav-link"><i class="fas fa-shield-alt"></i> Account Security</a>
        </aside>

        <main class="settings-content">
            <div style="margin-bottom: 30px;">
                <h1 style="margin:0; font-size: 28px;">Profile Settings</h1>
                <p style="color: #64748b; margin-top: 5px;">Manage your personal info and password.</p>
            </div>

            <!-- Personal Details -->
            <div id="personal" class="settings-section">
                <form action="profile_handler.php" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <h3 style="color: white; margin-bottom: 25px;">Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($staff_name); ?>" readonly style="opacity: 0.6;">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="form-input" value="<?php echo $staff_email; ?>" readonly style="opacity: 0.6;">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" class="form-input" value="<?php echo $staff_phone; ?>">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" class="form-input" value="<?php echo $staff_address; ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn-save">Update Profile</button>
                </form>
            </div>

            <!-- Professional Details (Read Only) -->
            <div id="professional" class="settings-section">
                <h3 style="color: white; margin-bottom: 25px;">Professional Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Staff Role</label>
                        <input type="text" class="form-input" value="<?php echo $staff_type; ?>" readonly style="opacity: 0.6;">
                    </div>
                    <div class="form-group">
                        <label>Designation</label>
                        <input type="text" class="form-input" value="<?php echo $staff_desig; ?>" readonly style="opacity: 0.6;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" class="form-input" value="<?php echo $staff_qual; ?>" readonly style="opacity: 0.6;">
                    </div>
                    <div class="form-group">
                        <label>Experience</label>
                        <input type="text" class="form-input" value="<?php echo $staff_exp; ?>" readonly style="opacity: 0.6;">
                    </div>
                </div>
                <button class="btn-save" style="background:rgba(255,255,255,0.1); cursor:not-allowed;">Contact Admin to Update Job Details</button>
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
        </main>
    </div>
</body>
</html>
