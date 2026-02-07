<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT r.name, u.role, r.status FROM registrations r JOIN users u ON r.registration_id = u.registration_id");
echo "Current Users in DB:\n";
while($row = $res->fetch_assoc()) {
    echo "- {$row['name']} ({$row['role']}) - Status: {$row['status']}\n";
}
?>
