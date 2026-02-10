<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'healcare');
$res = $conn->query("SELECT count(*) as total FROM ambulance_contacts");
$row = $res->fetch_assoc();
echo "TOTAL_AMBULANCES: " . $row['total'];
?>
