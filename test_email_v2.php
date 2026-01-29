<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'junaelsamathew2028@mca.ajce.in'; // Updated username
    $mail->Password   = 'yiuwcrykatkfzdwv';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

    //Recipients
    $mail->setFrom('junaelsamathew2028@mca.ajce.in', 'HealCare Hospital');
    $mail->addAddress('junaelsamathew2028@mca.ajce.in'); // Send to self

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email Credentials';
    $mail->Body    = 'If you read this, the credentials are CORRECT.';

    $mail->send();
    echo 'SUCCESS';
} catch (Exception $e) {
    echo "ERROR: {$mail->ErrorInfo}";
}
?>
