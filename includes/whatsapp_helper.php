<?php
function sendWhatsAppMessage($to, $message) {
    $sid    = "AC5902922fa99ff5bbf36a08867cd70a4c";
    $token  = "ab15848990f477ce9f195b2d7fefe867";
    $twilio = new \stdClass(); // Placeholder if we were using SDK, but we use curl
    
    // Ensure number is in E.164 format for Twilio (whatsapp:+91...)
    // Removing non-numeric characters
    $clean_phone = preg_replace('/[^0-9]/', '', $to);
    
    // If it's a 10 digit Indian number, add 91
    if (strlen($clean_phone) == 10) {
        $clean_phone = '91' . $clean_phone;
    }
    
    // If it doesn't start with +, add it (though for whatsapp: prefix we handle it below)
    
    $to_whatsapp = "whatsapp:+" . $clean_phone;
    $from_whatsapp = "whatsapp:+14155238886"; // Standard Twilio Sandbox Number
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
    
    $data = [
        'From' => $from_whatsapp,
        'To' => $to_whatsapp,
        'Body' => $message
    ];
    
    $post = http_build_query($data);
    $x = curl_init($url);
    curl_setopt($x, CURLOPT_POST, true);
    curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP issues
    curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($x, CURLOPT_USERPWD, "$sid:$token");
    curl_setopt($x, CURLOPT_POSTFIELDS, $post);
    $y = curl_exec($x);
    $http_code = curl_getinfo($x, CURLINFO_HTTP_CODE);
    curl_close($x);
    
    // Log for debugging
    $log_file = __DIR__ . '/../backups/whatsapp.log';
    $log_entry = date('Y-m-d H:i:s') . " - Sending to $to_whatsapp - Status: $http_code - Response: $y\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    return $y;
}
?>
