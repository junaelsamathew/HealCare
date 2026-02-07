<?php
include 'includes/db_connect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

echo "<h2>Debug Email Logic</h2>";

// 1. Get Latest Appointment
$last_appt = $conn->query("SELECT appointment_id FROM appointments ORDER BY appointment_id DESC LIMIT 1")->fetch_assoc();
$appt_id = $last_appt['appointment_id'];
echo "Testing with Appointment ID: " . $appt_id . "<br>";

// 2. Test Query
$sql = "
    SELECT a.appointment_date, a.appointment_time, a.queue_number, 
           d_reg.name as doctor_name, 
           p_reg.name as patient_name, u_pat.email as patient_email
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.user_id
    JOIN users u_doc ON d.user_id = u_doc.user_id
    JOIN registrations d_reg ON u_doc.registration_id = d_reg.registration_id
    JOIN users u_pat ON a.patient_id = u_pat.user_id
    JOIN registrations p_reg ON u_pat.registration_id = p_reg.registration_id
    WHERE a.appointment_id = $appt_id
";
$res_details = $conn->query($sql);

if ($res_details && $res_details->num_rows > 0) {
    $appt_details = $res_details->fetch_assoc();
    echo "Found Details:<br>";
    print_r($appt_details);
    
    // 3. Try Send
    if (!empty($appt_details['patient_email'])) {
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = 2; // Enable verbose
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'healcare.mail.services@gmail.com';
            $mail->Password   = 'yiuwcrykatkfzdwv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

            $mail->setFrom('healcare.mail.services@gmail.com', 'Mary Queens Mission Hospital');
            $mail->addAddress($appt_details['patient_email'], $appt_details['patient_name']);

            $email_body = "Debug Test Mail";
            $mail->isHTML(true);
            $mail->Subject = 'Debug Check';
            $mail->Body = $email_body;
            $mail->send();
            echo "<br><b>Email Sent Successfully!</b>";
        } catch (Exception $e) {
            echo "<br><b>Mailer Error:</b> " . $mail->ErrorInfo;
        }
    } else {
        echo "<br>Patient Email is empty.";
    }

} else {
    echo "<br><b>Query Returned No Rows!</b> Check your JOINS.";
    echo "<br>SQL: " . $sql;
}
?>
