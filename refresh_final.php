<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'healcare');
$conn->query("TRUNCATE TABLE ambulance_contacts");

$u1 = 'https://upload.wikimedia.org/wikipedia/commons/6/6d/Ambulance_in_London.jpg';
$u2 = 'https://upload.wikimedia.org/wikipedia/commons/1/1a/Ambulance_emergency_response_vehicle.jpg';
$u3 = 'https://upload.wikimedia.org/wikipedia/commons/c/c3/Mercedes-Benz_Sprinter_Ambulance.jpg';

$sql = "INSERT INTO ambulance_contacts (driver_name, phone_number, vehicle_number, vehicle_type, location, availability, image_url) VALUES 
('HealCare Hospital - Unit 01 (Side)', '+91 8086611101', 'HC-24-AMB-01', 'Advanced Life Support', 'Main Emergency Bay', 'Available', '$u1'),
('HealCare Hospital - Unit 02 (Front)', '+91 8086611102', 'HC-24-AMB-02', 'Rapid Response Unit', 'South Campus', 'On Duty', '$u2'),
('HealCare Hospital - Unit 03 (Action)', '+91 8086611103', 'HC-24-AMB-03', 'Critical Care Unit', 'North Wing', 'Available', '$u3')";

if ($conn->query($sql)) echo "FINAL_SYNC_DONE";
?>
