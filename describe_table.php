<?php
$conn = new mysqli('localhost', 'root', '', 'healcare');
$res = $conn->query("DESCRIBE ambulance_contacts");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
