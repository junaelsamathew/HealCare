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
            $stmt = $conn->prepare("UPDATE registrations SET phone = ?, address = ? WHERE registration_id = ?");
            $stmt->bind_param("ssi", $phone, $address, $reg_id);
            $stmt->execute();
            
            // Also update doctors table if it exists and has these fields
            // $conn->query("UPDATE doctors SET contact_number = '$phone' WHERE user_id = $user_id");
        }
        
        echo "<script>alert('Profile updated successfully!'); window.location.href='doctor_settings.php';</script>";
        
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
}

header("Location: index.php");
?>
