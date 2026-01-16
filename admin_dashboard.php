<?php
session_start();
include 'includes/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

// Simple Auth Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin') {
        // Allow
    } else {
        header("Location: login.php");
        exit();
    }
}

// Get current section from URL parameter
$section = $_GET['section'] ?? 'dashboard';

// Handle various POST actions
$success_msg = '';
$error_msg = '';

// Handle Approval/Rejection
// Handle all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $reg_id = $_POST['reg_id'] ?? null;
    
    if ($action == 'approve') {
        $conn->begin_transaction();
        try {
            $res = $conn->query("SELECT * FROM registrations WHERE registration_id = $reg_id");
            $reg = $res->fetch_assoc();
            
            $email = $reg['email'];
            $name = $reg['name']; // Extract Name
            $role = $reg['user_type'];
            
            $year = date("Y");
            $temp_pass = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10);
            
            // Generate base username from name
            $clean_name = strtolower(trim($name));
            $name_parts = explode(' ', $clean_name);
            if (count($name_parts) > 1) {
                $base_uname = $name_parts[0] . '.' . end($name_parts);
            } else {
                $base_uname = $name_parts[0];
            }
            $base_uname = preg_replace('/[^a-z0-9.]/', '', $base_uname);
            
            // Loop until unique username found
            $username = $base_uname . "@healcare.com";
            $counter = 1;
            while ($conn->query("SELECT user_id FROM users WHERE username = '$username'")->num_rows > 0) {
                $username = $base_uname . $counter . "@healcare.com";
                $counter++;
            }

            $password = password_hash($temp_pass, PASSWORD_DEFAULT); 

            $admin_msg = "Congratulations! Your application (ID: {$reg['app_id']}) has been approved. Your Hospital Username is: $username";
            $conn->query("UPDATE registrations SET status = 'Approved', admin_message = '$admin_msg' WHERE registration_id = $reg_id");

            $role_display = ucfirst($role);
            if ($role == 'staff' && !empty($reg['staff_type'])) {
                $role_display .= ' (' . ucfirst(str_replace('_', ' ', $reg['staff_type'])) . ')';
            }

            $perms = ($role == 'doctor') ? 'View Medical Records, Write prescriptions' : 'General Access';
            $conn->query("INSERT INTO users (registration_id, username, email, password, role, permissions, force_password_change, status) 
                         VALUES ($reg_id, '$username', '$email', '$password', '$role', '$perms', 1, 'Active')");
            $new_user_id = $conn->insert_id;

            if ($role == 'doctor') {
                $spec = mysqli_real_escape_string($conn, $reg['specialization']);
                $qual = mysqli_real_escape_string($conn, $reg['highest_qualification']);
                $exp = (int)$reg['total_experience'];
                $dept = mysqli_real_escape_string($conn, $reg['dept_preference']);
                $doj = mysqli_real_escape_string($conn, $reg['date_of_joining']);
                $desig = mysqli_real_escape_string($conn, $reg['designation']);

                $conn->query("INSERT INTO doctors (user_id, specialization, qualification, experience, department, date_of_join, designation) 
                             VALUES ($new_user_id, '$spec', '$qual', $exp, '$dept', '$doj', '$desig')");
            } elseif ($role == 'staff') {
                $stype = $reg['staff_type'];
                $dept = mysqli_real_escape_string($conn, $reg['dept_preference']);
                $shift = mysqli_real_escape_string($conn, $reg['shift_preference']);
                $qual = mysqli_real_escape_string($conn, $reg['qualification_details']);
                $rel_exp = (int)$reg['relevant_experience'];
                $doj = mysqli_real_escape_string($conn, $reg['date_of_joining']);
                $desig = mysqli_real_escape_string($conn, $reg['designation'] ?? 'Staff');
                
                if ($stype == 'nurse') {
                    $conn->query("INSERT INTO nurses (user_id, department, shift, qualification, experience, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$dept', '$shift', '$qual', $rel_exp, '$doj', '$desig', 'Active')");
                } elseif ($stype == 'lab_staff') {
                    $ltype = mysqli_real_escape_string($conn, $reg['specialization']);
                    $conn->query("INSERT INTO lab_staff (user_id, lab_type, shift, qualification, experience, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$ltype', '$shift', '$qual', $rel_exp, '$doj', '$desig', 'Active')");
                } elseif ($stype == 'pharmacist') {
                    $conn->query("INSERT INTO pharmacists (user_id, qualification, experience, shift, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$qual', $rel_exp, '$shift', '$doj', '$desig', 'Active')");
                } elseif ($stype == 'canteen_staff') {
                    $crole = mysqli_real_escape_string($conn, $reg['canteen_job_role']);
                    $conn->query("INSERT INTO canteen_staff (user_id, role, shift, date_of_join, status) 
                                 VALUES ($new_user_id, '$crole', '$shift', '$doj', 'Active')");
                } elseif ($stype == 'receptionist') {
                    $langs = mysqli_real_escape_string($conn, $reg['languages_known']);
                    $conn->query("INSERT INTO receptionists (user_id, desk_no, shift, experience, qualification, date_of_join, language_known, status) 
                                 VALUES ($new_user_id, 'Desk-1', '$shift', $rel_exp, '$qual', '$doj', '$langs', 'Active')");
                }
            }

            // --- SEND CREDENTIALS VIA EMAIL ---
            $mail = new PHPMailer(true);
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
            $mail->Password   = 'yiuwcrykatkfzdwv'; // App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
            
            $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare HR');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to HealCare - Your Official Credentials';
            
            $salutation = ($role == 'doctor') ? 'Dr. ' : '';
            $mail->Body = '
            <div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; padding: 30px; border: 1px solid #e1e8ed; border-radius: 15px; color: #334155; line-height: 1.6;">
                <h2 style="color: #2b50c0; text-align: center; border-bottom: 2px solid #3b82f6; padding-bottom: 15px;">Welcome to HealCare!</h2>
                <p>Dear <strong>' . $salutation . htmlspecialchars($name) . '</strong>,</p>
                <p>We are delighted to inform you that your application for the role of <strong>' . $role_display . '</strong> has been <strong>approved</strong> by the hospital administration.</p>
                <p>Your official hospital account has been created. Please use the following official credentials to access the portal:</p>
                
                <div style="background: #f8fafc; padding: 25px; border-radius: 12px; margin: 25px 0; border: 1px solid #3b82f6; border-left: 6px solid #3b82f6;">
                    <p style="margin: 0 0 10px 0; font-size: 15px;"><strong>Hospital Username:</strong> <span style="font-family: \'Courier New\', Courier, monospace; font-size: 1.1em; color: #1e40af; background: #e0f2fe; padding: 2px 8px; border-radius: 4px;">' . $username . '</span></p>
                    <p style="margin: 0; font-size: 15px;"><strong>Temporary Password:</strong> <span style="font-family: \'Courier New\', Courier, monospace; font-size: 1.1em; color: #1e40af; background: #e0f2fe; padding: 2px 8px; border-radius: 4px;">' . $temp_pass . '</span></p>
                </div>
                
                <p style="text-align: center; margin: 30px 0;">
                    <a href="http://localhost/HealCare/login.php" style="background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.5);">Login to Dashboard</a>
                </p>
                
                <p style="font-size: 0.9em; color: #64748b;"><strong>Note:</strong> For security reasons, you will be required to change your password upon your first login.</p>
                
                <hr style="border:0; border-top:1px solid #f1f5f9; margin: 30px 0;">
                <p style="font-size: 0.8em; color: #94a3b8; text-align: center;">This is an automated message from HealCare HR Department. Please do not reply to this email.</p>
            </div>';
            
            $mail->send();

            $conn->commit();
            $success_msg = "Application Approved! Email with credentials sent to <strong>$email</strong>. ID: <strong>$username</strong>";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error: " . $e->getMessage();
            if (isset($mail) && $mail->ErrorInfo) {
                $error_msg .= " (Mail Error: " . $mail->ErrorInfo . ")";
            }
        }
    } elseif ($action == 'reject') {
        $admin_msg = "We regret to inform you that your application has been rejected after review.";
        $conn->query("UPDATE registrations SET status = 'Rejected', admin_message = '$admin_msg' WHERE registration_id = $reg_id");
        
        // --- SEND REJECTION EMAIL ---
        try {
            $res = $conn->query("SELECT email, name FROM registrations WHERE registration_id = $reg_id");
            if ($res && $row = $res->fetch_assoc()) {
                $email = $row['email'];
                $name = $row['name'];
                
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
                $mail->Password   = 'yiuwcrykatkfzdwv';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
                
                $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare HR');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Update on your HealCare Application';
                $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; padding: 30px; border: 1px solid #e1e8ed; border-radius: 12px; color: #334155;">
                    <h2 style="color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px;">Application Status Update</h2>
                    <p>Dear ' . htmlspecialchars($name) . ',</p>
                    <p>We have reviewed your application for the position at HealCare Hospital.</p>
                    <p>After careful consideration, we regret to inform you that we are <strong>unable to proceed with your application</strong> at this time.</p>
                    <p><em>Reason: ' . $admin_msg . '</em></p>
                    <p>We appreciate your interest in joining our team and wish you the best in your future endeavors.</p>
                    <hr style="border:0; border-top:1px solid #f1f5f9; margin: 30px 0;">
                    <p style="font-size: 0.8em; color: #94a3b8; text-align: center;">HealCare Hospital HR Department</p>
                </div>';
                
                $mail->send();
                $success_msg = "Application Rejected. Notification email sent to $email.";
            } else {
                 $success_msg = "Application Rejected, but could not retrieve email to send notification.";
            }
        } catch (Exception $e) {
             $success_msg = "Application Rejected. Email send failed: " . $e->getMessage();
        }
    } elseif ($action == 'add_user') {
        $name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $perms = isset($_POST['permissions']) ? implode(', ', $_POST['permissions']) : 'General Access';

        $conn->begin_transaction();
        try {
            // Enforce hospital domain for manual entry
            if (strpos($username, '@healcare.com') === false) {
                $username .= '@healcare.com';
            }

            // Check if username/email exists
            $check = $conn->query("SELECT user_id FROM users WHERE username = '$username' OR email = '$email'");
            if ($check->num_rows > 0) {
                throw new Exception("Username or Email already exists!");
            }

            // Insert into Registrations first to have a name/profile link
            $stmt_reg = $conn->prepare("INSERT INTO registrations (name, email, user_type, status, password) VALUES (?, ?, ?, 'Approved', ?)");
            $stmt_reg->bind_param("ssss", $name, $email, $role, $password);
            $stmt_reg->execute();
            $registration_id = $conn->insert_id;

            // Insert into Users
            $stmt = $conn->prepare("INSERT INTO users (registration_id, username, email, password, role, permissions, status, force_password_change) VALUES (?, ?, ?, ?, ?, ?, 'Active', 1)");
            $stmt->bind_param("isssss", $registration_id, $username, $email, $password, $role, $perms);
            $stmt->execute();
            $new_user_id = $conn->insert_id;

            if ($role == 'doctor') {
                $spec = mysqli_real_escape_string($conn, $_POST['specialization']);
                $qual = mysqli_real_escape_string($conn, $_POST['qualification']);
                $exp = (int)$_POST['experience'];
                $dept = mysqli_real_escape_string($conn, $_POST['department']);
                $doj = mysqli_real_escape_string($conn, $_POST['date_of_join']);
                $desig = mysqli_real_escape_string($conn, $_POST['designation']);

                $conn->query("INSERT INTO doctors (user_id, specialization, qualification, experience, department, date_of_join, designation) 
                             VALUES ($new_user_id, '$spec', '$qual', $exp, '$dept', '$doj', '$desig')");
            } elseif ($role == 'staff') {
                $stype = $_POST['staff_type'];
                $qual = mysqli_real_escape_string($conn, $_POST['staff_qualification']);
                $exp = (int)$_POST['staff_experience'];
                $doj = mysqli_real_escape_string($conn, $_POST['staff_date_of_join']);
                $shift = mysqli_real_escape_string($conn, $_POST['staff_shift']);
                
                if ($stype == 'lab_staff') {
                    $ltype = mysqli_real_escape_string($conn, $_POST['staff_specialization']);
                    $conn->query("INSERT INTO lab_staff (user_id, lab_type, shift, qualification, experience, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$ltype', '$shift', '$qual', $exp, '$doj', 'Staff', 'Active')");
                } elseif ($stype == 'nurse') {
                    $dept = mysqli_real_escape_string($conn, $_POST['staff_department'] ?? 'General');
                    $conn->query("INSERT INTO nurses (user_id, department, shift, qualification, experience, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$dept', '$shift', '$qual', $exp, '$doj', 'Staff Nurse', 'Active')");
                } elseif ($stype == 'pharmacist') {
                    $conn->query("INSERT INTO pharmacists (user_id, qualification, experience, shift, date_of_join, designation, status) 
                                 VALUES ($new_user_id, '$qual', $exp, '$shift', '$doj', 'Pharmacist', 'Active')");
                } elseif ($stype == 'receptionist') {
                    $conn->query("INSERT INTO receptionists (user_id, desk_no, shift, experience, qualification, date_of_join, language_known, status) 
                                 VALUES ($new_user_id, 'Manual', '$shift', $exp, '$qual', '$doj', 'English, Hindi', 'Active')");
                } elseif ($stype == 'canteen_staff') {
                    $crole = mysqli_real_escape_string($conn, $_POST['staff_role'] ?? 'Worker');
                    $conn->query("INSERT INTO canteen_staff (user_id, role, shift, date_of_join, status) 
                                 VALUES ($new_user_id, '$crole', '$shift', '$doj', 'Active')");
                }
            }

            // --- SEND CREDENTIALS VIA EMAIL ---
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
            $mail->Password   = 'yiuwcrykatkfzdwv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
            
            $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare HR');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Official Account Created - HealCare';
            
            $pass_raw = $_POST['password']; // Get the unhashed password from POST
            $salutation = ($role == 'doctor') ? 'Dr. ' : '';
            $mail->Body = '
            <div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; padding: 30px; border: 1px solid #e1e8ed; border-radius: 15px; color: #334155; line-height: 1.6;">
                <h2 style="color: #2b50c0; text-align: center; border-bottom: 2px solid #3b82f6; padding-bottom: 15px;">Official Account Created</h2>
                <p>Welcome to the HealCare family, <strong>' . $salutation . htmlspecialchars($name) . '</strong>!</p>
                <p>An official account has been manually created for you by the administrator. Please find your official credentials below:</p>
                
                <div style="background: #f8fafc; padding: 25px; border-radius: 12px; margin: 25px 0; border: 1px solid #3b82f6; border-left: 6px solid #3b82f6;">
                    <p style="margin: 0 0 10px 0; font-size: 15px;"><strong>Hospital Username:</strong> <span style="font-family: \'Courier New\', Courier, monospace; font-size: 1.1em; color: #1e40af; background: #e0f2fe; padding: 2px 8px; border-radius: 4px;">' . $username . '</span></p>
                    <p style="margin: 0; font-size: 15px;"><strong>Temporary Password:</strong> <span style="font-family: \'Courier New\', Courier, monospace; font-size: 1.1em; color: #1e40af; background: #e0f2fe; padding: 2px 8px; border-radius: 4px;">' . $pass_raw . '</span></p>
                </div>
                
                <p style="text-align: center; margin: 30px 0;">
                    <a href="http://localhost/HealCare/login.php" style="background: #1e293b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Login Portal</a>
                </p>
                
                <p style="font-size: 0.9em; color: #64748b;"><strong>Note:</strong> Since your account was created manually, please change your password immediately after logging in for the first time.</p>
                
                <hr style="border:0; border-top:1px solid #f1f5f9; margin: 30px 0;">
                <p style="font-size: 0.8em; color: #94a3b8; text-align: center;">HealCare Hospital Management System</p>
            </div>';
            
            $mail->send();

            $conn->commit();
            $success_msg = "User account created successfully! Credentials sent to <strong>$email</strong>. ID: <strong>$username</strong>";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error: " . $e->getMessage();
        }
    } elseif ($action == 'delete_user') {
        $uid = (int)$_POST['user_id'];
        $conn->begin_transaction();
        try {
            // Get user info to cleanup related tables
            $u_res = $conn->query("SELECT role, registration_id FROM users WHERE user_id = $uid");
            if ($u_res && $u_data = $u_res->fetch_assoc()) {
                $role = $u_data['role'];
                $reg_id = $u_data['registration_id'];
                
                // Cleanup specific tables
                if ($role == 'doctor') {
                    $conn->query("DELETE FROM doctors WHERE user_id = $uid");
                    $conn->query("DELETE FROM doctor_schedules WHERE doctor_id = $uid");
                } elseif ($role == 'staff') {
                    $conn->query("DELETE FROM nurses WHERE user_id = $uid");
                    $conn->query("DELETE FROM lab_staff WHERE user_id = $uid");
                    $conn->query("DELETE FROM pharmacists WHERE user_id = $uid");
                    $conn->query("DELETE FROM canteen_staff WHERE user_id = $uid");
                    $conn->query("DELETE FROM receptionists WHERE user_id = $uid");
                }
                
                // Delete user
                $conn->query("DELETE FROM users WHERE user_id = $uid");
                
                // Delete registration if not admin (admins might not have registrations)
                if ($role != 'admin' && $reg_id) {
                    $conn->query("DELETE FROM registrations WHERE registration_id = $reg_id");
                }
                
                $conn->commit();
                $success_msg = "User and all related records deleted successfully.";
            } else {
                throw new Exception("User not found.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Error deleting user: " . $e->getMessage();
        }
    } elseif ($action == 'edit_user') {
        $uid = (int)$_POST['user_id'];
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $perms = isset($_POST['permissions']) ? implode(', ', $_POST['permissions']) : 'General Access';
        $perms = mysqli_real_escape_string($conn, $perms);
        
        $sql = "UPDATE users SET username='$username', email='$email', role='$role', status='$status', permissions='$perms' WHERE user_id = $uid";
        if ($conn->query($sql)) {
            $success_msg = "User record updated successfully!";
        } else {
            $error_msg = "Error updating user: " . $conn->error;
        }
    } elseif ($action == 'add_ambulance') {
        $driver = mysqli_real_escape_string($conn, $_POST['driver_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
        $v_no = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
        $v_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        
        $sql = "INSERT INTO ambulance_contacts (driver_name, phone_number, vehicle_number, vehicle_type, location, availability) 
                VALUES ('$driver', '$phone', '$v_no', '$v_type', '$location', 'Available')";
        if ($conn->query($sql)) {
            $success_msg = "Ambulance contact added successfully!";
        } else {
            $error_msg = "Error: " . $conn->error;
        }
    } elseif ($action == 'delete_ambulance') {
        $c_id = (int)$_POST['contact_id'];
        if ($conn->query("DELETE FROM ambulance_contacts WHERE contact_id = $c_id")) {
            $success_msg = "Contact deleted successfully.";
        } else {
            $error_msg = "Error deleting contact.";
        }
    } elseif ($action == 'update_doctor_schedule') {
        $doc_id = (int)$_POST['doctor_id'];
        $day = mysqli_real_escape_string($conn, $_POST['day_of_week']);
        $start = mysqli_real_escape_string($conn, $_POST['start_time']);
        $end = mysqli_real_escape_string($conn, $_POST['end_time']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        $sql = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, status) 
                VALUES ($doc_id, '$day', '$start', '$end', '$status')
                ON DUPLICATE KEY UPDATE start_time = '$start', end_time = '$end', status = '$status'";
        if ($conn->query($sql)) {
            $success_msg = "Doctor schedule updated successfully!";
        } else {
            $error_msg = "Error updating schedule: " . $conn->error;
        }
    } elseif ($action == 'update_doctor_availability') {
        $doc_id = (int)$_POST['doctor_id'];
        $availability = mysqli_real_escape_string($conn, $_POST['availability_status']);
        if ($conn->query("UPDATE doctors SET availability_status = '$availability' WHERE user_id = $doc_id")) {
            $success_msg = "Doctor availability updated!";
        } else {
            $error_msg = "Error updating availability.";
        }
    } elseif ($action == 'discard_stock') {
        $stock_id = (int)$_POST['stock_id'];
        if ($conn->query("DELETE FROM pharmacy_stock WHERE stock_id = $stock_id")) {
             $success_msg = "Medicine batch discarded and removed from inventory.";
        } else {
             $error_msg = "Error discarding stock: " . $conn->error;
        }
    } elseif ($action == 'update_doctor_dept') {
        $doc_id = (int)$_POST['doctor_id'];
        $dept = mysqli_real_escape_string($conn, $_POST['department']);
        $spec = mysqli_real_escape_string($conn, $_POST['specialization']);
        if ($conn->query("UPDATE doctors SET department = '$dept', specialization = '$spec' WHERE user_id = $doc_id")) {
            $success_msg = "Doctor department/specialization updated!";
        } else {
            $error_msg = "Error updating department.";
        }
    } elseif ($action == 'save_menu_item') {
        $name = mysqli_real_escape_string($conn, $_POST['food_name']);
        $cat = mysqli_real_escape_string($conn, $_POST['meal_category']);
        $diet = mysqli_real_escape_string($conn, $_POST['diet_type']);
        $price = (float)$_POST['price'];
        $desc = mysqli_real_escape_string($conn, $_POST['description']);
        $avail = mysqli_real_escape_string($conn, $_POST['availability']);
        $mid = isset($_POST['menu_id']) && !empty($_POST['menu_id']) ? (int)$_POST['menu_id'] : null;

        $image_url = '';
        if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] == 0) {
            $target_dir = "assets/food/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES["food_image"]["name"], PATHINFO_EXTENSION));
            $new_filename = strtolower(preg_replace('/[^a-z0-9]/', '_', $name)) . '_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["food_image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            }
        }

        if ($mid) {
            $img_clause = $image_url ? ", image_url='$image_url'" : "";
            $sql = "UPDATE canteen_menu SET item_name='$name', item_category='$cat', diet_type='$diet', price=$price, description='$desc', availability='$avail' $img_clause WHERE menu_id=$mid";
        } else {
            $sql = "INSERT INTO canteen_menu (item_name, item_category, diet_type, price, description, availability, image_url) VALUES ('$name', '$cat', '$diet', $price, '$desc', '$avail', '$image_url')";
        }
        
        if ($conn->query($sql)) {
            $success_msg = "Menu item saved successfully!";
            header("Location: admin_dashboard.php?section=canteen-menu");
            exit();
        } else {
            $error_msg = "Error saving menu item: " . $conn->error;
        }
    } elseif ($action == 'delete_menu_item') {
        $mid = (int)$_POST['menu_id'];
        if ($conn->query("DELETE FROM canteen_menu WHERE menu_id = $mid")) {
            $success_msg = "Menu item deleted!";
            header("Location: admin_dashboard.php?section=canteen-menu");
            exit();
        } else {
            $error_msg = "Error deleting item.";
        }
    } elseif ($action == 'update_order_status') {
        $oid = (int)$_POST['order_id'];
        $status = mysqli_real_escape_string($conn, $_POST['new_status']);
        if ($conn->query("UPDATE canteen_orders SET order_status = '$status' WHERE order_id = $oid")) {
            $success_msg = "Order #$oid status updated!";
        } else {
            $error_msg = "Error updating order status.";
        }
    } elseif ($action == 'save_package') {
        $name = mysqli_real_escape_string($conn, $_POST['package_name']);
        $desc = mysqli_real_escape_string($conn, $_POST['description']);
        $tests = mysqli_real_escape_string($conn, $_POST['included_tests']);
        $actual = (float)$_POST['actual_price'];
        $discount_p = (float)$_POST['discount_price'];
        $percent = (int)$_POST['discount_percent'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $pid = isset($_POST['package_id']) ? (int)$_POST['package_id'] : null;

        if ($pid) {
            $sql = "UPDATE health_packages SET package_name='$name', package_description='$desc', included_tests='$tests', original_price=$actual, discounted_price=$discount_p, discount_percentage=$percent, status='$status' WHERE package_id=$pid";
        } else {
            $sql = "INSERT INTO health_packages (package_name, package_description, included_tests, original_price, discounted_price, discount_percentage, status) VALUES ('$name', '$desc', '$tests', $actual, $discount_p, $percent, '$status')";
        }
        if ($conn->query($sql)) {
            $success_msg = "Health package saved successfully!";
        } else {
            $error_msg = "Error saving health package: " . $conn->error;
        }
    } elseif ($action == 'delete_package') {
        $pid = (int)$_POST['package_id'];
        if ($conn->query("DELETE FROM health_packages WHERE package_id = $pid")) {
            $success_msg = "Health package deleted!";
        } else {
            $error_msg = "Error deleting package.";
        }
    } elseif ($action == 'toggle_stock') {
        $mid = (int)$_POST['menu_id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        if ($conn->query("UPDATE canteen_menu SET availability = '$status' WHERE menu_id = $mid")) {
            // Redirect to refresh the page and show updated status
            header("Location: admin_dashboard.php?section=canteen-menu");
            exit();
        } else {
            $error_msg = "Error updating stock status.";
        }
    } elseif ($action == 'approve_leave') {
        $lid = (int)$_POST['leave_id'];
        if ($conn->query("UPDATE doctor_leaves SET status = 'Approved' WHERE leave_id = $lid")) {
            $success_msg = "Leave request approved!";
        } else {
            $error_msg = "Error approving leave.";
        }
    } elseif ($action == 'reject_leave') {
        $lid = (int)$_POST['leave_id'];
        if ($conn->query("UPDATE doctor_leaves SET status = 'Rejected' WHERE leave_id = $lid")) {
            $success_msg = "Leave request rejected.";
        } else {
            $error_msg = "Error rejecting leave.";
        }
    }
}

// Fetch statistics with error handling
$today = date('Y-m-d');

// Total users
try {
    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
} catch (Exception $e) {
    $total_users = 0;
}

// Pending bills (table may not exist yet)
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM billing WHERE payment_status = 'Pending'");
    $pending_bills = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $pending_bills = 0;
}

// Today's patients
try {
    $result = $conn->query("SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE DATE(appointment_date) = '$today'");
    $todays_patients = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $todays_patients = 0;
}

// Chart Data: Consultation Traffic (Last 7 Days)
$chart_labels = [];
$chart_values = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_date = date('M d', strtotime("-$i days"));
    $chart_labels[] = $display_date;
    
    $traffic = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = '$date'")->fetch_assoc()['count'];
    $chart_values[] = (int)$traffic;
}

// Pharmacy stock alerts (Low Stock & Expiry)
$low_stock_list = [];
$expiry_list = [];
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'pharmacy_stock'");
    if($check_table && $check_table->num_rows > 0) {
        // Low Stock
        $result = $conn->query("SELECT COUNT(*) as count FROM pharmacy_stock WHERE quantity < 20");
        $pharmacy_alerts = $result ? $result->fetch_assoc()['count'] : 0;
        
        if ($pharmacy_alerts > 0) {
            $list_res = $conn->query("SELECT medicine_name, quantity FROM pharmacy_stock WHERE quantity < 20 LIMIT 5");
            while($row = $list_res->fetch_assoc()) {
                $low_stock_list[] = $row;
            }
        }

        // Expiry Alerts (Next 3 Months)
        $exp_result = $conn->query("SELECT COUNT(*) as count FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)");
        $expiry_alerts = $exp_result ? $exp_result->fetch_assoc()['count'] : 0;

        if ($expiry_alerts > 0) {
            // Get most urgent first
            $list_exp = $conn->query("SELECT medicine_name, expiry_date, DATEDIFF(expiry_date, CURDATE()) as days_left FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) ORDER BY expiry_date ASC LIMIT 5");
            while($row = $list_exp->fetch_assoc()) {
                $expiry_list[] = $row;
            }
        }
    } else {
        $pharmacy_alerts = 0;
        $expiry_alerts = 0;
    }
} catch (Exception $e) {
    $pharmacy_alerts = 0;
    $expiry_alerts = 0;
}

// Fetch data based on section
$pending_requests = $conn->query("SELECT * FROM registrations WHERE status = 'Pending' ORDER BY registered_date DESC");
$all_users = $conn->query("SELECT u.*, r.app_id FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #3b82f6;
            --dark-blue: #1e293b;
            --darker-blue: #0f172a;
            --darkest-blue: #020617;
            --accent-green: #10b981;
            --accent-orange: #f59e0b;
            --accent-red: #ef4444;
            --text-white: #f8fafc;
            --text-gray: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--darkest-blue);
            color: var(--text-white);
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            background: var(--darker-blue);
            border-right: 1px solid var(--border-color);
            padding: 30px 0;
            position: fixed;
            overflow-y: auto;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-blue);
            padding: 0 30px;
            margin-bottom: 40px;
            display: block;
            text-decoration: none;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-section-title {
            padding: 0 30px;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 30px;
            color: var(--text-gray);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            gap: 12px;
        }

        .nav-link:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-blue);
        }

        .nav-link.active {
            background: rgba(59, 130, 246, 0.15);
            color: var(--primary-blue);
            border-left: 3px solid var(--primary-blue);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Notifications */
        .notification-btn {
            position: relative;
            cursor: pointer;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-gray);
            transition: 0.3s;
            border: 1px solid var(--border-color);
        }

        .notification-btn:hover {
            color: var(--primary-blue);
            background: rgba(59, 130, 246, 0.1);
            border-color: var(--primary-blue);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent-red);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 10px;
            border: 2px solid var(--darkest-blue);
        }

        .notification-dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            width: 320px;
            background: var(--dark-blue);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            display: none;
            z-index: 1000;
            overflow: hidden;
        }

        .notification-header {
            padding: 15px 20px;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            transition: 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .notification-item:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .notification-item i {
            margin-right: 12px;
            color: var(--primary-blue);
        }

        .notification-item p {
            font-size: 13px;
            color: var(--text-white);
            margin-bottom: 3px;
        }

        .notification-item span {
            font-size: 11px;
            color: var(--text-gray);
        }

        /* Search Filter */
        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            background: rgba(255,255,255,0.03);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .search-input-group {
            position: relative;
            flex: 1;
        }

        .search-input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
        }

        .search-input-group input {
            width: 100%;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 12px 12px 45px;
            color: white;
            outline: none;
        }

        .search-input-group input:focus {
            border-color: var(--primary-blue);
        }

        /* Charts */
        .chart-container {
            background: var(--dark-blue);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-top: 30px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 40px 50px;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-title p {
            color: var(--text-gray);
            font-size: 14px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--dark-blue);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-card-title {
            font-size: 13px;
            color: var(--text-gray);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-card-value {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-card-subtitle {
            font-size: 12px;
            color: var(--text-gray);
        }

        /* Content Sections */
        .content-section {
            background: var(--dark-blue);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-success {
            background: var(--accent-green);
            color: white;
        }

        .btn-danger {
            background: var(--accent-red);
            color: white;
        }

        .btn-warning {
            background: var(--accent-orange);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            color: var(--text-gray);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-completed { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-unpaid { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-gray);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            background: var(--darker-blue);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-white);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        /* Placeholder Section */
        .placeholder-section {
            text-align: center;
            padding: 60px 20px;
        }

        .placeholder-section i {
            font-size: 64px;
            color: var(--text-gray);
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .placeholder-section h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .placeholder-section p {
            color: var(--text-gray);
            font-size: 14px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--dark-blue);
            margin: 10% auto;
            padding: 30px;
            border: 1px solid var(--border-color);
            width: 500px;
            border-radius: 20px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            color: var(--text-gray);
            font-size: 24px;
            cursor: pointer;
        }

        /* Admin specific fixes for new header */
        .sidebar { top: 72px !important; height: calc(100vh - 72px) !important; }
        .main-content { margin-top: 72px !important; }
    </style>
</head>
<body>
    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-sizing: border-box;">
        <h1 style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">+ HEALCARE</h1>
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">EMERGENCY</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">(+254) 717 783 146</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-clock"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WORK HOUR</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">09:00 - 20:00 Everyday</span>
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
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="admin_dashboard.php" class="logo">HEALCARE ADMIN</a>
        
        <div class="nav-section">
            <div class="nav-section-title">Overview</div>
            <a href="?section=dashboard" class="nav-link <?php echo $section == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">User Management</div>
            <a href="?section=pending-requests" class="nav-link <?php echo $section == 'pending-requests' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Pending Requests
            </a>
            <a href="?section=all-users" class="nav-link <?php echo $section == 'all-users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> All Users
            </a>
            <a href="?section=create-user" class="nav-link <?php echo $section == 'create-user' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Create User
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Operations</div>
            <a href="?section=appointments" class="nav-link <?php echo $section == 'appointments' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <a href="?section=leaves" class="nav-link <?php echo $section == 'leaves' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-minus"></i> Leave Requests
            </a>
            <a href="?section=doctor-scheduling" class="nav-link <?php echo $section == 'doctor-scheduling' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Doctor Scheduling
            </a>
            <a href="?section=canteen-menu" class="nav-link <?php echo $section == 'canteen-menu' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> Canteen Menu
            </a>
            <a href="?section=packages" class="nav-link <?php echo $section == 'packages' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Health Packages
            </a>
            <a href="?section=pharmacy-alerts" class="nav-link <?php echo $section == 'pharmacy-alerts' ? 'active' : ''; ?>">
                <i class="fas fa-pills"></i> Pharmacy Alerts
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Emergency & Contact</div>
            <a href="?section=ambulance" class="nav-link <?php echo $section == 'ambulance' ? 'active' : ''; ?>">
                <i class="fas fa-ambulance"></i> Ambulance Service
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Reports & Analytics</div>
            <a href="reports_manager.php" class="nav-link">
                <i class="fas fa-chart-bar"></i> Revenue Reports
            </a>
            <a href="?section=analytics" class="nav-link <?php echo $section == 'analytics' ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram"></i> Operational Intelligence
            </a>
            <a href="?section=complaints" class="nav-link <?php echo $section == 'complaints' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i> Complaint Logs
            </a>
        </div>

        <div class="nav-section">
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($section == 'dashboard'): ?>
            <!-- Dashboard Overview -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>. Here's what's happening today.</p>
                </div>
                
                <div style="position: relative;">
                    <div class="notification-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php 
                        $notif_count = $pending_requests->num_rows; 
                        $total_pharmacy = ($pharmacy_alerts > 0 ? 1 : 0) + ($expiry_alerts > 0 ? 1 : 0);
                        $total_notifs = $notif_count + $total_pharmacy;
                        if ($total_notifs > 0): 
                        ?>
                            <span class="notification-badge"><?php echo $total_notifs; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-dropdown" id="notifDropdown">
                        <div class="notification-header">
                            <span style="font-weight: 700;">Notifications</span>
                            <span style="font-size: 11px; color: var(--primary-blue); cursor: pointer;">Mark all as read</span>
                        </div>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if ($notif_count > 0): ?>
                                <a href="?section=pending-requests" class="notification-item">
                                    <i class="fas fa-user-plus"></i>
                                    <div>
                                        <p>New staff application pending</p>
                                        <span><?php echo $notif_count; ?> requests need your approval</span>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($pharmacy_alerts > 0): ?>
                                <div class="notification-item" style="align-items: flex-start;">
                                    <i class="fas fa-exclamation-triangle" style="color: var(--accent-orange); margin-top: 5px;"></i>
                                    <div>
                                        <p style="margin-bottom: 5px;">Low Stock Alert (<?php echo $pharmacy_alerts; ?>)</p>
                                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 12px; color: var(--text-gray);">
                                            <?php foreach($low_stock_list as $item): ?>
                                                <li style="margin-bottom: 2px;">• <?php echo htmlspecialchars($item['medicine_name']); ?> <strong style="color: var(--accent-red);">(<?php echo $item['quantity']; ?>)</strong></li>
                                            <?php endforeach; ?>
                                            <?php if($pharmacy_alerts > 5): ?>
                                                <li><em>...and <?php echo ($pharmacy_alerts - 5); ?> more</em></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($expiry_alerts > 0): ?>
                                <div class="notification-item" style="align-items: flex-start;">
                                    <i class="fas fa-hourglass-end" style="color: var(--accent-red); margin-top: 5px;"></i>
                                    <div>
                                        <p style="margin-bottom: 5px;">Expiry Alert (<?php echo $expiry_alerts; ?>)</p>
                                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 12px; color: var(--text-gray);">
                                            <?php foreach($expiry_list as $item): ?>
                                                <li style="margin-bottom: 2px;">• <?php echo htmlspecialchars($item['medicine_name']); ?> 
                                                <?php if($item['days_left'] < 0): ?>
                                                    <strong style="color: var(--accent-red);">(EXPIRED)</strong>
                                                <?php else: ?>
                                                    <strong style="color: var(--accent-orange);">(<?php echo $item['days_left']; ?>d left)</strong>
                                                <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                            <?php if($expiry_alerts > 5): ?>
                                                <li><em>...and <?php echo ($expiry_alerts - 5); ?> more</em></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="notification-item">
                                <i class="fas fa-info-circle" style="color: var(--accent-green);"></i>
                                <div>
                                    <p>System Update</p>
                                    <span>Dashboard features enhanced successfully</span>
                                </div>
                            </div>
                        </div>
                        <div style="padding: 12px; text-align: center; border-top: 1px solid var(--border-color);">
                            <a href="#" style="color: var(--text-gray); font-size: 12px; text-decoration: none;">View all notifications</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--primary-blue);">
                            <i class="fas fa-user-injured"></i>
                        </div>
                    </div>
                    <div class="stat-card-title">Today's Patients</div>
                    <div class="stat-card-value" style="color: var(--primary-blue);"><?php echo $todays_patients; ?></div>
                    <div class="stat-card-subtitle">Active appointments today</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-orange);">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                    <div class="stat-card-title">Pending Bills</div>
                    <div class="stat-card-value" style="color: var(--accent-orange);"><?php echo $pending_bills; ?></div>
                    <div class="stat-card-subtitle">Awaiting payment</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-green);">
                            <i class="fas fa-bed"></i>
                        </div>
                    </div>
                    <div class="stat-card-title">Bed Occupancy</div>
                    <div class="stat-card-value" style="color: var(--accent-green);">85%</div>
                    <div class="stat-card-subtitle">170/200 beds occupied</div>
                </div>

                <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='?section=pharmacy-alerts'">
                    <div class="stat-card-header">
                        <div class="stat-card-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-red);">
                            <i class="fas fa-pills"></i>
                        </div>
                    </div>
                    <div class="stat-card-title">Pharmacy Alerts</div>
                    <div class="stat-card-value" style="color: var(--accent-red);"><?php echo $pharmacy_alerts; ?></div>
                    <div class="stat-card-subtitle">Low stock items • Click to view</div>
                </div>
            </div>

            <div class="chart-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 700;">Consultation Traffic</h3>
                        <p style="font-size: 12px; color: var(--text-gray);">Daily appointment trends for the past week</p>
                    </div>
                    <div style="background: rgba(59, 130, 246, 0.1); padding: 8px 15px; border-radius: 8px; font-size: 11px; color: var(--primary-blue); font-weight: 700;">
                        <i class="fas fa-calendar-alt"></i> LAST 7 DAYS
                    </div>
                </div>
                <div style="height: 350px;">
                    <canvas id="consultationChart"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Quick Actions</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <a href="?section=pending-requests" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-clock"></i> View Pending Requests
                    </a>
                    <a href="?section=create-user" class="btn btn-success" style="text-align: center;">
                        <i class="fas fa-user-plus"></i> Create New User
                    </a>
                    <a href="?section=appointments" class="btn btn-warning" style="text-align: center;">
                        <i class="fas fa-calendar-check"></i> Manage Appointments
                    </a>
                    <a href="?section=packages" class="btn btn-primary" style="text-align: center;">
                        <i class="fas fa-box"></i> Health Packages
                    </a>
                </div>
            </div>

            <!-- Health Packages Sneak-Peek -->
            <div class="content-section" style="background: transparent; border: none; padding: 0;">
                <div class="section-header">
                    <h3 class="section-title">Health Packages Preview</h3>
                    <a href="?section=packages" style="color: var(--primary-blue); font-size: 14px; text-decoration: none; font-weight: 600;">Manage All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php
                    $preview_pkgs = $conn->query("SELECT * FROM health_packages WHERE status = 'Active' ORDER BY created_at DESC LIMIT 3");
                    if ($preview_pkgs && $preview_pkgs->num_rows > 0):
                        while($p = $preview_pkgs->fetch_assoc()):
                            $icon = 'fa-file-medical';
                            if (stripos($p['package_name'], 'Basic') !== false) $icon = 'fa-user-check';
                            elseif (stripos($p['package_name'], 'Comprehensive') !== false) $icon = 'fa-heartbeat';
                            elseif (stripos($p['package_name'], 'Diabetes') !== false) $icon = 'fa-file-prescription';
                    ?>
                        <div style="background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                                <div style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary-blue);">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <h4 style="margin: 0; color: #1e293b; font-size: 16px;"><?php echo htmlspecialchars($p['package_name']); ?></h4>
                            </div>
                            <p style="color: #64748b; font-size: 13px; line-height: 1.5; margin-bottom: 15px;"><?php echo htmlspecialchars($p['package_description']); ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                                <div style="color: #1e293b; font-weight: 800; font-size: 18px;">₹<?php echo number_format($p['discounted_price'], 0); ?></div>
                                <div style="font-size: 11px; background: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 4px; font-weight: 700;"><?php echo $p['discount_percentage']; ?>% OFF</div>
                            </div>
                        </div>
                    <?php endwhile; endif; ?>
                </div>
            </div>

        <?php elseif ($section == 'pharmacy-alerts'): ?>
            <!-- Pharmacy Alerts Detailed View -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Pharmacy Stock Alerts</h1>
                    <p>Monitor critical medicine stock levels</p>
                </div>
            </div>

            <div class="content-section">
                <?php
                try {
                // Fetch critical stock for the detailed view
                $stock_sql = "SELECT * FROM pharmacy_stock WHERE quantity < 20 ORDER BY quantity ASC";
                $stock_res = $conn->query($stock_sql);
                } catch (Exception $e) {
                    $stock_res = false;
                }
                ?>

                <?php if ($stock_res && $stock_res->num_rows > 0): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i>
                        <div>
                            <strong>Low Stock Warning:</strong> 
                            Found <?php echo $stock_res->num_rows; ?> medicines below the minimum stock threshold (20 units).
                        </div>
                    </div>
                    <table style="margin-bottom: 40px;">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Batch No</th>
                                <th>Manufacturer</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Restock Needed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $stock_res->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['medicine_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                                     <td><?php echo htmlspecialchars($item['manufacturer']); ?></td>
                                    <td style="font-weight: bold; color: var(--accent-red);"><?php echo $item['quantity']; ?> Units</td>
                                    <td><span class="badge badge-rejected">Critical</span></td>
                                     <td><a href="#" style="color:var(--primary-blue)">Notify Procurement</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php
                // Fetch Expiry Data
                try {
                     $exp_sql = "SELECT *, DATEDIFF(expiry_date, CURDATE()) as days_left FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) ORDER BY expiry_date ASC";
                     $exp_res = $conn->query($exp_sql);
                } catch (Exception $e) { $exp_res = false; }
                ?>

                <?php if ($exp_res && $exp_res->num_rows > 0): ?>
                    <h3 style="margin-bottom: 15px; color: var(--accent-orange); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-hourglass-end"></i> Expiry Alerts
                    </h3>
                    <div class="alert alert-warning" style="margin-bottom: 20px; background: rgba(245, 158, 11, 0.1); border-color: #f59e0b; color: #f59e0b; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle" style="font-size: 20px;"></i>
                        <div>
                            <strong>Expiry Warning:</strong> 
                            Found <?php echo $exp_res->num_rows; ?> medicines expiring within the next 3 months.
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Batch No</th>
                                <th>Expiry Date</th>
                                <th>Stock Remaining</th>
                                <th>Days Left</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $exp_res->fetch_assoc()): 
                                $is_expired = $item['days_left'] < 0;
                                $color = $is_expired ? '#ef4444' : '#f59e0b';
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['medicine_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                                    <td style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo htmlspecialchars($item['expiry_date']); ?></td>
                                    <td><?php echo $item['quantity']; ?> Units</td>
                                    <td style="font-weight: bold; color: <?php echo $color; ?>;">
                                        <?php echo $is_expired ? 'EXPIRED' : $item['days_left'] . ' Days'; ?>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to discard this batch? This cannot be undone.');">
                                            <input type="hidden" name="action" value="discard_stock">
                                            <input type="hidden" name="stock_id" value="<?php echo $item['stock_id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 5px 10px;">Discard / Write-off</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if ((!$stock_res || $stock_res->num_rows == 0) && (!$exp_res || $exp_res->num_rows == 0)): ?>
                     <div class="placeholder-section">
                        <i class="fas fa-check-circle" style="color: var(--accent-green);"></i>
                        <h3>All Systems Go</h3>
                        <p>All pharmacy stock levels are healthy and no upcoming expiries.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($section == 'pending-requests'): ?>
            <!-- Pending Registration Requests -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Pending Registration Requests</h1>
                    <p>Review and approve or reject staff and doctor applications</p>
                </div>
            </div>

            <div class="filter-container">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" id="approvalSearch" placeholder="Search by name, ID, or email..." onkeyup="filterTable('approvalSearch', 'approvalTable')">
                </div>
            </div>

            <div class="content-section">
                <?php if ($pending_requests->num_rows > 0): ?>
                    <table id="approvalTable">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Role</th>
                                <th>Qualification</th>
                                <th>Experience</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $pending_requests->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                        <small style="color: var(--text-gray);"><?php echo htmlspecialchars($row['email']); ?></small><br>
                                        <small style="color: var(--text-gray);"><?php echo htmlspecialchars($row['phone']); ?></small><br>
                                        <small style="color: var(--primary-blue); font-weight: 600;">ID: <?php echo htmlspecialchars($row['app_id']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-pending"><?php echo ucfirst($row['user_type']); ?></span>
                                        <?php if($row['staff_type']): ?>
                                            <br><small>(<?php echo ucfirst($row['staff_type']); ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['highest_qualification']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_experience']); ?> Years</td>
                                    <td>
                                        <?php if($row['specialization']): ?>
                                            <strong>Spec:</strong> <?php echo htmlspecialchars($row['specialization']); ?><br>
                                        <?php endif; ?>
                                        <?php if($row['dept_preference']): ?>
                                            <strong>Dept:</strong> <?php echo htmlspecialchars($row['dept_preference']); ?><br>
                                        <?php endif; ?>
                                        <?php if($row['resume_path']): ?>
                                            <a href="<?php echo $row['resume_path']; ?>" target="_blank" style="color: var(--primary-blue);">View Resume</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="reg_id" value="<?php echo $row['registration_id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success" style="font-size: 12px; padding: 8px 15px; margin-bottom: 5px;" onclick="return confirm('Approve this application?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" style="font-size: 12px; padding: 8px 15px;" onclick="return confirm('Reject this application?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-inbox"></i>
                        <h3>No Pending Requests</h3>
                        <p>All applications have been processed</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($section == 'all-users'): ?>
            <!-- All Users -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>User Management</h1>
                    <p>View and manage all registered users</p>
                </div>
            </div>

            <div class="filter-container">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" id="userSearch" placeholder="Search users by name, email, or role..." onkeyup="filterTable('userSearch', 'userTable')">
                </div>
            </div>

            <div class="content-section">
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>ID / App ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Username</th>
                            <th>Permissions</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $all_users_display = $conn->query("SELECT u.*, r.app_id FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id ORDER BY u.created_at DESC");
                        while($row = $all_users_display->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><strong style="color: var(--primary-blue);"><?php echo htmlspecialchars($row['app_id'] ?? $row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                 <td><span class="badge badge-<?php 
                                    echo ($row['role'] == 'admin' ? 'active' : ($row['role'] == 'doctor' ? 'pending' : ($row['role'] == 'staff' ? 'completed' : 'active'))); 
                                ?>"><?php echo ucfirst($row['role']); ?></span></td>
                                <td><small style="color: var(--text-gray);"><?php echo htmlspecialchars($row['username']); ?></small></td>
                                <td><small style="color: var(--text-gray);"><?php echo htmlspecialchars($row['permissions'] ?? 'Full Access'); ?></small></td>
                                <td><span class="badge badge-<?php 
                                    echo ($row['status'] == 'Active' ? 'active' : ($row['status'] == 'Suspended' ? 'rejected' : 'pending')); 
                                ?>"><?php echo $row['status']; ?></span></td>
                                <td><small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small></td>
                                <td>
                                    <button class="btn btn-primary" style="font-size: 11px; padding: 5px 10px;" onclick='openEditUserModal(<?php echo json_encode($row); ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('WARNING: This will permanently delete the user and all their records. Continue?')">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 5px 10px;"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit User Modal -->
            <div id="editUserModal" class="modal">
                <div class="modal-content" style="width: 500px;">
                    <span class="close-modal" onclick="closeModal('editUserModal')">&times;</span>
                    <h3>Edit User Profile</h3>
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Hospital Username</label>
                            <input type="text" name="username" id="edit_username" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Official Email</label>
                            <input type="email" name="email" id="edit_email" required>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" id="edit_role">
                                    <option value="doctor">Doctor</option>
                                    <option value="staff">Staff</option>
                                    <option value="patient">Patient</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Account Status</label>
                                <select name="status" id="edit_status">
                                    <option value="Active">Active</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Suspended">Suspended</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Designated Permissions</label>
                            <div id="editPermissionsContainer" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; background: rgba(0,0,0,0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color);">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Changes</button>
                    </form>
                </div>
            </div>

        <?php elseif ($section == 'create-user'): ?>
            <!-- Create User Form -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Create New User</h1>
                    <p>Manually onboard doctors, nurses, and administrative staff</p>
                </div>
            </div>

            <style>
                .role-cards {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                    margin-bottom: 30px;
                }
                .role-card {
                    background: rgba(255, 255, 255, 0.03);
                    border: 1px solid var(--border-color);
                    border-radius: 16px;
                    padding: 20px;
                    text-align: center;
                    cursor: pointer;
                    transition: 0.3s;
                    position: relative;
                }
                .role-card:hover {
                    background: rgba(59, 130, 246, 0.05);
                    border-color: var(--primary-blue);
                    transform: translateY(-5px);
                }
                .role-card.active {
                    background: rgba(59, 130, 246, 0.1);
                    border-color: var(--primary-blue);
                    box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
                }
                .role-card i {
                    font-size: 24px;
                    margin-bottom: 10px;
                    display: block;
                    transition: 0.3s;
                }
                .role-card.active i {
                    color: var(--primary-blue);
                    transform: scale(1.1);
                }
                .role-card span {
                    font-size: 14px;
                    font-weight: 600;
                    color: var(--text-gray);
                }
                .role-card.active span {
                    color: var(--text-white);
                }
                .role-card input {
                    position: absolute;
                    opacity: 0;
                    cursor: pointer;
                }
            </style>

            <div class="content-section" style="max-width: 900px; margin: 0 auto; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05); border-radius: 24px;">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: block;">Step 1: Select User Role</label>
                        <div class="role-cards">
                            <label class="role-card active" onclick="selectRole('doctor')">
                                <input type="radio" name="role" value="doctor" id="role_doctor" checked onchange="toggleDoctorFields()">
                                <i class="fas fa-user-md"></i>
                                <span>Doctor</span>
                            </label>
                            <label class="role-card" onclick="selectRole('staff')">
                                <input type="radio" name="role" value="staff" id="role_staff" onchange="toggleDoctorFields()">
                                <i class="fas fa-users-cog"></i>
                                <span>Hospital Staff</span>
                            </label>
                            <label class="role-card" onclick="selectRole('admin')">
                                <input type="radio" name="role" value="admin" id="role_admin" onchange="toggleDoctorFields()">
                                <i class="fas fa-user-shield"></i>
                                <span>Administrator</span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: block;">Step 2: Basic Information</label>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" placeholder="Enter user's full name" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1);">
                            </div>
                            <div class="form-group">
                                <label>Official Email</label>
                                <input type="email" name="email" placeholder="e.g. name@healcare.com" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1);">
                            </div>
                            <div class="form-group">
                                <label>Username / Login ID</label>
                                <div style="position: relative;">
                                    <input type="text" name="username" placeholder="e.g. junael.mathew" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); width: 100%; padding-right: 120px;">
                                    <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 12px; color: var(--primary-blue); font-weight: 600;">@healcare.com</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Temporary Password</label>
                                <div style="position: relative;">
                                    <input type="password" name="password" id="tempPass" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); width: 100%;">
                                    <i class="fas fa-sync" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); cursor: pointer;" onclick="generatePass()" title="Generate Password"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="doctorFields" style="display: block; background: rgba(59, 130, 246, 0.05); padding: 30px; border-radius: 20px; border: 1px solid rgba(59, 130, 246, 0.1); margin: 25px 0;">
                        <h4 style="margin-bottom: 25px; font-size: 18px; color: var(--primary-blue); border-bottom: 1px solid rgba(59, 130, 246, 0.2); padding-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-stethoscope"></i> Doctor Specialization
                        </h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Specialization</label>
                                <input type="text" name="specialization" placeholder="e.g. Cardiologist">
                            </div>
                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" name="qualification" placeholder="e.g. MBBS, MD">
                            </div>
                            <div class="form-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="experience" placeholder="e.g. 5">
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department">
                                    <option value="General Medicine / Cardiovascular">General Medicine / Cardiovascular</option>
                                    <option value="Gynecology">Gynecology</option>
                                    <option value="Orthopedics">Orthopedics</option>
                                    <option value="ENT">ENT</option>
                                    <option value="Ophthalmology">Ophthalmology</option>
                                    <option value="Dermatology">Dermatology</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date of Joining</label>
                                <input type="date" name="date_of_join" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Designation</label>
                                <select name="designation">
                                    <option value="Consultant">Consultant</option>
                                    <option value="Senior Doctor">Senior Doctor</option>
                                    <option value="Junior Doctor">Junior Doctor</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="staffFields" style="display: none; background: rgba(16, 185, 129, 0.05); padding: 30px; border-radius: 20px; border: 1px solid rgba(16, 185, 129, 0.1); margin: 25px 0;">
                        <h4 style="margin-bottom: 25px; font-size: 18px; color: var(--accent-green); border-bottom: 1px solid rgba(16, 185, 129, 0.2); padding-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-id-badge"></i> Staff Assignment
                        </h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Staff Type</label>
                                <select name="staff_type" id="staffTypeSelect" onchange="toggleStaffLabFields()">
                                    <option value="receptionist">Receptionist</option>
                                    <option value="nurse">Nurse</option>
                                    <option value="pharmacist">Pharmacist</option>
                                    <option value="lab_staff">Lab Staff</option>
                                    <option value="canteen_staff">Canteen Staff</option>
                                </select>
                            </div>
                            <div class="form-group" id="labTypeGroup" style="display:none;">
                                <label>Lab Type / Specialization</label>
                                <select name="staff_specialization">
                                    <option value="Blood / Pathology Lab">Blood / Pathology Lab</option>
                                    <option value="X-Ray / Imaging Lab">X-Ray / Imaging Lab</option>
                                    <option value="Diagnostic Lab">Diagnostic Lab</option>
                                    <option value="Ultrasound Lab">Ultrasound Lab</option>
                                </select>
                            </div>
                            <div class="form-group" id="nurseDeptGroup" style="display:none;">
                                <label>Nursing Department</label>
                                <select name="staff_department">
                                    <option value="Emergency">Emergency</option>
                                    <option value="ICU">ICU</option>
                                    <option value="OT">OT</option>
                                    <option value="General Ward">General Ward</option>
                                    <option value="OPD">OPD</option>
                                </select>
                            </div>
                            <div class="form-group" id="canteenRoleGroup" style="display:none;">
                                <label>Canteen Job Role</label>
                                <select name="staff_role">
                                    <option value="Head Chef">Head Chef</option>
                                    <option value="Cook">Cook</option>
                                    <option value="Server">Server</option>
                                    <option value="Cleaner">Cleaner</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Qualification Details</label>
                                <input type="text" name="staff_qualification" placeholder="e.g. B.Sc Nursing, D.Pharm">
                            </div>
                            <div class="form-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="staff_experience" placeholder="e.g. 3">
                            </div>
                            <div class="form-group">
                                <label>Date of Joining</label>
                                <input type="date" name="staff_date_of_join" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                             <div class="form-group">
                                <label>Shift Preference</label>
                                <select name="staff_shift">
                                    <option value="Day">Day Shift</option>
                                    <option value="Night">Night Shift</option>
                                    <option value="Rotational">Rotational</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: block;">Step 3: Access Permissions</label>
                        <div id="permissionsContainer" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; background: rgba(255,255,255,0.02); padding: 20px; border-radius: 16px;">
                            <!-- Permissions will be dynamically loaded here by JS -->
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 16px; font-weight: 700; border-radius: 16px; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);">
                        <i class="fas fa-paper-plane" style="margin-right: 10px;"></i> Create User & Send Credentials
                    </button>
                    <p style="text-align: center; font-size: 12px; color: var(--text-gray); margin-top: 15px;">
                        <i class="fas fa-lock"></i> All login credentials will be securely sent to the user's official email.
                    </p>
                </form>
            </div>

        <?php elseif ($section == 'appointments'): ?>
            <!-- Appointments Management -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Appointments Management</h1>
                    <p>View and manage all patient appointments</p>
                </div>
            </div>

            <div class="content-section">
                <?php
                // Fetch All Appointments
                $all_appts = $conn->query("
                    SELECT a.*, 
                           rd.name as doctor_name, 
                           rp.name as patient_name_reg,
                           pp.name as patient_name_prof
                    FROM appointments a
                    LEFT JOIN users ud ON a.doctor_id = ud.user_id
                    LEFT JOIN registrations rd ON ud.registration_id = rd.registration_id
                    LEFT JOIN users up ON a.patient_id = up.user_id
                    LEFT JOIN registrations rp ON up.registration_id = rp.registration_id
                    LEFT JOIN patient_profiles pp ON a.patient_id = pp.user_id
                    ORDER BY a.appointment_date DESC
                ");
                ?>

                <?php if ($all_appts && $all_appts->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Queue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($appt = $all_appts->fetch_assoc()): 
                                $p_name = $appt['patient_name_prof'] ?? $appt['patient_name_reg'] ?? 'Walk-in/Unknown';
                                $d_name = $appt['doctor_name'] ?? 'Unassigned';
                            ?>
                                <tr>
                                    <td><small style="color:var(--primary-blue);">#<?php echo $appt['appointment_id']; ?></small></td>
                                    <td><strong><?php echo htmlspecialchars($p_name); ?></strong></td>
                                    <td><?php echo htmlspecialchars($d_name); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($appt['appointment_time'] ?? $appt['appointment_date'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($appt['department']); ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($appt['status'] == 'Scheduled' ? 'active' : ($appt['status'] == 'Requested' ? 'pending' : 'completed')); ?>"><?php echo $appt['status']; ?></span></td>
                                    <td><?php echo $appt['queue_number'] ?? '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Appointments Found</h3>
                        <p>There are no appointments in the system yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($section == 'doctor-scheduling'): ?>
            <!-- Doctor Scheduling -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Doctor Scheduling</h1>
                    <p>Manage doctor availability, assign departments, and manage weekly schedules</p>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">All Doctors</h3>
                    <a href="?section=create-user" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Doctor</a>
                </div>

                <?php
                $doctors_sql = "SELECT d.*, r.name, u.username, u.email 
                                FROM doctors d 
                                JOIN users u ON d.user_id = u.user_id 
                                JOIN registrations r ON u.registration_id = r.registration_id 
                                ORDER BY d.department ASC";
                $doctors_res = $conn->query($doctors_sql);
                
                if ($doctors_res && $doctors_res->num_rows > 0):
                    $current_dept = '';
                    while($doc = $doctors_res->fetch_assoc()):
                        if ($current_dept != $doc['department']):
                            $current_dept = $doc['department'];
                            echo '<div style="background: rgba(59, 130, 246, 0.05); padding: 10px 20px; border-radius: 8px; margin: 30px 0 15px; border-left: 4px solid var(--primary-blue);">';
                            echo '<h4 style="color: var(--primary-blue); font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">' . htmlspecialchars($current_dept ?: 'Unassigned Dept') . '</h4>';
                            echo '</div>';
                        endif;
                ?>
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s; hover: background: rgba(255,255,255,0.04);">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="width: 50px; height: 50px; background: var(--primary-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                                    <?php echo substr($doc['name'], 0, 1); ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 16px;">DR. <?php echo htmlspecialchars($doc['name']); ?></h4>
                                    <p style="font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($doc['specialization']); ?> • <?php echo htmlspecialchars($doc['username']); ?></p>
                                    <span class="badge badge-<?php 
                                        echo ($doc['availability_status'] == 'Available' ? 'active' : ($doc['availability_status'] == 'Busy' ? 'pending' : 'rejected')); 
                                    ?>" style="margin-top: 5px; display: inline-block;">
                                        <?php echo $doc['availability_status'] ?: 'Available'; ?>
                                    </span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button onclick="openScheduleModal(<?php echo $doc['user_id']; ?>, '<?php echo htmlspecialchars($doc['name']); ?>')" class="btn btn-primary" style="font-size: 12px; padding: 8px 15px;"><i class="fas fa-calendar-alt"></i> Schedule</button>
                                <button onclick="openDeptModal(<?php echo $doc['user_id']; ?>, '<?php echo htmlspecialchars($doc['department']); ?>', '<?php echo htmlspecialchars($doc['specialization']); ?>')" class="btn btn-warning" style="font-size: 12px; padding: 8px 15px;"><i class="fas fa-building"></i> Dept</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_doctor_availability">
                                    <input type="hidden" name="doctor_id" value="<?php echo $doc['user_id']; ?>">
                                    <select name="availability_status" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 12px; border-radius: 6px; background: var(--darkest-blue); color: white; border: 1px solid var(--border-color);">
                                        <option value="Available" <?php echo $doc['availability_status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="Busy" <?php echo $doc['availability_status'] == 'Busy' ? 'selected' : ''; ?>>Busy</option>
                                        <option value="On Leave" <?php echo $doc['availability_status'] == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                <?php endwhile; else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-user-md"></i>
                        <h3>No Doctors Found</h3>
                        <p>Start by adding doctors from the "Create User" section.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Schedule Modal -->
            <div id="scheduleModal" class="modal">
                <div class="modal-content" style="width: 600px;">
                    <span class="close-modal" onclick="closeModal('scheduleModal')">&times;</span>
                    <h3 id="scheduleTitle" style="margin-bottom: 25px;">Manage Schedule</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_doctor_schedule">
                        <input type="hidden" name="doctor_id" id="sched_doc_id">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Day of Week</label>
                                <select name="day_of_week" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="Available">Available</option>
                                    <option value="Not Available">Not Available</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Start Time</label>
                                <input type="time" name="start_time" value="09:00" required>
                            </div>
                            <div class="form-group">
                                <label>End Time</label>
                                <input type="time" name="end_time" value="17:00" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 20px;">Save Schedule Entry</button>
                    </form>
                    
                    <div style="margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <h4 style="font-size: 14px; margin-bottom: 15px;">Current Weekly Schedule:</h4>
                        <div id="scheduleList" style="font-size: 12px; color: var(--text-gray);">
                            <!-- Will be populated via JS or shown in next reload -->
                            <p>Select a doctor and update entries. Each entry overwrites previous setting for that day.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dept Modal -->
            <div id="deptModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('deptModal')">&times;</span>
                    <h3>Assign Department</h3>
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="update_doctor_dept">
                        <input type="hidden" name="doctor_id" id="dept_doc_id">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Department</label>
                            <select name="department" id="dept_select" required>
                                <option value="General Medicine / Cardiovascular">General Medicine / Cardiovascular</option>
                                <option value="Gynecology">Gynecology</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="ENT">ENT</option>
                                <option value="Ophthalmology">Ophthalmology</option>
                                <option value="Dermatology">Dermatology</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Specialization</label>
                            <input type="text" name="specialization" id="spec_input" required>
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%;">Update Details</button>
                    </form>
                </div>
            </div>

        <?php elseif ($section == 'leaves'): ?>
            <!-- Leave Management -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Leave Requests</h1>
                    <p>Manage doctor leave applications</p>
                </div>
            </div>

            <div class="content-section">
                <?php
                $leaves_sql = "
                    SELECT dl.*, r.name as doc_name, d.department 
                    FROM doctor_leaves dl 
                    JOIN users u ON dl.doctor_id = u.user_id 
                    JOIN registrations r ON u.registration_id = r.registration_id
                    LEFT JOIN doctors d ON u.user_id = d.user_id 
                    WHERE dl.status = 'Pending' 
                    ORDER BY dl.start_date ASC
                ";
                $leave_reqs = $conn->query($leaves_sql);
                ?>

                <?php if ($leave_reqs && $leave_reqs->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Leave Dates</th>
                                <th>Reason</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($lr = $leave_reqs->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">Dr. <?php echo htmlspecialchars($lr['doc_name']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($lr['department']); ?></td>
                                    <td>
                                        <?php echo date('M d', strtotime($lr['start_date'])); ?> - <?php echo date('M d, Y', strtotime($lr['end_date'])); ?>
                                        <div style="font-size: 11px; color: #94a3b8;">
                                            <?php 
                                            $d1 = new DateTime($lr['start_date']);
                                            $d2 = new DateTime($lr['end_date']);
                                            echo ($d1->diff($d2)->days + 1) . ' Days';
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="max-width: 250px; font-size: 13px; color: #cbd5e1;"><?php echo htmlspecialchars($lr['reason']); ?></div>
                                    </td>
                                    <td><span class="badge" style="background: rgba(255,255,255,0.1);"><?php echo htmlspecialchars($lr['leave_type']); ?></span></td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="approve_leave">
                                                <input type="hidden" name="leave_id" value="<?php echo $lr['leave_id']; ?>">
                                                <button type="submit" class="btn btn-success" style="font-size: 11px; padding: 6px 12px;"><i class="fas fa-check"></i> Approve</button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="reject_leave">
                                                <input type="hidden" name="leave_id" value="<?php echo $lr['leave_id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 6px 12px;"><i class="fas fa-times"></i> Reject</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-umbrella-beach"></i>
                        <h3>No Pending Leave Requests</h3>
                        <p>All leave applications have been processed.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($section == 'canteen-menu'): ?>
            <!-- Canteen Menu Management -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Canteen Management</h1>
                    <p>Track live orders and manage hospital food menu</p>
                </div>
            </div>

            <div class="content-section">
                <!-- Menu Management Section -->
                <div class="section-header">
                    <h3 class="section-title">Food Menu Management</h3>
                </div>

                <!-- Search and Add Section -->
                <div class="filter-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 20px;">
                    <div class="search-input-group" style="flex: 1; max-width: 400px; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray);"></i>
                        <input type="text" id="foodSearch" placeholder="Search food by name or category..." onkeyup="filterFoodItems()" style="width: 100%; padding: 12px 15px 12px 45px; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 10px; color: white; font-size: 14px;">
                    </div>
                    <button onclick="openMenuModal()" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus-circle"></i> Add Food Item
                    </button>
                </div>

                <!-- Add Canteen specific styles inline for this section -->
                <style>
                    .food-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                        gap: 25px;
                        margin-top: 20px;
                    }
                    .food-card {
                        background: #1e293b;
                        border-radius: 16px;
                        overflow: hidden;
                        border: 1px solid rgba(255,255,255,0.05);
                        transition: 0.3s;
                        position: relative;
                        display: flex;
                        flex-direction: column;
                    }
                    .food-card:hover {
                        transform: translateY(-5px);
                        border-color: #3b82f6;
                        box-shadow: 0 10px 40px rgba(0,0,0,0.4);
                    }
                    .food-card img {
                        width: 100%;
                        height: 180px;
                        object-fit: cover;
                    }
                    .card-content {
                        padding: 18px;
                        text-align: left;
                        background: #1e293b;
                        position: relative;
                        flex: 1;
                    }
                    .food-name {
                        font-size: 16px;
                        font-weight: 700;
                        color: #fff;
                        margin: 0;
                        line-height: 1.3;
                    }
                    .food-price {
                        font-size: 18px;
                        font-weight: 800;
                        color: #3b82f6;
                        margin-top: 8px;
                    }
                    .food-category-badge {
                        position: absolute;
                        top: -12px;
                        left: 15px;
                        background: #3b82f6;
                        color: white;
                        padding: 4px 12px;
                        border-radius: 20px;
                        font-size: 10px;
                        font-weight: 700;
                        text-transform: uppercase;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
                        z-index: 10;
                    }
                    .stock-controls {
                        padding: 12px 18px;
                        display: flex;
                        gap: 8px;
                        background: rgba(15, 23, 42, 0.8);
                        border-top: 1px solid rgba(255,255,255,0.05);
                    }
                    .btn-stock {
                        flex: 1;
                        padding: 10px;
                        border-radius: 8px;
                        border: none;
                        background: #0f172a;
                        color: rgba(255,255,255,0.4);
                        font-size: 12px;
                        font-weight: 700;
                        cursor: pointer;
                        transition: 0.3s;
                    }
                    .btn-stock.active-stock, .btn-stock.active-out {
                        background: #10b981;
                        color: white;
                    }
                    .admin-overlay {
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        display: flex;
                        gap: 5px;
                    }
                    .btn-icon-mini {
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        background: rgba(15, 23, 42, 0.9);
                        color: white;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border: none;
                        cursor: pointer;
                        transition: 0.2s;
                        font-size: 12px;
                    }
                    .btn-icon-mini:hover { transform: scale(1.1); }
                    .btn-edit-icon:hover { background: #3b82f6 !important; }
                    .btn-delete-icon:hover { background: #ef4444 !important; }
                </style>

                <div class="food-grid">
                    <?php
                    $menu_items = $conn->query("SELECT * FROM canteen_menu ORDER BY item_category, item_name");
                    if ($menu_items && $menu_items->num_rows > 0):
                        while ($m = $menu_items->fetch_assoc()):
                            $is_bev = (stripos($m['item_category'], 'Beverage') !== false || stripos($m['item_category'], 'Drink') !== false || in_array($m['item_name'], ['Boost', 'Horlicks', 'Tea', 'Coffee', 'Milk', 'Lassi', 'Buttermilk']));
                            $fallback = $is_bev ? 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600' : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600';
                            $img = $m['image_url'] ?: $fallback;
                            $is_avail = $m['availability'] == 'Available';
                    ?>
                        <div class="food-card">
                            <img src="<?php echo $img; ?>" 
                                 alt="<?php echo htmlspecialchars($m['item_name']); ?>"
                                 onerror="this.onerror=null; this.src='<?php echo $fallback; ?>';">
                            
                            <div class="admin-overlay">
                                <button onclick='editMenuItem(<?php echo json_encode($m); ?>)' class="btn-icon-mini btn-edit-icon"><i class="fas fa-edit"></i></button>
                                <form method="POST" onsubmit="return confirm('Delete this item?')" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_menu_item">
                                    <input type="hidden" name="menu_id" value="<?php echo $m['menu_id']; ?>">
                                    <button type="submit" class="btn-icon-mini btn-delete-icon" style="background:rgba(239,68,68,0.8);"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>

                            <div class="card-content">
                                <span class="food-category-badge"><?php echo explode(' / ', $m['item_category'])[0]; ?></span>
                                <h3 class="food-name"><?php echo htmlspecialchars($m['item_name']); ?></h3>
                                <div class="food-price">₹<?php echo number_format($m['price'], 0); ?></div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 10px; display: flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-carrot" style="color: #10b981;"></i> <?php echo $m['diet_type']; ?>
                                </div>
                            </div>

                            <div class="stock-controls">
                                <form method="POST" action="?section=canteen-menu" style="display:flex; gap:10px; width:100%;">
                                    <input type="hidden" name="action" value="toggle_stock">
                                    <input type="hidden" name="menu_id" value="<?php echo $m['menu_id']; ?>">
                                    
                                    <button type="submit" name="status" value="Available" class="btn-stock <?php echo $is_avail ? 'active-stock' : ''; ?>">
                                        In Stock
                                    </button>
                                    <button type="submit" name="status" value="Out of Stock" class="btn-stock <?php echo !$is_avail ? 'active-out' : ''; ?>">
                                        Out of Stock
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; 
                    else: ?>
                        <div class="placeholder-section" style="grid-column: 1/-1;">
                            <i class="fas fa-utensils"></i>
                            <p>No food items found. Start by adding to your menu.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Canteen Menu Modal -->
            <div id="canteenMenuModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('canteenMenuModal')">&times;</span>
                    <h3 id="menuModalTitle" style="margin-bottom: 25px;">Add Food Item</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_menu_item">
                        <input type="hidden" name="menu_id" id="item_id">
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Food Item Name</label>
                            <input type="text" name="food_name" id="item_name" required>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="meal_category" id="item_cat">
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Snacks">Snacks</option>
                                    <option value="Dinner">Dinner</option>
                                    <option value="Beverages">Beverages</option>
                                    <option value="Desserts">Desserts</option>
                                    <option value="Patient Special">Patient Special</option>
                                    <option value="Other Food Items">Other Food Items</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Price (₹)</label>
                                <input type="number" step="0.01" name="price" id="item_price" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Diet Type</label>
                                <select name="diet_type" id="item_diet">
                                    <option value="Normal">Normal</option>
                                    <option value="Diabetic">Diabetic</option>
                                    <option value="Low-Salt">Low-Salt</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="availability" id="item_avail">
                                    <option>Available</option>
                                    <option>Out of Stock</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Short Description</label>
                            <textarea name="description" id="item_desc" rows="2"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Food Product Image</label>
                            <input type="file" name="food_image" id="item_image_file" accept="image/*" style="padding: 10px; background: rgba(255,255,255,0.05); border: 1px dashed var(--border-color); border-radius: 8px; width: 100%;">
                            <p style="font-size: 10px; color: var(--text-gray); margin-top: 5px;">Leave empty to keep existing image (when editing)</p>
                        </div>

                        <button type="submit" class="btn btn-success" style="width: 100%;">Save Menu Item</button>
                    </form>
                </div>
            </div>

        <?php elseif ($section == 'packages'): ?>
            <!-- Health Packages Section -->
            <div style="margin: -40px -50px 40px -50px; background: #1e40af; padding: 60px 50px; text-align: center; color: white;">
                <h1 style="font-size: 42px; font-weight: 800; margin-bottom: 15px;">Health Packages</h1>
                <p style="font-size: 18px; opacity: 0.9; max-width: 600px; margin: 0 auto;">Comprehensive checkups for a healthier you. Book directly.</p>
            </div>

            <div class="content-section" style="background: transparent; border: none; padding: 0;">
                <div class="section-header" style="margin-bottom: 30px;">
                    <h3 class="section-title" style="color: white;">Package Management</h3>
                    <button onclick="openPackageModal()" class="btn btn-success"><i class="fas fa-plus"></i> Create New Package</button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-top: 20px;">
                    <?php
                    $pkgs = $conn->query("SELECT * FROM health_packages ORDER BY created_at DESC");
                    if ($pkgs && $pkgs->num_rows > 0):
                        while($p = $pkgs->fetch_assoc()):
                            // Determine icon based on name
                            $icon = 'fa-file-medical';
                            $icon_bg = 'rgba(16, 185, 129, 0.1)';
                            $icon_color = '#10b981';
                            
                            if (stripos($p['package_name'], 'Basic') !== false) {
                                $icon = 'fa-user-check';
                            } elseif (stripos($p['package_name'], 'Comprehensive') !== false) {
                                $icon = 'fa-heartbeat';
                            } elseif (stripos($p['package_name'], 'Diabetes') !== false) {
                                $icon = 'fa-file-prescription';
                            }
                    ?>
                        <div style="background: #ffffff; border-radius: 24px; padding: 35px; display: flex; flex-direction: column; position: relative; transition: all 0.3s; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 25px;">
                                <div style="width: 50px; height: 50px; background: <?php echo $icon_bg; ?>; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: <?php echo $icon_color; ?>;">
                                    <i class="fas <?php echo $icon; ?>" style="font-size: 22px;"></i>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick='editPackage(<?php echo json_encode($p); ?>)' class="btn" style="background: #f1f5f9; color: #64748b; font-size: 11px; padding: 8px;"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this package?')">
                                        <input type="hidden" name="action" value="delete_package">
                                        <input type="hidden" name="package_id" value="<?php echo $p['package_id']; ?>">
                                        <button type="submit" class="btn" style="background: #fee2e2; color: #ef4444; font-size: 11px; padding: 8px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>

                            <h3 style="margin: 0 0 10px; font-size: 22px; color: #1e293b; font-weight: 700;"><?php echo htmlspecialchars($p['package_name']); ?></h3>
                            <p style="font-size: 14px; color: #64748b; margin-bottom: 25px; line-height: 1.6;"><?php echo htmlspecialchars($p['package_description']); ?></p>

                            <div style="margin-bottom: 30px; flex-grow: 1;">
                                <h4 style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 15px; font-weight: 800;">Includes:</h4>
                                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                                    <?php 
                                    $tests = explode(',', $p['included_tests']);
                                    foreach($tests as $t): 
                                    ?>
                                        <li style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 10px;">
                                            <i class="fas fa-check" style="color: #10b981; font-size: 12px;"></i> 
                                            <?php echo trim(htmlspecialchars($t)); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div style="display: flex; align-items: center; gap: 15px; margin-top: auto; padding-top: 25px; border-top: 1px solid #f1f5f9;">
                                <div style="font-size: 30px; font-weight: 800; color: #1e293b;">₹<?php echo number_format($p['discounted_price'], 0); ?></div>
                                <div style="font-size: 16px; text-decoration: line-through; color: #94a3b8; font-weight: 500;">₹<?php echo number_format($p['original_price'], 0); ?></div>
                                <div style="font-size: 12px; background: #fee2e2; color: #ef4444; padding: 4px 10px; border-radius: 6px; font-weight: 700;"><?php echo $p['discount_percentage']; ?>% OFF</div>
                            </div>
                            
                            <button class="btn btn-primary" style="width: 100%; margin-top: 25px; padding: 12px; font-weight: 700; background: #1e40af; border-radius: 12px;">Select Package</button>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="placeholder-section" style="grid-column: 1/-1; background: rgba(255,255,255,0.02); border-radius: 20px; padding: 60px;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                            <h3 style="color: white;">No Packages Found</h3>
                            <p>Start by creating a new health checkup package.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Health Package Modal -->
            <div id="packageModal" class="modal">
                <div class="modal-content" style="width: 600px;">
                    <span class="close-modal" onclick="closeModal('packageModal')">&times;</span>
                    <h3 id="pkgModalTitle" style="margin-bottom: 25px;">Create New Package</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_package">
                        <input type="hidden" name="package_id" id="pkg_id">
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Package Name</label>
                            <input type="text" name="package_name" id="pkg_name" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Short Description</label>
                            <textarea name="description" id="pkg_desc" rows="2" required></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Included Tests (Comma separated)</label>
                            <input type="text" name="included_tests" id="pkg_tests" placeholder="e.g. CBC, Lipid Profile, X-Ray" required>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Actual Price (₹)</label>
                                <input type="number" name="actual_price" id="pkg_actual" oninput="calculateDiscount()" required>
                            </div>
                            <div class="form-group">
                                <label>Discounted Price (₹)</label>
                                <input type="number" name="discount_price" id="pkg_discount" oninput="calculateDiscount()" required>
                            </div>
                            <div class="form-group">
                                <label>Discount %</label>
                                <input type="number" name="discount_percent" id="pkg_percent" readonly>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Status</label>
                            <select name="status" id="pkg_status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success" style="width: 100%;">Save Package Details</button>
                    </form>
                </div>
            </div>

        <?php elseif ($section == 'ambulance'): ?>
            <!-- Ambulance Service -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Ambulance Emergency Service</h1>
                    <p>Manage emergency contact numbers and ambulance availability</p>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Emergency Contacts</h3>
                    <button onclick="openModal('ambulanceModal')" class="btn btn-success"><i class="fas fa-plus"></i> Add Contact</button>
                </div>
                
                <?php
                $ambulances = $conn->query("SELECT * FROM ambulance_contacts ORDER BY availability ASC, created_at DESC");
                if ($ambulances && $ambulances->num_rows > 0):
                ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Driver Name</th>
                                <th>Phone Number</th>
                                <th>Vehicle Info</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($amb = $ambulances->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($amb['driver_name']); ?></strong></td>
                                    <td><span style="color:var(--primary-blue); font-weight:600;"><?php echo htmlspecialchars($amb['phone_number']); ?></span></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($amb['vehicle_type']); ?></small><br>
                                        <strong><?php echo htmlspecialchars($amb['vehicle_number']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($amb['location']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($amb['availability'] == 'Available' ? 'active' : ($amb['availability'] == 'On Duty' ? 'pending' : 'rejected')); ?>">
                                            <?php echo $amb['availability']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this contact?')">
                                            <input type="hidden" name="action" value="delete_ambulance">
                                            <input type="hidden" name="contact_id" value="<?php echo $amb['contact_id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 5px 10px;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-ambulance"></i>
                        <h3>No Active Ambulances</h3>
                        <p>Start by adding emergency ambulance contacts.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Ambulance Modal -->
            <div id="ambulanceModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('ambulanceModal')">&times;</span>
                    <h3 style="margin-bottom: 25px;">Add New Emergency Contact</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_ambulance">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Driver Name</label>
                            <input type="text" name="driver_name" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Phone Number</label>
                            <input type="text" name="phone_number" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Vehicle Number</label>
                            <input type="text" name="vehicle_number" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type">
                                <option value="Basic Life Support">Basic Life Support</option>
                                <option value="Advanced Life Support">Advanced Life Support</option>
                                <option value="Patient Transport">Patient Transport</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>Base Location</label>
                            <input type="text" name="location" required>
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%;">Save Contact</button>
                    </form>
                </div>
            </div>

        <?php elseif ($section == 'reports'): ?>
            <!-- Revenue Reports -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Revenue Reports & Analytics</h1>
                    <p>Generate daily, weekly, and monthly reports</p>
                </div>
            </div>

                <div class="content-section">
                    <div class="section-header">
                        <h3 class="section-title">Generate Report</h3>
                    </div>
                    
                    <form method="POST" action="?section=reports">
                        <div class="form-grid" style="max-width: 800px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <div class="form-group">
                                <label>Report Period</label>
                                <select name="report_type" class="form-control">
                                    <option value="Daily" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'Daily') ? 'selected' : ''; ?>>Today (Daily)</option>
                                    <option value="Weekly" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'Weekly') ? 'selected' : ''; ?>>This Week</option>
                                    <option value="Monthly" <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'Monthly') ? 'selected' : ''; ?>>This Month</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="report_category" class="form-control">
                                    <option value="Revenue" <?php echo (isset($_POST['report_category']) && $_POST['report_category'] == 'Revenue') ? 'selected' : ''; ?>>Revenue & Finance</option>
                                    <option value="Appointments" <?php echo (isset($_POST['report_category']) && $_POST['report_category'] == 'Appointments') ? 'selected' : ''; ?>>Appointments</option>
                                    <option value="Patients" <?php echo (isset($_POST['report_category']) && $_POST['report_category'] == 'Patients') ? 'selected' : ''; ?>>New Patients</option>
                                </select>
                            </div>
                            <div class="form-group" style="display: flex; align-items: flex-end;">
                                <button type="submit" name="generate_report" class="btn btn-primary" style="width: 100%;"><i class="fas fa-chart-line"></i> Generate Report</button>
                            </div>
                        </div>
                    </form>

                    <?php
                    if (isset($_POST['generate_report'])) {
                        $type = $_POST['report_type'];
                        $cat = $_POST['report_category'];
                        
                        $date_filter = "";
                        $date_col = "";
                        $table = "";
                        $cols = "*";
                        
                        // Set Date Filter Logic
                        if ($type == 'Daily') {
                            $date_cond = "DATE(date_column) = CURDATE()";
                            $period_label = date('F j, Y');
                        } elseif ($type == 'Weekly') {
                            $date_cond = "YEARWEEK(date_column, 1) = YEARWEEK(CURDATE(), 1)";
                            $period_label = "Week " . date('W, Y');
                        } elseif ($type == 'Monthly') {
                            $date_cond = "MONTH(date_column) = MONTH(CURDATE()) AND YEAR(date_column) = YEAR(CURDATE())";
                            $period_label = date('F Y');
                        }

                        // Set Query Logic based on Category
                        $query = "";
                        $total_val = 0;
                        $headers = [];
                        $data_rows = [];

                        if ($cat == 'Revenue') {
                            $date_col = 'bill_date';
                            // Filter only Paid bills for revenue
                            $query = "SELECT bill_id, patient_id, bill_type, total_amount, bill_date, payment_status 
                                      FROM billing 
                                      WHERE " . str_replace('date_column', $date_col, $date_cond) . " AND payment_status = 'Paid'
                                      ORDER BY bill_date DESC";
                            $headers = ['Invoice ID', 'Date', 'Type', 'Amount', 'Status'];
                        } elseif ($cat == 'Appointments') {
                            $date_col = 'appointment_date';
                            $query = "SELECT a.appointment_id, a.appointment_date, a.status, a.department, pp.name as patient_name
                                      FROM appointments a
                                      LEFT JOIN patient_profiles pp ON a.patient_id = pp.user_id
                                      WHERE " . str_replace('date_column', $date_col, $date_cond) . "
                                      ORDER BY a.appointment_date DESC";
                            $headers = ['Appt ID', 'Date', 'Patient', 'Department', 'Status'];
                        } elseif ($cat == 'Patients') {
                            $date_col = 'created_at';
                            $query = "SELECT users.user_id, users.username, users.email, users.created_at, r.name 
                                      FROM users 
                                      LEFT JOIN registrations r ON users.registration_id = r.registration_id
                                      WHERE users.role = 'patient' AND " . str_replace('date_column', 'users.created_at', $date_cond) . "
                                      ORDER BY users.created_at DESC";
                            $headers = ['User ID', 'Name', 'Email', 'Joined Date'];
                        }

                        if ($query) {
                            $result = $conn->query($query);
                            if ($result && $result->num_rows > 0) {
                                echo '<div style="margin-top: 30px;">';
                                echo '<h4 style="margin-bottom: 15px; color: var(--text-light);">Results for: <span style="color: var(--primary-blue);">' . $cat . '</span> - <span style="font-weight:400;">' . $period_label . '</span></h4>';
                                echo '<table><thead><tr>';
                                foreach ($headers as $h) echo "<th>$h</th>";
                                echo '</tr></thead><tbody>';

                                while ($row = $result->fetch_assoc()) {
                                    echo '<tr>';
                                    if ($cat == 'Revenue') {
                                        echo '<td>#' . $row['bill_id'] . '</td>';
                                        echo '<td>' . date('M d', strtotime($row['bill_date'])) . '</td>';
                                        echo '<td>' . $row['bill_type'] . '</td>';
                                        echo '<td style="color: #10b981; font-weight:700;">$' . number_format($row['total_amount'], 2) . '</td>';
                                        echo '<td><span class="badge badge-active">Paid</span></td>';
                                        $total_val += $row['total_amount'];
                                    } elseif ($cat == 'Appointments') {
                                        echo '<td>#' . $row['appointment_id'] . '</td>';
                                        echo '<td>' . date('M d, H:i', strtotime($row['appointment_date'])) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['patient_name'] ?? 'Unknown') . '</td>';
                                        echo '<td>' . $row['department'] . '</td>';
                                        echo '<td>' . $row['status'] . '</td>';
                                        $total_val++;
                                    } elseif ($cat == 'Patients') {
                                        echo '<td>' . $row['username'] . '</td>';
                                        echo '<td>' . htmlspecialchars($row['name'] ?? 'N/A') . '</td>';
                                        echo '<td>' . $row['email'] . '</td>';
                                        echo '<td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>';
                                        $total_val++;
                                    }
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';

                                // Summary Box
                                echo '<div style="margin-top: 20px; padding: 20px; background: rgba(59, 130, 246, 0.1); border-radius: 10px; display: inline-block;">';
                                if ($cat == 'Revenue') {
                                    echo '<span style="font-size: 14px; color: var(--text-gray);">Total Revenue for Period:</span><br>';
                                    echo '<strong style="font-size: 24px; color: var(--primary-blue);">$ ' . number_format($total_val, 2) . '</strong>';
                                } else {
                                    echo '<span style="font-size: 14px; color: var(--text-gray);">Total Records:</span><br>';
                                    echo '<strong style="font-size: 24px; color: var(--primary-blue);">' . $total_val . '</strong>';
                                }
                                echo '</div>';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-error" style="margin-top: 20px;"><i class="fas fa-info-circle"></i> No data found for the selected period and category (`'.$period_label.'`).</div>';
                            }
                        }
                    }
                    ?>
                </div>

        <?php elseif ($section == 'analytics'): ?>
            <!-- Operational Intelligence Section -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Operational Intelligence</h1>
                    <p>Centralized Analytics & Report Management System</p>
                </div>
            </div>

            <?php
            // --- FILTER LOGIC ---
            $f_type = $_GET['f_type'] ?? '';
            $f_period = $_GET['f_period'] ?? 'all';
            $f_search = $_GET['f_search'] ?? '';
            $f_sort = $_GET['f_sort'] ?? 'newest';

            // Base Query
            $sql = "SELECT m.*, u.username, u.role, r.name as uploader_name 
                    FROM manual_reports m 
                    JOIN users u ON m.user_id = u.user_id 
                    LEFT JOIN registrations r ON u.registration_id = r.registration_id
                    WHERE 1=1";
            
            // Apply Filters
            if ($f_type) {
                $sql .= " AND m.report_type = '" . $conn->real_escape_string($f_type) . "'";
            }

            if ($f_search) {
                $safe_search = $conn->real_escape_string($f_search);
                $sql .= " AND (m.report_title LIKE '%$safe_search%' OR r.name LIKE '%$safe_search%')";
            }

            if ($f_period == 'day') {
                $sql .= " AND DATE(m.report_date) = CURDATE()";
            } elseif ($f_period == 'month') {
                $sql .= " AND MONTH(m.report_date) = MONTH(CURDATE()) AND YEAR(m.report_date) = YEAR(CURDATE())";
            } elseif ($f_period == 'year') {
                $sql .= " AND YEAR(m.report_date) = YEAR(CURDATE())";
            }

            // Sorting
            if ($f_sort == 'newest') $sql .= " ORDER BY m.report_date DESC";
            elseif ($f_sort == 'oldest') $sql .= " ORDER BY m.report_date ASC";
            elseif ($f_sort == 'az') $sql .= " ORDER BY m.report_title ASC";
            elseif ($f_sort == 'za') $sql .= " ORDER BY m.report_title DESC";
            else $sql .= " ORDER BY m.created_at DESC";

            $result = $conn->query($sql);
            $reports = [];
            
            // Analytic Arrays
            $dist_type = [];
            $dist_role = [];
            $timeline = [];
            
            if ($result) {
                while($row = $result->fetch_assoc()) {
                    $reports[] = $row;
                    
                    // Populate Analytics
                    $t = $row['report_type'] ?: 'Unspecified';
                    $dist_type[$t] = ($dist_type[$t] ?? 0) + 1;

                    $c = $row['report_category'] ?: 'General';
                    $dist_role[$c] = ($dist_role[$c] ?? 0) + 1;

                    $d = date('Y-m-d', strtotime($row['report_date']));
                    $timeline[$d] = ($timeline[$d] ?? 0) + 1;
                }
            }
            ksort($timeline); // Sort timeline by date
            ?>

            <!-- Analytics Visuals -->
            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-chart-line"></i> Intelligence Overview</h3>
                    <div style="font-size: 13px; color: #94a3b8;">Analysis based on <?php echo count($reports); ?> filtered reports</div>
                </div>
                <!-- Charts Grid -->
                 <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div style="background: rgba(0,0,0,0.2); padding: 25px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                        <h4 style="font-size: 14px; color: #94a3b8; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Report Composition</h4>
                        <canvas id="chartType" style="height: 200px; width: 100%;"></canvas>
                    </div>
                    <div style="background: rgba(0,0,0,0.2); padding: 25px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                        <h4 style="font-size: 14px; color: #94a3b8; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Departmental Activity</h4>
                        <canvas id="chartRole" style="height: 200px; width: 100%;"></canvas>
                    </div>
                    <div style="background: rgba(0,0,0,0.2); padding: 25px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                        <h4 style="font-size: 14px; color: #94a3b8; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Submission Timeline</h4>
                        <canvas id="chartTimeline" style="height: 200px; width: 100%;"></canvas>
                    </div>
                 </div>
            </div>

            <!-- Filters & Controls -->
            <div class="content-section">
                <form method="GET" class="filter-container" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; padding: 20px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2);">
                    <input type="hidden" name="section" value="analytics">
                    
                    <div class="search-input-group" style="min-width: 250px; flex: 1;">
                        <i class="fas fa-search"></i>
                        <input type="text" name="f_search" value="<?php echo htmlspecialchars($f_search); ?>" placeholder="Search report content...">
                    </div>

                    <select name="f_type" style="padding: 12px; background: #0f172a; border: 1px solid var(--border-color); color: white; border-radius: 8px; min-width: 150px;">
                        <option value="">All Types</option>
                        <?php 
                        $d_types = $conn->query("SELECT DISTINCT report_type FROM manual_reports");
                        while($dt = $d_types->fetch_assoc()) echo "<option value='{$dt['report_type']}' ".($f_type==$dt['report_type']?'selected':'').">{$dt['report_type']}</option>";
                        ?>
                    </select>

                    <select name="f_period" style="padding: 12px; background: #0f172a; border: 1px solid var(--border-color); color: white; border-radius: 8px; min-width: 150px;">
                        <option value="all" <?php echo $f_period=='all'?'selected':''; ?>>All Time</option>
                        <option value="day" <?php echo $f_period=='day'?'selected':''; ?>>Today</option>
                        <option value="month" <?php echo $f_period=='month'?'selected':''; ?>>This Month</option>
                        <option value="year" <?php echo $f_period=='year'?'selected':''; ?>>This Year</option>
                    </select>

                    <select name="f_sort" style="padding: 12px; background: #0f172a; border: 1px solid var(--border-color); color: white; border-radius: 8px; min-width: 150px;">
                        <option value="newest" <?php echo $f_sort=='newest'?'selected':''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $f_sort=='oldest'?'selected':''; ?>>Oldest First</option>
                        <option value="az" <?php echo $f_sort=='az'?'selected':''; ?>>Name (A-Z)</option>
                    </select>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Analyzing</button>
                    <a href="?section=analytics" class="btn" style="background: rgba(255,255,255,0.1); color: white;">Reset</a>
                </form>
            </div>

            <!-- Data Table -->
            <div class="content-section">
                <table id="reportsTable" style="font-size: 14px;">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Report Details</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Uploaded By</th>
                            <th>Report Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($reports) > 0): foreach($reports as $r): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: #fff; margin-bottom: 4px;"><?php echo htmlspecialchars($r['report_title']); ?></div>
                                <div style="font-size: 11px; color: #64748b; font-family: monospace;">ID: <?php echo $r['report_id']; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($r['report_type']); ?></td>
                            <td><span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #60a5fa;"><?php echo htmlspecialchars($r['report_category']); ?></span></td>
                            <td>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($r['uploader_name'] ?? 'Unknown'); ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?php echo ucfirst($r['role']); ?></div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($r['report_date'])); ?></td>
                            <td style="text-align: right;">
                                <a href="<?php echo $r['file_path']; ?>" target="_blank" class="btn" style="background: rgba(255,255,255,0.05); color: #fff; padding: 6px 12px; font-size: 12px;"><i class="fas fa-eye"></i> View</a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 40px; color: #64748b;">No analytics data available for the selected criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Include Report Upload Modal -->
            <?php include 'includes/report_upload_modal.php'; ?>

            <script>
            // Data Injection for Charts
            const typeLabels = <?php echo json_encode(array_keys($dist_type)); ?>;
            const typeData = <?php echo json_encode(array_values($dist_type)); ?>;
            
            const roleLabels = <?php echo json_encode(array_keys($dist_role)); ?>;
            const roleData = <?php echo json_encode(array_values($dist_role)); ?>;
            
            const timeLabels = <?php echo json_encode(array_keys($timeline)); ?>;
            const timeData = <?php echo json_encode(array_values($timeline)); ?>;

            // Chart Configs
            const commonOptions = {
                responsive: true,
                plugins: {
                    legend: { labels: { color: '#94a3b8', font: { size: 11 } } }
                }
            };

            // 1. Types (Doughnut)
            new Chart(document.getElementById('chartType'), {
                type: 'doughnut',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        data: typeData,
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                        borderWidth: 0
                    }]
                },
                options: commonOptions
            });

            // 2. Roles (Bar)
            new Chart(document.getElementById('chartRole'), {
                type: 'bar',
                data: {
                    labels: roleLabels,
                    datasets: [{
                        label: 'Reports',
                        data: roleData,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    }]
                },
                options: { ...commonOptions, scales: { y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } }, x: { grid: { display: false }, ticks: { color: '#94a3b8' } } } }
            });

            // 3. Timeline (Line)
            new Chart(document.getElementById('chartTimeline'), {
                type: 'line',
                data: {
                    labels: timeLabels,
                    datasets: [{
                        label: 'Submissions',
                        data: timeData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { ...commonOptions, scales: { y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } }, x: { grid: { display: false }, ticks: { color: '#94a3b8' } } } }
            });
            </script>

        <?php elseif ($section == 'complaints'): ?>
            <!-- Complaint Logs -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Complaint Logs</h1>
                    <p>View and manage patient complaints</p>
                </div>
            </div>

            <div class="content-section">
                <div class="placeholder-section">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Complaint Logs Module</h3>
                    <p>Track and manage all patient complaints and feedback</p>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <script>
        function toggleDoctorFields() {
            const roles = document.getElementsByName('role');
            let role = 'doctor';
            for (let r of roles) {
                if (r.checked) {
                    role = r.value;
                    break;
                }
            }
            
            const docFields = document.getElementById('doctorFields');
            const staffFields = document.getElementById('staffFields');
            
            if (role === 'doctor') {
                docFields.style.display = 'block';
                staffFields.style.display = 'none';
                updatePermissionsUI('doctor');
            } else if (role === 'staff') {
                docFields.style.display = 'none';
                staffFields.style.display = 'block';
                updatePermissionsUI('staff');
                toggleStaffLabFields();
            } else {
                docFields.style.display = 'none';
                staffFields.style.display = 'none';
                updatePermissionsUI('patient');
            }
        }

        function updatePermissionsUI(role, containerId = 'permissionsContainer', existingPerms = '') {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            let availablePerms = [];
            if (role === 'doctor') {
                availablePerms = ['Consultation Access', 'View Medical Records', 'Write Prescriptions', 'Manage Schedule'];
            } else if (role === 'staff') {
                availablePerms = ['Registration Desk', 'Billing Access', 'Pharmacy Inventory', 'Lab Reports Access'];
            } else if (role === 'patient') {
                availablePerms = ['My Profile', 'Book Appointments', 'Canteen Orders', 'View My Prescriptions'];
            } else if (role === 'admin') {
                availablePerms = ['System Settings', 'User Management', 'Financial Reports', 'Full Database Access'];
            }

            // If existingPerms is empty, we check all by default for NEW users.
            // If it's not empty, we split and check only those.
            const activePerms = existingPerms ? existingPerms.split(', ') : (containerId === 'permissionsContainer' ? availablePerms : []);

            container.innerHTML = availablePerms.map(p => `
                <label style="cursor:pointer; display: flex; align-items: center; gap: 10px; font-size: 13px;">
                    <input type="checkbox" name="permissions[]" value="${p}" ${activePerms.includes(p) ? 'checked' : ''}> ${p}
                </label>
            `).join('');
        }

        function selectRole(roleId) {
            document.getElementById('role_' + roleId).checked = true;
            const cards = document.querySelectorAll('.role-card');
            cards.forEach(c => c.classList.remove('active'));
            document.getElementById('role_' + roleId).parentElement.classList.add('active');
            toggleDoctorFields();
        }

        function generatePass() {
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let retVal = "";
            for (let i = 0; i < 12; ++i) {
                retVal += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            document.getElementById('tempPass').value = retVal;
            document.getElementById('tempPass').type = 'text';
        }
        
        function toggleStaffLabFields() {
            const type = document.getElementById('staffTypeSelect').value;
            const labGroup = document.getElementById('labTypeGroup');
            const nurseGroup = document.getElementById('nurseDeptGroup');
            const canteenGroup = document.getElementById('canteenRoleGroup');
            
            labGroup.style.display = (type === 'lab_staff') ? 'block' : 'none';
            nurseGroup.style.display = (type === 'nurse') ? 'block' : 'none';
            canteenGroup.style.display = (type === 'canteen_staff') ? 'block' : 'none';
        }
        
        window.addEventListener('DOMContentLoaded', toggleDoctorFields);

        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        function openScheduleModal(id, name) {
            document.getElementById('sched_doc_id').value = id;
            document.getElementById('scheduleTitle').innerText = 'Schedule for Dr. ' + name;
            openModal('scheduleModal');
        }

        function openDeptModal(id, dept, spec) {
            document.getElementById('dept_doc_id').value = id;
            document.getElementById('dept_select').value = dept;
            document.getElementById('spec_input').value = spec;
            openModal('deptModal');
        }

        function openMenuModal() {
            document.getElementById('menuModalTitle').innerText = 'Add Food Item';
            document.getElementById('item_id').value = '';
            document.getElementById('item_name').value = '';
            document.getElementById('item_price').value = '';
            document.getElementById('item_desc').value = '';
            document.getElementById('item_image_file').value = '';
            openModal('canteenMenuModal');
        }

        function editMenuItem(item) {
            document.getElementById('menuModalTitle').innerText = 'Edit Food Item';
            document.getElementById('item_id').value = item.menu_id;
            document.getElementById('item_name').value = item.item_name;
            document.getElementById('item_cat').value = item.item_category;
            document.getElementById('item_diet').value = item.diet_type;
            document.getElementById('item_price').value = item.price;
            document.getElementById('item_avail').value = item.availability;
            document.getElementById('item_desc').value = item.description;
            openModal('canteenMenuModal');
        }

        function openPackageModal() {
            document.getElementById('pkgModalTitle').innerText = 'Create New Package';
            document.getElementById('pkg_id').value = '';
            document.getElementById('pkg_name').value = '';
            document.getElementById('pkg_desc').value = '';
            document.getElementById('pkg_tests').value = '';
            document.getElementById('pkg_actual').value = '';
            document.getElementById('pkg_discount').value = '';
            document.getElementById('pkg_percent').value = '';
            openModal('packageModal');
        }

        function editPackage(p) {
            document.getElementById('pkgModalTitle').innerText = 'Edit Health Package';
            document.getElementById('pkg_id').value = p.package_id;
            document.getElementById('pkg_name').value = p.package_name;
            document.getElementById('pkg_desc').value = p.package_description;
            document.getElementById('pkg_tests').value = p.included_tests;
            document.getElementById('pkg_actual').value = p.original_price;
            document.getElementById('pkg_discount').value = p.discounted_price;
            document.getElementById('pkg_percent').value = p.discount_percentage;
            document.getElementById('pkg_status').value = p.status;
            openModal('packageModal');
        }

        function calculateDiscount() {
            const actual = parseFloat(document.getElementById('pkg_actual').value) || 0;
            const discountP = parseFloat(document.getElementById('pkg_discount').value) || 0;
            if (actual > 0) {
                const percent = Math.round(((actual - discountP) / actual) * 100);
                document.getElementById('pkg_percent').value = percent > 0 ? percent : 0;
            } else {
                document.getElementById('pkg_percent').value = 0;
            }
        }

        // --- NEW SYSTEMS ---

        function toggleNotifications() {
            const dropdown = document.getElementById('notifDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('.notification-btn') && !e.target.closest('.notification-dropdown')) {
                const dropdown = document.getElementById('notifDropdown');
                if (dropdown) dropdown.style.display = 'none';
            }
        });

        function openEditUserModal(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_status').value = user.status;
            
            // Re-populate permissions based on role
            updatePermissionsUI(user.role, 'editPermissionsContainer', user.permissions);
            
            openModal('editUserModal');
        }

        // Add event listener for edit role change
        document.getElementById('edit_role').addEventListener('change', function() {
            updatePermissionsUI(this.value, 'editPermissionsContainer');
        });

        function filterTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            const filter = input.value.toUpperCase();
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let found = false;
                const td = tr[i].getElementsByTagName("td");
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? "" : "none";
            }
        }

        function filterFoodItems() {
            const input = document.getElementById('foodSearch');
            const filter = input.value.toUpperCase();
            const cards = document.querySelectorAll('.food-card');
            
            cards.forEach(card => {
                const name = card.querySelector('.food-name').textContent || card.querySelector('.food-name').innerText;
                const cat = card.querySelector('.food-category-badge').textContent || card.querySelector('.food-category-badge').innerText;
                
                if (name.toUpperCase().indexOf(filter) > -1 || cat.toUpperCase().indexOf(filter) > -1) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        }

        <?php if ($section == 'dashboard'): ?>
        // Initialize Consultation Chart
        const ctx = document.getElementById('consultationChart').getContext('2d');
        const consultationChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Consultations',
                    data: <?php echo json_encode($chart_values); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e293b',
                        titleColor: '#94a3b8',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' Patients Consulted';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
