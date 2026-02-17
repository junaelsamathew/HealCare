<?php
include 'includes/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

echo "<h1>SMTP Debug Test</h1>";
echo "<pre>"; // Format output

try {
    //Server settings
    configureDefaultMail($mail);
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Override to show debug info

    //Recipients
    $mail->addAddress(EMAIL_FROM); // Send to self to test

    //Content
    $mail->isHTML(true);                                  
    $mail->Subject = 'Test Email Connection - Centralized Config';
    $mail->Body    = 'If you read this, SMTP is working with Centralized Config using ' . EMAIL_FROM;

    $mail->send();
    echo 'Message has been sent successfully!';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
echo "</pre>";
?>
