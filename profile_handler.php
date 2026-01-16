<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$action = $_POST['action'] ?? '';

if ($action == 'update_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $redirect = ($role == 'doctor') ? 'doctor_settings.php' : 'staff_settings.php';
        echo "<script>alert('New passwords do not match!'); window.location.href='$redirect?section=security';</script>";
        exit();
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if (password_verify($current_password, $user['password'])) {
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update->bind_param("si", $new_hashed, $user_id);
        
        if ($update->execute()) {
            $redirect = ($role == 'doctor') ? 'doctor_settings.php' : 'staff_settings.php';
            echo "<script>alert('Password updated successfully!'); window.location.href='$redirect?section=security';</script>";
        } else {
            echo "<script>alert('Error updating password.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Incorrect current password!'); window.history.back();</script>";
    }
    exit();

} elseif ($action == 'update_profile') {
    // Basic profile update (Phone, etc.)
    // Note: Critical fields like Email/Role are usually restricted or require admin approval
    // For now, we'll allow updating Phone and Address if they exist in the respective profile tables
    
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Determine table based on role
    if ($role == 'doctor') {
        // Update doctor specific table if exists, or just specific fields we allow
        // Assuming doctors table has phone/address or we update users table/registrations
        // For simplicity, let's assume we update the 'registrations' table linked to the user
        // But first we need the registration_id
        
        $u_res = $conn->query("SELECT registration_id FROM users WHERE user_id = $user_id");
        $u_row = $u_res->fetch_assoc();
        $reg_id = $u_row['registration_id'];
        
        if ($reg_id) {
            // Handle Photo Upload
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $target_dir = "uploads/photos/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed)) {
                    $new_filename = "doc_" . $user_id . "_" . time() . "." . $file_ext;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                        $p_stmt = $conn->prepare("UPDATE registrations SET profile_photo = ? WHERE registration_id = ?");
                        $p_stmt->bind_param("si", $target_file, $reg_id);
                        $p_stmt->execute();
                    }
                }
            }

            $stmt = $conn->prepare("UPDATE registrations SET phone = ?, address = ? WHERE registration_id = ?");
            $stmt->bind_param("ssi", $phone, $address, $reg_id);
            $stmt->execute();
        }
        
        echo "<script>alert('Personal Details updated successfully!'); window.location.href='doctor_settings.php';</script>";
        
    } elseif ($role == 'staff') {
        $u_res = $conn->query("SELECT registration_id FROM users WHERE user_id = $user_id");
        $u_row = $u_res->fetch_assoc();
        $reg_id = $u_row['registration_id'];
        
        if ($reg_id) {
            $stmt = $conn->prepare("UPDATE registrations SET phone = ?, address = ? WHERE registration_id = ?");
            $stmt->bind_param("ssi", $phone, $address, $reg_id);
            $stmt->execute();
        }
        echo "<script>alert('Profile updated successfully!'); window.location.href='staff_settings.php';</script>";
    }
    exit();

} elseif ($action == 'update_professional') {
    if ($role != 'doctor') {
        header("Location: login.php");
        exit();
    }

    $spec = $_POST['specialization'];
    $qual = $_POST['qualification'];
    $exp = (int)$_POST['experience'];
    $fee = (int)$_POST['consultation_fee'];
    $bio = $_POST['bio'];

    $stmt = $conn->prepare("UPDATE doctors SET specialization = ?, qualification = ?, experience = ?, consultation_fee = ?, bio = ? WHERE user_id = ?");
    $stmt->bind_param("ssissi", $spec, $qual, $exp, $fee, $bio, $user_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Professional Details updated successfully!'); window.location.href='doctor_settings.php';</script>";
    } else {
        echo "<script>alert('Error updating details: " . $conn->error . "'); window.location.href='doctor_settings.php';</script>";
    }
    exit();
}

header("Location: index.php");
?>
