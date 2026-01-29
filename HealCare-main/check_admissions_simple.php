<?php
include 'includes/db_connect.php';

echo "=== ADMISSIONS (id, status, room_id) ===\n";
$res = $conn->query("SELECT admission_id, status, room_id FROM admissions");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
