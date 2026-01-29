<?php
$conn = new mysqli("127.0.0.1", "root", "", "HealCare", 3306);
$names = ['Ciya John', 'Gigi Tony'];
foreach($names as $name) {
    echo "--- $name ---\n";
    $stmt = $conn->prepare("SELECT u.user_id, r.name, u.user_role FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE ?");
    $searchTerm = "%$name%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        echo "ID: " . $row['user_id'] . " | Name: " . $row['name'] . " | Role: " . $row['user_role'] . "\n";
    }
}
?>
