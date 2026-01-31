<?php
include 'includes/db_connect.php';

echo "=== ROOMS ===\n";
$res = $conn->query("SELECT room_id, room_number, ward_id FROM rooms WHERE room_id = 5");
if ($res->num_rows > 0) {
    print_r($res->fetch_assoc());
} else {
    echo "Room ID 5 not found.\n";
}
?>
