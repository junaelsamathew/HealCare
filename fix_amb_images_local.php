<?php
// Function to download image
function download_image($url, $save_to) {
    echo "Downloading $url to $save_to...\n";
    $ch = curl_init($url);
    $fp = fopen($save_to, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    
    if ($httpCode == 200 && filesize($save_to) > 1000) {
        echo "Success! Size: " . filesize($save_to) . " bytes\n";
        return true;
    } else {
        echo "Failed. HTTP Code: $httpCode, Size: " . filesize($save_to) . "\n";
        @unlink($save_to);
        return false;
    }
}

$dir = 'c:/xampp/htdocs/HealCare-main/images/';
if (!file_exists($dir)) mkdir($dir, 0777, true);

// 3 High Quality Pexels Images
$images = [
    'amb_side.jpg' => 'https://images.pexels.com/photos/10121703/pexels-photo-10121703.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
    'amb_front.jpg' => 'https://images.pexels.com/photos/15752395/pexels-photo-15752395.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
    'amb_action.jpg' => 'https://images.pexels.com/photos/337909/pexels-photo-337909.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1'
];

foreach ($images as $file => $url) {
    download_image($url, $dir . $file);
}

// Update Database to use local paths
$conn = new mysqli('127.0.0.1', 'root', '', 'healcare');
$conn->query("TRUNCATE TABLE ambulance_contacts");

$p1 = 'images/amb_side.jpg';
$p2 = 'images/amb_front.jpg';
$p3 = 'images/amb_action.jpg';

$sql = "INSERT INTO ambulance_contacts (driver_name, phone_number, vehicle_number, vehicle_type, location, availability, image_url) VALUES 
('HealCare Hospital - Unit 01 (Side)', '+91 8086611101', 'HC-24-AMB-01', 'Advanced Life Support', 'Main Emergency Bay', 'Available', '$p1'),
('HealCare Hospital - Unit 02 (Front)', '+91 8086611102', 'HC-24-AMB-02', 'Rapid Response Unit', 'South Campus', 'On Duty', '$p2'),
('HealCare Hospital - Unit 03 (Action)', '+91 8086611103', 'HC-24-AMB-03', 'Critical Care Unit', 'North Wing', 'Available', '$p3')";

if ($conn->query($sql)) {
    echo "\nDATABASE UPDATED with LOCAL IMAGES.";
}
?>
