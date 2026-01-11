<?php
include 'includes/db_connect.php';

echo "Syncing Registrations to Users and Profiles...\n";

$res = $conn->query("SELECT * FROM registrations WHERE status = 'Approved'");

while ($reg = $res->fetch_assoc()) {
    $email = $reg['email'];
    $reg_id = $reg['registration_id'];
    $role = $reg['user_type'];
    $username = explode('@', $email)[0];
    
    // Check if user exists
    $check_u = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check_u->num_rows == 0) {
        echo "Creating user for $email ($role)...\n";
        $pass = $reg['password'] ?: password_hash('Pass1234', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (registration_id, username, email, password, role, status) VALUES ($reg_id, '$username', '$email', '$pass', '$role', 'Active')");
        $user_id = $conn->insert_id;
    } else {
        $user = $check_u->fetch_assoc();
        $user_id = $user['user_id'];
    }
    
    // Create specific profiles
    if ($role == 'doctor') {
        $check_d = $conn->query("SELECT * FROM doctors WHERE user_id = $user_id");
        if ($check_d->num_rows == 0) {
            echo "Creating doctor profile for $email...\n";
            $spec = $reg['specialization'] ?: 'General Medicine';
            $qual = $reg['highest_qualification'] ?: 'MBBS';
            $exp = $reg['total_experience'] ?: '5 Years';
            $dept = $reg['dept_preference'] ?: 'General';
            $conn->query("INSERT INTO doctors (user_id, specialization, qualification, experience, department, designation) VALUES ($user_id, '$spec', '$qual', '$exp', '$dept', 'Senior Consultant')");
        }
    } elseif ($role == 'patient') {
        $check_p = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $user_id");
        if ($check_p->num_rows == 0) {
            echo "Creating patient profile for $email...\n";
            $p_code = 'HC-P-' . date('Y') . '-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            $name = $reg['name'];
            $conn->query("INSERT INTO patient_profiles (user_id, patient_code, name, status) VALUES ($user_id, '$p_code', '$name', 'Active')");
        }
    } elseif ($role == 'staff') {
        $stype = $reg['staff_type'];
        $table = "";
        if ($stype == 'Nurse') $table = "nurses";
        elseif ($stype == 'Lab Staff') $table = "lab_staff";
        elseif ($stype == 'Pharmacist') $table = "pharmacists";
        elseif ($stype == 'Canteen Staff') $table = "canteen_staff";
        elseif ($stype == 'Receptionist') $table = "receptionists";
        
        if ($table) {
            $check_s = $conn->query("SELECT * FROM $table WHERE user_id = $user_id");
            if ($check_s->num_rows == 0) {
                echo "Creating staff profile ($stype) for $email...\n";
                $conn->query("INSERT INTO $table (user_id, status) VALUES ($user_id, 'Active')");
            }
        }
    }
}

echo "Sync Complete.\n";
?>
