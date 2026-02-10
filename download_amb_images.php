<?php
$images = [
    'ambulance_side.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/6/6d/Ambulance_in_London.jpg',
    'ambulance_front.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/1/1a/Ambulance_emergency_response_vehicle.jpg',
    'ambulance_action.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/c/c3/Mercedes-Benz_Sprinter_Ambulance.jpg'
];

$dir = __DIR__ . '/images/';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

foreach ($images as $name => $url) {
    echo "Downloading $name from $url...\n";
    $content = @file_get_contents($url);
    if ($content === false) {
        echo "Failed to download $name. Trying CURL...\n";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $content = curl_exec($ch);
        curl_close($ch);
    }
    
    if ($content) {
        file_put_contents($dir . $name, $content);
        echo "Saved to " . $dir . $name . " (" . strlen($content) . " bytes)\n";
    } else {
        echo "FAILED ALL METHODS for $name\n";
    }
}
?>
