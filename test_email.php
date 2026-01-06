<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer(true);

echo "<h1>SMTP Debug Test</h1>";
echo "<pre>"; // Format output

try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                     
    $mail->SMTPAuth   = true;                                   
    
    // VERIFY THIS: Is this 100% the email address you generated the App Password for?
    $mail->Username   = 'healcare.mail.services@gmail.com';                     
    $mail->Password   = 'yiuwcrykatkfzdwv';                               

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
    $mail->Port       = 465;                                    
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    //Recipients
    $mail->setFrom('healcare.mail.services@gmail.com', 'HealCare Debug');
    $mail->addAddress('healcare.mail.services@gmail.com'); // Send to self to test

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
