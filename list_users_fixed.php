<?php
$conn = new mysqli("127.0.0.1", "root", "", "HealCare", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "COLUMNS IN users:\n";
$res = $conn->query("SHOW COLUMNS FROM users");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";

echo "\nALL REGISTERED STAFF/USERS:\n";
$res = $conn->query("SELECT u.user_id, r.name, u.username, u.role FROM users u JOIN registrations r ON u.registration_id = r.registration_id");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "ID: " . $row['user_id'] . " | " . $row['name'] . " | Role: " . $row['role'] . "\n";
    }
}
?>
