<?php
$conn = new mysqli('localhost', 'root', '', 'healcare');
$res = $conn->query("SHOW COLUMNS FROM ambulance_contacts LIKE 'image_url'");
$row = $res->fetch_assoc();
print_r($row);
?>
