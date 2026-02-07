<?php
include 'includes/db_connect.php';
$email = 'augustinejoyaljose@gmail.com';

echo "--- USERS ---\n";
$res = $conn->query("SELECT * FROM users WHERE email = '$email'");
while($row = $res->fetch_assoc()) print_r($row);

echo "\n--- REGISTRATIONS ---\n";
$res = $conn->query("SELECT * FROM registrations WHERE email = '$email'");
while($row = $res->fetch_assoc()) print_r($row);

echo "\n--- PROFILES ---\n";
$res = $conn->query("SELECT * FROM patient_profiles WHERE user_id IN (SELECT user_id FROM users WHERE email = '$email')");
while($row = $res->fetch_assoc()) print_r($row);
?>
