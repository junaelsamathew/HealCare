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
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->addAddress(SMTP_USER); // Send to self to test

    //Content
    $mail->isHTML(true);                                  
    $mail->Subject = 'Test Email Connection';
    $mail->Body    = 'If you read this, SMTP is working!';

    $mail->send();
    echo 'Message has been sent successfully!';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
echo "</pre>";
?>
