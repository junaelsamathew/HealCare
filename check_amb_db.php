<?php
$conn = new mysqli('localhost', 'root', '', 'healcare');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$res = $conn->query("SELECT driver_name, image_url FROM ambulance_contacts");
while($row = $res->fetch_assoc()) {
    echo $row['driver_name'] . " : " . $row['image_url'] . "\n";
}
$conn->close();
?>
