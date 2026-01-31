<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT user_id, name FROM patient_profiles WHERE name LIKE '%Juna%'");
while($row = $res->fetch_assoc()){
    echo "UID: " . $row['user_id'] . " | NAME: " . $row['name'] . "\n";
}
?>
