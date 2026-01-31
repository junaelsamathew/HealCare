<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM appointments ORDER BY appointment_id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
