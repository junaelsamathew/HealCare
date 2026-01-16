<?php
include 'includes/db_connect.php';
$id = 36;
$res = $conn->query("SELECT name FROM registrations WHERE registration_id = $id");
if ($row = $res->fetch_assoc()) {
    echo "Current Name: [" . $row['name'] . "]\n";
}
?>
