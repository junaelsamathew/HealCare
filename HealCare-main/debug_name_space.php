<?php
include 'includes/db_connect.php';

// Try to find the user
$search = "NandanaPramod";
$sql = "SELECT * FROM users WHERE username LIKE '%$search%' OR email LIKE '%$search%'";
$result = $conn->query($sql);

echo "Searching users...\n";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "User Found: " . print_r($row, true) . "\n";
        // Check registration
        if ($row['registration_id']) {
            $reg_id = $row['registration_id'];
            $res_reg = $conn->query("SELECT * FROM registrations WHERE registration_id = $reg_id");
            if ($res_reg->num_rows > 0) {
                $reg = $res_reg->fetch_assoc();
                echo "Registration: " . print_r($reg, true) . "\n";
            }
        }
    }
} else {
    echo "No user found with that exact string in username/email.\n";
    // Try searching by similar name
    $sql2 = "SELECT u.user_id, r.name, r.registration_id FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Nandana%'";
    $result2 = $conn->query($sql2);
    while($row2 = $result2->fetch_assoc()) {
         echo "Match by name: " . print_r($row2, true) . "\n";
    }
}
?>
