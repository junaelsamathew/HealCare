<?php
$conn = new mysqli("127.0.0.1", "root", "", "HealCare", 3306);

echo "--- Ciya John Search ---\n";
$res = $conn->query("SELECT u.user_id, r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Ciya%'");
while($row = $res->fetch_assoc()) {
    echo "USER ID: {$row['user_id']} | NAME: {$row['name']}\n";
}

echo "\n--- Gigi Tony Search ---\n";
$res = $conn->query("SELECT u.user_id, r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Gigi%'");
while($row = $res->fetch_assoc()) {
    echo "USER ID: {$row['user_id']} | NAME: {$row['name']}\n";
}

echo "\n--- All staff roles in users table ---\n";
$res = $conn->query("SELECT u.user_id, r.name, u.role FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.role = 'staff'");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['user_id']} | NAME: {$row['name']} | ROLE: {$row['role']}\n";
}
?>
