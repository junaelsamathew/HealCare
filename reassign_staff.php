<?php
$conn = new mysqli("127.0.0.1", "root", "", "HealCare", 3306);

// 1. Identify Gigi Tony
$res = $conn->query("SELECT u.user_id, r.registration_id FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Gigi%'");
$gigi = $res->fetch_assoc();

if ($gigi) {
    $gigi_uid = $gigi['user_id'];
    echo "Found Gigi Tony (User ID: $gigi_uid)\n";
    
    // 2. Clear from other tables if they exist
    $conn->query("DELETE FROM lab_staff WHERE user_id = $gigi_uid");
    $conn->query("DELETE FROM pharmacists WHERE user_id = $gigi_uid");
    
    // 3. Ensure Nurses table exists
    $conn->query("CREATE TABLE IF NOT EXISTS nurses (
        nurse_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        department VARCHAR(100),
        shift VARCHAR(50),
        status VARCHAR(20) DEFAULT 'Active',
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )");
    
    // 4. Insert into Nurses table
    $stmt = $conn->prepare("INSERT INTO nurses (user_id, department, shift) VALUES (?, 'General Ward', 'Morning')");
    $stmt->bind_param("i", $gigi_uid);
    $stmt->execute();
    echo "Added Gigi Tony to nurses table.\n";
}

// 5. Identify Ciya John
$res = $conn->query("SELECT u.user_id FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Ciya%'");
$ciya = $res->fetch_assoc();

if ($ciya) {
    $ciya_uid = $ciya['user_id'];
    echo "Found Ciya John (User ID: $ciya_uid)\n";
    
    // 6. Update Ciya to Blood/Pathology in Lab Staff
    $conn->query("UPDATE lab_staff SET lab_type = 'Blood / Pathology Lab' WHERE user_id = $ciya_uid");
    
    // If not in lab_staff, add her
    if ($conn->affected_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO lab_staff (user_id, lab_type, status) VALUES (?, 'Blood / Pathology Lab', 'Active')");
        $stmt->bind_param("i", $ciya_uid);
        $stmt->execute();
    }
    echo "Updated Ciya John to Blood / Pathology Lab.\n";
}
?>
