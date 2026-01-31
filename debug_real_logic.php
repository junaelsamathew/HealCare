<?php
include 'includes/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

$bill_id = 65; // Latest Bill
$appt_id = 53; // Latest Appt

echo "<h2>Simulating Email Logic for Appt: $appt_id</h2>";

// COPY-PASTED LOGIC FROM payment_process.php (The part I wrote)
try {
    // 1. Get Appointment Details
    $stmt_a = $conn->prepare("SELECT appointment_date, appointment_time, queue_number, doctor_id, patient_id FROM appointments WHERE appointment_id = ?");
    $stmt_a->bind_param("i", $appt_id);
    $stmt_a->execute();
    $res_a = $stmt_a->get_result();
    
    if ($res_a->num_rows > 0) {
        $appt_row = $res_a->fetch_assoc();
        echo "Found Appt ID: $appt_id <br>";
        
        $date_str = $appt_row['appointment_date'];
        $token_num = $appt_row['queue_number'];
        $doc_id = $appt_row['doctor_id'];
        $pat_id = $appt_row['patient_id'];

        // 2. Get Doctor Name
        $doc_name = "Doctor";
        $q_doc = $conn->query("SELECT name FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE u.user_id = $doc_id");
        if ($q_doc && $q_doc->num_rows > 0) {
            $doc_name = $q_doc->fetch_assoc()['name'];
        }
        echo "Doctor: $doc_name <br>";

        // 3. Get Patient Details 
        $pat_name = "Patient";
        $pat_email = "";
        
        $q_pp = $conn->query("SELECT name FROM patient_profiles WHERE user_id = $pat_id");
        if ($q_pp && $q_pp->num_rows > 0) {
            $pat_name = $q_pp->fetch_assoc()['name'];
        } else {
             $q_pr = $conn->query("SELECT name FROM registrations r JOIN users u ON r.registration_id = u.registration_id WHERE u.user_id = $pat_id");
             if ($q_pr && $q_pr->num_rows > 0) $pat_name = $q_pr->fetch_assoc()['name'];
        }
        echo "Patient Name: $pat_name <br>";

        $q_u = $conn->query("SELECT email FROM users WHERE user_id = $pat_id");
        if ($q_u && $q_u->num_rows > 0) {
            $pat_email = $q_u->fetch_assoc()['email'];
        }
        echo "Patient Email: $pat_email <br>";

        // 4. Send Email
        if (!empty($pat_email)) {
            $mail = new PHPMailer(true);
            $mail->SMTPDebug = 2; // VERBOSE DEBUG
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'junaelsamathew2028@mca.ajce.in';
            $mail->Password   = 'yiuwcrykatkfzdwv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

            $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'Mary Queens Mission Hospital');
            $mail->addAddress($pat_email, $pat_name);

            $email_booking_number = date('Y', strtotime($date_str)) . "/" . str_pad($appt_id, 6, '0', STR_PAD_LEFT);

            $email_body = "Debug Test for Appt ID $appt_id";
            
            $mail->isHTML(true);
            $mail->Subject = 'Debug Confirmation';
            $mail->Body = $email_body;

            $mail->send();
            echo "<b>EMAIL SENT!</b>";
        } else {
            echo "<b>EMAIL SKIPPED: NO EMAIL FOUND</b>";
        }
    } else {
        echo "Appt Not Found";
    }
} catch (Exception $e) {
    echo "<b>MAIL ERROR:</b> " . $e->getMessage() . " <br>" . $mail->ErrorInfo; 
}
?>
