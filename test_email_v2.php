<?php
include 'includes/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    //Server settings
    configureDefaultMail($mail);

    //Recipients
    $mail->addAddress(EMAIL_USER); // Send to self

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Verify New Hospital Email Credentials';
    $mail->Body    = 'If you read this, the credentials for <b>' . EMAIL_USER . '</b> are CORRECT.';

    $mail->send();
    echo 'SUCCESS';
} catch (Exception $e) {
    echo "ERROR: {$mail->ErrorInfo}";
}
?>
