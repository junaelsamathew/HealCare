<?php
/**
 * HealCare Email Configuration
 * Centralized settings for PHPMailer
 */

// Include PHPMailer files (adjust paths if needed, assuming absolute or relative to project root)
// We use require_once to avoid multiple inclusion errors
require_once __DIR__ . '/../phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

// Alias to global namespace for existing code
if (!class_exists('PHPMailer')) {
    class_alias('PHPMailer\PHPMailer\PHPMailer', 'PHPMailer');
}
if (!class_exists('SMTP')) {
    class_alias('PHPMailer\PHPMailer\SMTP', 'SMTP');
}
// We DON'T alias Exception because it conflicts with PHP's global Exception
// Code should use \PHPMailer\PHPMailer\Exception or PHPMailerException if needed,
// but PHPMailer's Exception inherits from \Exception anyway.

// Primary Email Credentials
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl'); // Use PHPMailer::ENCRYPTION_SMTPS in code

// The user requested to use hospitalhealcare@gmail.com
define('SMTP_USER', 'hospitalhealcare@gmail.com');
define('SMTP_PASS', 'eoikgeslcbojyaao'); 

define('EMAIL_FROM', 'hospitalhealcare@gmail.com');
define('EMAIL_FROM_NAME', 'HealCare Hospital');

/**
 * Configure PHPMailer instance with default settings
 * @param PHPMailer\PHPMailer\PHPMailer $mail
 */
function configureDefaultMail($mail) {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = 'ssl'; // For PHPMailer, 'ssl' on 465 or 'tls' on 587
    $mail->Port       = SMTP_PORT;
    
    // SSL Options for local development (XAMPP) issues
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
}
