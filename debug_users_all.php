<?php
$conn = new mysqli("127.0.0.1", "root", "", "healcare", 3306);
if ($conn->connect_error) {
    $conn = new mysqli("127.0.0.1", "root", "", "HealCare", 3306);
}

echo "COLUMNS IN users:\n";
$res = $conn->query("SHOW COLUMNS FROM users");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\nCOLUMNS IN registrations:\n";
$res = $conn->query("SHOW COLUMNS FROM registrations");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\nALL USERS:\n";
$res = $conn->query("SELECT u.user_id, r.name, u.username FROM users u JOIN registrations r ON u.registration_id = r.registration_id");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['user_id'] . " | " . $row['name'] . " | " . $row['username'] . "\n";
    }
} else {
    echo "Error searching users: " . $conn->error . "\n";
}
?>
