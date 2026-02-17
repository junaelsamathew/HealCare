<?php
include 'includes/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    configureDefaultMail($mail);
    $mail->addAddress(SMTP_USER); // Send to self

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
