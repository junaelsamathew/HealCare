<?php
$conn = new mysqli("127.0.0.1", "root", "", "HealCare", 3306);

echo "SEARCH FOR STAFF:\n";
$res = $conn->query("SELECT u.user_id, r.name, u.role FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%John%' OR r.name LIKE '%Tony%'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\nLAB STAFF TABLE:\n";
$res = $conn->query("SELECT * FROM lab_staff");
while($row = $res->fetch_assoc()) print_r($row);

echo "\nNURSES TABLE EXITS?\n";
$res = $conn->query("SHOW TABLES LIKE 'nurses'");
if($res->num_rows > 0) {
    echo "Nurses table exists. Content:\n";
    $res2 = $conn->query("SELECT * FROM nurses");
    while($row = $res2->fetch_assoc()) print_r($row);
} else {
    echo "Nurses table MISSING.\n";
}
?>
