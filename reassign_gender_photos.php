<?php
include 'includes/db_connect.php';

// MAPPING BASED ON IMAGE ANALYSIS:
// doctor-1: Male (Older, glasses)
// doctor-5: Male (Younger/Middle-aged)
// doctor-6: Male
// doctor-7: Male
// doctor-2: Female (Younger)
// doctor-4: Female
// doctor-8: Female
// doctor-9: Female
// doctor-10: Female (Middle-aged)
// dr_june_mary_antony.png: Female

$photo_mapping = [
    // --- FEMALE DOCTORS ---
    'mary.mariam@healcare.com'   => 'images/doctor-10.jpg', // Female
    'leena.jose@healcare.com'    => 'images/doctor-8.jpg',  // Female
    'june.antony@healcare.com'   => 'images/dr_june_mary_antony.png', // Female (Specific)
    'meenu.thomas@healcare.com'  => 'images/doctor-9.jpg',  // Female
    'maria.vineeth@healcare.com' => 'images/doctor-4.jpg',  // Female
    'jincymathew72@gmail.com'    => 'images/doctor-2.jpg',  // Female
    'cicilymathew56@gmail.com'   => 'images/doctor-10.jpg', // Female
    'pavithrabinu657@gmail.com'  => 'images/doctor-8.jpg',  // Female
    'amalaannjoseph@gmail.com'   => 'images/doctor-2.jpg',  // Female

    // --- MALE DOCTORS ---
    'jacob.mathew@healcare.com'   => 'images/doctor-1.jpg',  // Male
    'krishnan.manoj@healcare.com' => 'images/doctor-5.jpg',  // Male
    'alan.thomas@healcare.com'    => 'images/doctor-6.jpg',  // Male
    'suresh.k@healcare.com'       => 'images/doctor-7.jpg',  // Male
    'kurian.thomas@healcare.com'  => 'images/doctor-5.jpg',  // Male
    'johnymathew56@gmail.com'     => 'images/doctor-1.jpg',  // Male
    'jaisanmathew43@gmail.com'    => 'images/doctor-6.jpg'   // Male
];

$conn->begin_transaction();
try {
    foreach ($photo_mapping as $email => $photo) {
        $stmt = $conn->prepare("UPDATE registrations SET profile_photo = ? WHERE email = ?");
        $stmt->bind_param("ss", $photo, $email);
        $stmt->execute();
        if ($conn->affected_rows > 0) {
            echo "Updated photo for $email\n";
        }
    }
    $conn->commit();
    echo "\nAll male and female doctor pictures have been correctly assigned.\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
