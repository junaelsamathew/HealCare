<?php
include 'includes/db_connect.php';

$bad_diagnoses = [
    'dftyuhygfghj' => 'Seasonal Flu and Viral Fever',
    'kjhgvbhjnkgcfgvhb' => 'Chronic Migraine',
    'dfbfsdf' => 'Acute Bronchitis',
    'test' => 'General Wellness Checkup',
    'asdf' => 'Common Cold'
];

foreach ($bad_diagnoses as $junk => $good) {
    $stmt = $conn->prepare("UPDATE medical_records SET diagnosis = ? WHERE diagnosis = ?");
    $stmt->bind_param("ss", $good, $junk);
    $stmt->execute();
    echo "Updated $junk to $good\n";
}

// Also handle high-entropy ones or very short ones?
// For now, these specific ones from the screenshot are the priority.
?>
